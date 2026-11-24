<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\ScoreSubmission;
use App\Services\QrService;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class JudgeController extends Controller
{
    protected QrService $qrService;
    protected ScoringService $scoringService;

    public function __construct(QrService $qrService, ScoringService $scoringService)
    {
        $this->qrService = $qrService;
        $this->scoringService = $scoringService;
    }

    public function dashboard()
    {
        $user = Auth::user();
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $assignments = JudgeAssignment::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->with(['competition.entries.scoreSubmissions' => function ($q) use ($user) {
                $q->whereHas('assignment', function ($aq) use ($user) {
                    $aq->where('user_id', $user->id);
                });
            }])
            ->get();

        return view('judge.dashboard', compact('user', 'event', 'assignments'));
    }

    public function scanner()
    {
        $user = Auth::user();
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        return view('judge.scanner', compact('user', 'event'));
    }

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

    public function scoringForm(Entry $entry)
    {
        $user = Auth::user();
        $entry->loadMissing(['team.group', 'team.members', 'participant.group', 'participant.gradeLevel']);
        $competition = $entry->competition;

        // Check assignment permissions
        $assignment = JudgeAssignment::where('competition_id', $competition->id)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();

        if (!$assignment && !$user->isMaster()) {
            abort(403, 'Anda tidak ditugaskan sebagai juri pada cabang lomba ini.');
        }

        $rubric = $competition->activeRubric;
        if (!$rubric) {
            return back()->with('error', 'Lomba ini belum memiliki rubrik penilaian aktif.');
        }

        // Existing score by this judge
        $existingSubmission = null;
        if ($assignment) {
            $existingSubmission = ScoreSubmission::with('items')
                ->where('entry_id', $entry->id)
                ->where('assignment_id', $assignment->id)
                ->where('rubric_id', $rubric->id)
                ->first();
        }

        // Peer scores if permitted
        $peerSubmissions = collect();
        if ($assignment && $assignment->can_view_peer_scores) {
            $peerSubmissions = ScoreSubmission::with(['assignment.user', 'items.criterion'])
                ->where('entry_id', $entry->id)
                ->where('assignment_id', '!=', $assignment->id)
                ->where('status', 'submitted')
                ->get();
        } elseif ($user->isMaster()) {
            $peerSubmissions = ScoreSubmission::with(['assignment.user', 'items.criterion'])
                ->where('entry_id', $entry->id)
                ->get();
        }

        return view('judge.scoring-form', compact('entry', 'competition', 'rubric', 'assignment', 'existingSubmission', 'peerSubmissions'));
    }

    public function submitScore(Request $request, Entry $entry)
    {
        $user = Auth::user();
        $competition = $entry->competition;

        $assignment = JudgeAssignment::where('competition_id', $competition->id)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();

        if (!$assignment && !$user->isMaster()) {
            abort(403, 'Anda tidak memiliki hak untuk menilai cabang lomba ini.');
        }

        if ($assignment && !$assignment->can_score && !$user->isMaster()) {
            abort(403, 'Hak akses Anda pada lomba ini hanya untuk melihat (View-Only).');
        }

        $rubric = $competition->activeRubric;
        if (!$rubric) {
            return back()->with('error', 'Rubrik penilaian tidak ditemukan.');
        }

        $itemsData = $request->input('scores', []);
        $notes = $request->input('notes');
        $actionType = $request->input('action_type', 'submit'); // 'draft' or 'submit'
        $status = ($actionType === 'draft') ? 'draft' : 'submitted';

        try {
            $submission = $this->scoringService->submitScore(
                $entry,
                $assignment,
                $itemsData,
                $notes,
                $user,
                $status
            );

            $msg = ($status === 'submitted') 
                ? "Nilai berhasil disimpan & disubmit! Total Nilai: {$submission->score_total}" 
                : "Draft penilaian berhasil disimpan!";

            return redirect()->route('judge.competition.entries', $competition->id)->with('success', $msg);
        } catch (\Exception $e) {
            return back()->withInput()->with('error', 'Gagal menyimpan nilai: ' . $e->getMessage());
        }
    }

    public function competitionEntries(Competition $competition)
    {
        $user = Auth::user();

        $assignment = JudgeAssignment::where('competition_id', $competition->id)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->first();

        if (!$assignment && !$user->isMaster()) {
            abort(403, 'Anda tidak ditugaskan pada cabang lomba ini.');
        }

        $entries = $competition->entries()
            ->with(['participant.group', 'participant.gradeLevel', 'team.group', 'team.members', 'scoreSubmissions.assignment.user'])
            ->orderBy('queue_number')
            ->get();

        return view('judge.competition-entries', compact('competition', 'assignment', 'entries'));
    }
}
