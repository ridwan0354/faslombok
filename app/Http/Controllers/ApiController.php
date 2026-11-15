<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\SyncOperation;
use App\Models\User;
use App\Services\QrService;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ApiController extends Controller
{
    protected QrService $qrService;
    protected ScoringService $scoringService;

    public function __construct(QrService $qrService, ScoringService $scoringService)
    {
        $this->qrService = $qrService;
        $this->scoringService = $scoringService;
    }

    /**
     * API Login via Phone + Password
     */
    public function login(Request $request)
    {
        $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $phone = trim($request->input('phone'));
        if (str_starts_with($phone, '+62')) {
            $phone = '0' . substr($phone, 3);
        }

        $user = User::where('phone', $phone)->first();

        if (!$user || !Hash::check($request->input('password'), $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Nomor HP atau password salah.',
            ], 401);
        }

        if (!$user->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Akun dinonaktifkan oleh panitia.',
            ], 403);
        }

        Auth::login($user);
        $user->update(['last_login_at' => now()]);

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'role' => $user->role,
            ],
        ]);
    }

    /**
     * Judge assignments and permissions
     */
    public function assignments(Request $request)
    {
        $user = Auth::user();
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $assignments = JudgeAssignment::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->with(['competition.activeRubric.criteria', 'competition.activeRubric.ratingOptions'])
            ->get();

        return response()->json([
            'success' => true,
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
            ],
            'assignments' => $assignments,
        ]);
    }

    /**
     * Resolve scanned QR code
     */
    public function resolveQr(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
        ]);

        $user = Auth::user();
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $result = $this->qrService->resolve($request->input('token'), $user, $event);

        return response()->json($result);
    }

    /**
     * Get active rubric for a competition
     */
    public function getRubric(Competition $competition)
    {
        $rubric = $competition->activeRubric()->with(['criteria', 'ratingOptions'])->first();

        if (!$rubric) {
            return response()->json([
                'success' => false,
                'message' => 'Rubrik aktif belum tersedia untuk lomba ini.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'rubric' => $rubric,
        ]);
    }

    /**
     * Submit single judge score (idempotent PUT)
     */
    public function submitScore(Request $request, Entry $entry)
    {
        $user = Auth::user();
        $competition = $entry->competition;

        $assignment = JudgeAssignment::where('competition_id', $competition->id)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();

        if (!$assignment && !$user->isMaster()) {
            return response()->json(['success' => false, 'message' => 'Anda tidak ditugaskan pada lomba ini.'], 403);
        }

        if ($assignment && !$assignment->can_score && !$user->isMaster()) {
            return response()->json(['success' => false, 'message' => 'Hak akses Anda hanya untuk melihat (View-Only).'], 403);
        }

        $scores = $request->input('scores', []);
        $notes = $request->input('notes');
        $status = $request->input('status', 'submitted');

        try {
            $submission = $this->scoringService->submitScore(
                $entry,
                $assignment,
                $scores,
                $notes,
                $user,
                $status
            );

            return response()->json([
                'success' => true,
                'submission' => $submission,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Batch offline sync
     */
    public function syncBatch(Request $request)
    {
        $user = Auth::user();
        $drafts = $request->input('drafts', []);

        if (empty($drafts)) {
            return response()->json([
                'success' => true,
                'message' => 'Tidak ada draft untuk disinkronkan.',
                'synced_uuids' => [],
            ]);
        }

        $syncedUuids = [];
        $failedUuids = [];

        foreach ($drafts as $draft) {
            $clientUuid = $draft['client_uuid'] ?? null;
            if (!$clientUuid) continue;

            $entryId = $draft['entry_id'] ?? null;
            $scores = $draft['scores'] ?? [];
            $notes = $draft['notes'] ?? null;

            $entry = Entry::with('competition')->find($entryId);
            if (!$entry) {
                $failedUuids[] = ['uuid' => $clientUuid, 'reason' => 'Entry tidak ditemukan.'];
                continue;
            }

            // Verify judge assignment
            $assignment = JudgeAssignment::where('competition_id', $entry->competition_id)
                ->where('user_id', $user->id)
                ->where('active', true)
                ->first();

            if (!$assignment || (!$assignment->can_score && !$user->isMaster())) {
                SyncOperation::create([
                    'client_uuid' => $clientUuid,
                    'actor_id' => $user->id,
                    'status' => 'rejected',
                    'rejection_reason' => 'Hak akses juri telah dicabut saat offline.',
                    'processed_at' => now(),
                ]);
                $failedUuids[] = ['uuid' => $clientUuid, 'reason' => 'Hak akses dicabut.'];
                continue;
            }

            if ($entry->competition->is_locked && !$user->isMaster()) {
                SyncOperation::create([
                    'client_uuid' => $clientUuid,
                    'actor_id' => $user->id,
                    'status' => 'rejected',
                    'rejection_reason' => 'Perlombaan telah dikunci oleh panitia.',
                    'processed_at' => now(),
                ]);
                $failedUuids[] = ['uuid' => $clientUuid, 'reason' => 'Lomba sudah dikunci.'];
                continue;
            }

            try {
                $this->scoringService->submitScore(
                    $entry,
                    $assignment,
                    $scores,
                    $notes,
                    $user,
                    'submitted'
                );

                SyncOperation::create([
                    'client_uuid' => $clientUuid,
                    'actor_id' => $user->id,
                    'status' => 'synced',
                    'processed_at' => now(),
                ]);

                $syncedUuids[] = $clientUuid;
            } catch (\Exception $e) {
                SyncOperation::create([
                    'client_uuid' => $clientUuid,
                    'actor_id' => $user->id,
                    'status' => 'conflict',
                    'rejection_reason' => $e->getMessage(),
                    'processed_at' => now(),
                ]);
                $failedUuids[] = ['uuid' => $clientUuid, 'reason' => $e->getMessage()];
            }
        }

        return response()->json([
            'success' => true,
            'synced_count' => count($syncedUuids),
            'synced_uuids' => $syncedUuids,
            'failed_count' => count($failedUuids),
            'failures' => $failedUuids,
        ]);
    }
}
