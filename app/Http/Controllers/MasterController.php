<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\CompetitionAward;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventSetting;
use App\Models\GradeLevel;
use App\Models\Group;
use App\Models\JudgeAssignment;
use App\Models\Participant;
use App\Models\RatingOption;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\ScoreAuditLog;
use App\Models\ScoreSubmission;
use App\Models\SyncOperation;
use App\Models\Team;
use App\Models\User;
use App\Services\ImportExportService;
use App\Services\QrService;
use App\Services\RankingService;
use App\Services\ScoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class MasterController extends Controller
{
    protected QrService $qrService;
    protected ScoringService $scoringService;
    protected RankingService $rankingService;
    protected ImportExportService $importExportService;

    public function __construct(
        QrService $qrService,
        ScoringService $scoringService,
        RankingService $rankingService,
        ImportExportService $importExportService
    ) {
        $this->qrService = $qrService;
        $this->scoringService = $scoringService;
        $this->rankingService = $rankingService;
        $this->importExportService = $importExportService;
    }

    public function dashboard()
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $stats = [
            'total_participants' => Participant::where('event_id', $event->id)->count(),
            'total_competitions' => Competition::where('event_id', $event->id)->count(),
            'total_judges' => User::where('role', 'judge')->count(),
            'total_groups' => Group::where('event_id', $event->id)->count(),
            'total_entries' => Entry::where('event_id', $event->id)->count(),
            'completed_entries' => Entry::where('event_id', $event->id)->where('status', 'completed')->count(),
            'published_competitions' => Competition::where('event_id', $event->id)->where('status', 'published')->count(),
            'sync_operations' => SyncOperation::count(),
        ];

        $competitions = Competition::where('event_id', $event->id)
            ->withCount('entries')
            ->with(['judgeAssignments.user', 'latestPublication'])
            ->get();

        $recentAudits = ScoreAuditLog::with(['actor', 'entry.competition', 'entry.participant'])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('master.dashboard', compact('event', 'stats', 'competitions', 'recentAudits'));
    }

    /* -------------------------------------------------------------
     * Competitions & Rubrics
     * ------------------------------------------------------------- */
    public function competitions()
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();
        $competitions = Competition::where('event_id', $event->id)
            ->withCount('entries')
            ->with(['activeRubric', 'judgeAssignments.user', 'latestPublication', 'gradeLevels'])
            ->get();
        $gradeLevels = GradeLevel::where('event_id', $event->id)->orderBy('sort_order')->get();

        return view('master.competitions.index', compact('event', 'competitions', 'gradeLevels'));
    }

    public function storeCompetition(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'format' => ['required', 'in:individual,team'],
            'grade_ids' => ['nullable', 'array'],
            'grade_ids.*' => ['exists:grade_levels,id'],
        ]);

        $gradeIds = $request->input('grade_ids', []);
        $selectedGrades = GradeLevel::whereIn('id', $gradeIds)->orderBy('sort_order')->pluck('name');
        $eligibilityRule = $selectedGrades->isNotEmpty() ? $selectedGrades->join(', ') : 'Semua Tingkat';

        DB::transaction(function () use ($event, $data, $gradeIds, $eligibilityRule) {
            $competition = Competition::create([
                'event_id' => $event->id,
                'name' => $data['name'],
                'format' => $data['format'],
                'eligibility_rule' => $eligibilityRule,
                'status' => 'ready',
                'is_locked' => false,
            ]);

            if (!empty($gradeIds)) {
                $competition->gradeLevels()->sync($gradeIds);
            }

            // Create default active rubric
            $rubric = Rubric::create([
                'competition_id' => $competition->id,
                'version' => 1,
                'status' => 'active',
            ]);

            // Default Rating Options: Baik (100), Cukup (75), Kurang (50)
            RatingOption::create(['rubric_id' => $rubric->id, 'label' => 'Baik', 'numeric_value' => 100, 'sort_order' => 1]);
            RatingOption::create(['rubric_id' => $rubric->id, 'label' => 'Cukup', 'numeric_value' => 75, 'sort_order' => 2]);
            RatingOption::create(['rubric_id' => $rubric->id, 'label' => 'Kurang', 'numeric_value' => 50, 'sort_order' => 3]);

            // Default Criterion
            RubricCriterion::create([
                'rubric_id' => $rubric->id,
                'title' => 'Penguasaan Materi / Penampilan',
                'weight_pct' => 100.0,
                'sort_order' => 1,
                'required' => true,
            ]);

            // Default Awards
            $defaultAwards = [
                ['rank_position' => 1, 'rank_label' => 'Juara 1', 'points_for_group' => 10],
                ['rank_position' => 2, 'rank_label' => 'Juara 2', 'points_for_group' => 7],
                ['rank_position' => 3, 'rank_label' => 'Juara 3', 'points_for_group' => 5],
                ['rank_position' => 4, 'rank_label' => 'Juara Harapan 1', 'points_for_group' => 3],
            ];
            foreach ($defaultAwards as $aw) {
                CompetitionAward::create(array_merge($aw, ['competition_id' => $competition->id]));
            }
        });

        return redirect()->route('master.competitions')->with('success', 'Cabang lomba baru berhasil ditambahkan.');
    }

    public function updateCompetition(Request $request, Competition $competition)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'format' => ['required', 'in:individual,team'],
            'grade_ids' => ['nullable', 'array'],
            'grade_ids.*' => ['exists:grade_levels,id'],
        ]);

        $gradeIds = $request->input('grade_ids', []);
        $selectedGrades = GradeLevel::whereIn('id', $gradeIds)->orderBy('sort_order')->pluck('name');
        $eligibilityRule = $selectedGrades->isNotEmpty() ? $selectedGrades->join(', ') : 'Semua Tingkat';

        $competition->update([
            'name' => $data['name'],
            'format' => $data['format'],
            'eligibility_rule' => $eligibilityRule,
        ]);

        $competition->gradeLevels()->sync($gradeIds);

        return redirect()->back()->with('success', "Data cabang lomba '{$competition->name}' berhasil diperbarui.");
    }

    public function destroyCompetition(Competition $competition)
    {
        $name = $competition->name;

        // Check if there are active score submissions for this competition
        $entryIds = $competition->entries()->pluck('id');
        $hasScores = ScoreSubmission::whereIn('entry_id', $entryIds)->exists();

        if ($hasScores) {
            return redirect()->back()->with('error', "Gagal menghapus: Cabang lomba '{$name}' sudah memiliki data nilai dari dewan juri.");
        }

        DB::transaction(function () use ($competition) {
            $competition->gradeLevels()->detach();
            $competition->delete();
        });

        return redirect()->route('master.competitions')->with('success', "Cabang lomba '{$name}' berhasil dihapus.");
    }

    public function competitionDetail(Competition $competition)
    {
        $competition->load([
            'activeRubric.criteria',
            'activeRubric.ratingOptions',
            'judgeAssignments.user',
            'entries.participant.group',
            'entries.participant.gradeLevel',
            'entries.team.group',
            'entries.scoreSubmissions',
            'awards',
        ]);

        $availableJudges = User::where('role', 'judge')->where('status', 'active')->orderBy('name')->get();

        return view('master.competitions.detail', compact('competition', 'availableJudges'));
    }

    public function destroyEntry(Competition $competition, Entry $entry)
    {
        if ($entry->competition_id !== $competition->id) {
            abort(404);
        }

        $name = $entry->display_name;
        if ($entry->scoreSubmissions()->exists()) {
            return back()->with('error', "Gagal mengeluarkan: Peserta '{$name}' sudah memiliki nilai dari juri.");
        }

        $entry->delete();

        return back()->with('success', "Peserta '{$name}' berhasil dikeluarkan dari lomba {$competition->name}.");
    }

    public function updateRubricCriteria(Request $request, Competition $competition)
    {
        $rubric = $competition->activeRubric;
        if (!$rubric) {
            return back()->with('error', 'Rubrik tidak ditemukan.');
        }

        $criteriaData = $request->input('criteria', []);
        $totalWeight = 0;

        foreach ($criteriaData as $c) {
            $totalWeight += (float) ($c['weight_pct'] ?? 0);
        }

        if (round($totalWeight, 2) != 100.00) {
            return back()->with('error', "Gagal menyimpan: Total bobot kriteria harus tepat 100%. Saat ini: {$totalWeight}%.");
        }

        DB::transaction(function () use ($rubric, $criteriaData) {
            $rubric->criteria()->delete();

            $sort = 1;
            foreach ($criteriaData as $c) {
                if (!empty(trim($c['title'] ?? ''))) {
                    RubricCriterion::create([
                        'rubric_id' => $rubric->id,
                        'title' => trim($c['title']),
                        'weight_pct' => (float) $c['weight_pct'],
                        'sort_order' => $sort++,
                        'required' => true,
                    ]);
                }
            }
        });

        return back()->with('success', 'Kriteria rubrik penilaian berhasil diperbarui (Total Bobot: 100%).');
    }

    public function toggleLock(Request $request, Competition $competition)
    {
        $actor = Auth::user();
        $isLocking = !$competition->is_locked;
        $reason = $request->input('reason', $isLocking ? 'Kunci nilai oleh Master' : 'Buka kunci nilai untuk perbaikan');

        $competition->update([
            'is_locked' => $isLocking,
            'locked_at' => $isLocking ? now() : null,
            'status' => $isLocking ? 'locked' : 'ongoing',
        ]);

        ScoreAuditLog::create([
            'actor_id' => $actor->id,
            'action' => $isLocking ? 'lock' : 'unlock',
            'reason' => $reason,
            'after_json' => ['is_locked' => $isLocking, 'competition_id' => $competition->id],
        ]);

        $msg = $isLocking ? 'Penilaian lomba berhasil dikunci.' : 'Kunci penilaian lomba dibuka.';
        return back()->with('success', $msg);
    }

    public function publish(Competition $competition)
    {
        $publication = $this->rankingService->publish($competition, Auth::user());
        return back()->with('success', "Hasil {$competition->name} berhasil dipublikasikan (Versi {$publication->version})!");
    }

    /* -------------------------------------------------------------
     * Participants & CSV Import
     * ------------------------------------------------------------- */
    public function participants(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $query = Participant::with(['group', 'gradeLevel', 'entries.competition', 'qrToken'])
            ->where('event_id', $event->id);

        if ($request->filled('search')) {
            $q = $request->input('search');
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('participant_code', 'like', "%{$q}%");
            });
        }

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->input('group_id'));
        }

        if ($request->filled('competition_id')) {
            $compId = $request->input('competition_id');
            $query->whereHas('entries', function ($eq) use ($compId) {
                $eq->where('competition_id', $compId);
            });
        }

        $participants = $query->latest('id')->paginate(20)->withQueryString();
        $groups = Group::where('event_id', $event->id)->orderBy('name')->get();
        $grades = GradeLevel::where('event_id', $event->id)->orderBy('sort_order')->get();
        $competitions = Competition::where('event_id', $event->id)
            ->where('format', 'individual')
            ->with('gradeLevels')
            ->orderBy('name')
            ->get();

        return view('master.participants.index', compact('event', 'participants', 'groups', 'grades', 'competitions'));
    }

    public function storeParticipant(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'group_id' => ['required', 'exists:groups,id'],
            'grade_id' => ['nullable', 'exists:grade_levels,id'],
            'gender' => ['required', 'in:L,P'],
            'competitions' => ['nullable', 'array'],
        ]);

        $nextCodeNum = Participant::where('event_id', $event->id)->count() + 1;
        do {
            $code = sprintf('FAS-%s-%04d', $event->year, $nextCodeNum++);
        } while (Participant::where('event_id', $event->id)->where('participant_code', $code)->exists());

        $participant = Participant::create([
            'event_id' => $event->id,
            'group_id' => $data['group_id'],
            'grade_id' => $data['grade_id'],
            'participant_code' => $code,
            'name' => $data['name'],
            'gender' => $data['gender'],
            'active' => true,
        ]);

        // Generate QR
        $this->qrService->getOrCreateForParticipant($participant);

        // Assign to competitions
        if (!empty($data['competitions'])) {
            foreach ($data['competitions'] as $compId) {
                $queue = Entry::where('competition_id', $compId)->count() + 1;
                Entry::create([
                    'event_id' => $event->id,
                    'competition_id' => $compId,
                    'participant_id' => $participant->id,
                    'queue_number' => $queue,
                    'status' => 'registered',
                ]);
            }
        }

        return redirect()->route('master.participants')->with('success', "Peserta {$participant->name} ({$participant->participant_code}) berhasil didaftarkan.");
    }

    public function updateParticipant(Request $request, Participant $participant)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'group_id' => ['required', 'exists:groups,id'],
            'grade_id' => ['nullable', 'exists:grade_levels,id'],
            'gender' => ['required', 'in:L,P'],
            'active' => ['nullable', 'boolean'],
            'competitions' => ['nullable', 'array'],
        ]);

        DB::transaction(function () use ($participant, $data, $request) {
            $participant->update([
                'name' => $data['name'],
                'group_id' => $data['group_id'],
                'grade_id' => $data['grade_id'] ?? null,
                'gender' => $data['gender'],
                'active' => $request->boolean('active', true),
            ]);

            // Sync competitions
            $selectedCompIds = $data['competitions'] ?? [];
            $existingEntries = $participant->entries()->get()->keyBy('competition_id');

            // Add new entries
            foreach ($selectedCompIds as $compId) {
                if (!$existingEntries->has($compId)) {
                    $queue = Entry::where('competition_id', $compId)->count() + 1;
                    Entry::create([
                        'event_id' => $participant->event_id,
                        'competition_id' => $compId,
                        'participant_id' => $participant->id,
                        'queue_number' => $queue,
                        'status' => 'registered',
                    ]);
                }
            }

            // Remove unselected entries if no scores yet
            foreach ($existingEntries as $compId => $entry) {
                if (!in_array($compId, $selectedCompIds)) {
                    if ($entry->scoreSubmissions()->count() === 0) {
                        $entry->delete();
                    }
                }
            }
        });

        return redirect()->route('master.participants')->with('success', "Data peserta {$participant->name} ({$participant->participant_code}) berhasil diperbarui.");
    }

    public function destroyParticipant(Participant $participant)
    {
        $name = $participant->name;
        $code = $participant->participant_code;

        $participant->delete();

        return redirect()->route('master.participants')->with('success', "Peserta {$name} ({$code}) berhasil dihapus.");
    }

    /* -------------------------------------------------------------
     * Kelompok / TPQ Management
     * ------------------------------------------------------------- */
    public function storeGroup(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $code = !empty($data['code']) 
            ? strtoupper(trim($data['code'])) 
            : strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $data['name']), 0, 4) . '-' . rand(10, 99));

        Group::create([
            'event_id' => $event->id,
            'name' => trim($data['name']),
            'code' => $code,
        ]);

        return redirect()->back()->with('success', "Kelompok / TPQ '{$data['name']}' berhasil ditambahkan.");
    }

    public function updateGroup(Request $request, Group $group)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
        ]);

        $group->update([
            'name' => trim($data['name']),
            'code' => !empty($data['code']) ? strtoupper(trim($data['code'])) : $group->code,
        ]);

        return redirect()->back()->with('success', "Kelompok / TPQ '{$group->name}' berhasil diperbarui.");
    }

    public function destroyGroup(Group $group)
    {
        $participantCount = $group->participants()->count();
        $teamCount = $group->teams()->count();

        if ($participantCount > 0 || $teamCount > 0) {
            return redirect()->back()->with('error', "Gagal menghapus: Kelompok / TPQ '{$group->name}' masih memiliki {$participantCount} peserta dan {$teamCount} tim terdaftar.");
        }

        $name = $group->name;
        $group->delete();

        return redirect()->back()->with('success', "Kelompok / TPQ '{$name}' berhasil dihapus.");
    }

    /* -------------------------------------------------------------
     * Tingkat Kelas Management
     * ------------------------------------------------------------- */
    public function storeGradeLevel(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $code = !empty($data['code']) 
            ? strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', str_replace([' ', '-'], '_', $data['code'])))
            : strtoupper(preg_replace('/[^A-Za-z0-9_]/', '', str_replace([' ', '-'], '_', $data['name'])));

        $maxSort = GradeLevel::where('event_id', $event->id)->max('sort_order') ?? 0;

        GradeLevel::create([
            'event_id' => $event->id,
            'name' => trim($data['name']),
            'code' => $code,
            'sort_order' => $data['sort_order'] ?? ($maxSort + 1),
        ]);

        return redirect()->back()->with('success', "Tingkat Kelas '{$data['name']}' berhasil ditambahkan.");
    }

    public function updateGradeLevel(Request $request, GradeLevel $gradeLevel)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        $gradeLevel->update([
            'name' => trim($data['name']),
            'code' => !empty($data['code']) ? strtoupper(trim($data['code'])) : $gradeLevel->code,
            'sort_order' => $data['sort_order'] ?? $gradeLevel->sort_order,
        ]);

        return redirect()->back()->with('success', "Tingkat Kelas '{$gradeLevel->name}' berhasil diperbarui.");
    }

    public function destroyGradeLevel(GradeLevel $gradeLevel)
    {
        $participantCount = $gradeLevel->participants()->count();
        if ($participantCount > 0) {
            return redirect()->back()->with('error', "Gagal menghapus: Tingkat Kelas '{$gradeLevel->name}' masih digunakan oleh {$participantCount} peserta.");
        }

        $name = $gradeLevel->name;
        $gradeLevel->delete();

        return redirect()->back()->with('success', "Tingkat Kelas '{$name}' berhasil dihapus.");
    }

    public function importParticipants(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt'],
        ]);

        $content = file_get_contents($request->file('file')->getRealPath());
        $rows = $this->importExportService->parseCsv($content);

        if (empty($rows)) {
            return back()->with('error', 'File CSV kosong atau format tidak sesuai.');
        }

        $res = $this->importExportService->importParticipants($event, $rows);

        if (!$res['success']) {
            return back()->with('error', $res['message']);
        }

        $msg = "Impor selesai: {$res['created']} peserta baru dibuat, {$res['updated']} peserta diperbarui, {$res['entries_created']} pendaftaran lomba ditambahkan.";
        return redirect()->route('master.participants')->with('success', $msg);
    }

    /* -------------------------------------------------------------
     * Print QR Badges / ID Cards
     * ------------------------------------------------------------- */
    public function printQr(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $query = Participant::with(['group', 'gradeLevel', 'entries.competition'])
            ->where('event_id', $event->id)
            ->where('active', true);

        if ($request->filled('group_id')) {
            $query->where('group_id', $request->input('group_id'));
        }

        if ($request->filled('participant_id')) {
            $query->where('id', $request->input('participant_id'));
        }

        $participants = $query->get()->map(function ($p) {
            $qrToken = $this->qrService->getOrCreateForParticipant($p);
            return [
                'type' => 'participant',
                'name' => $p->name,
                'code' => $p->participant_code,
                'group' => $p->group ? $p->group->name : '-',
                'grade' => $p->gradeLevel ? $p->gradeLevel->name : '-',
                'comps' => $p->entries->pluck('competition.name')->implode(', '),
                'qr_svg' => $this->qrService->renderSvg($qrToken->token_plain, 140),
            ];
        });

        // Also teams
        $teams = Team::with(['group', 'competition', 'members'])->where('event_id', $event->id)->get()->map(function ($t) {
            $qrToken = $this->qrService->getOrCreateForTeam($t);
            return [
                'type' => 'team',
                'name' => $t->name,
                'code' => $t->team_code,
                'group' => $t->group ? $t->group->name : '-',
                'grade' => 'Beregu',
                'comps' => $t->competition ? $t->competition->name : '-',
                'qr_svg' => $this->qrService->renderSvg($qrToken->token_plain, 140),
            ];
        });

        $badges = $participants->concat($teams);

        return view('master.print-qr', compact('event', 'badges'));
    }

    /* -------------------------------------------------------------
     * Judges & Assignments
     * ------------------------------------------------------------- */
    public function judges()
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();
        $judges = User::where('role', 'judge')
            ->with(['judgeAssignments.competition'])
            ->orderBy('name')
            ->get();
        $competitions = Competition::where('event_id', $event->id)->orderBy('name')->get();

        return view('master.judges.index', compact('event', 'judges', 'competitions'));
    }

    protected function normalizePhone(string $raw): string
    {
        $phone = preg_replace('/[\s-]+/', '', trim($raw));
        if (str_starts_with($phone, '+62')) {
            $phone = '0' . substr($phone, 3);
        }
        return $phone;
    }

    public function storeJudge(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $normalizedPhone = $this->normalizePhone((string) $request->input('phone', ''));
        $request->merge(['phone' => $normalizedPhone]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        // Jika password dikosongkan, gunakan Nomor HP sebagai username & password default
        $rawPassword = !empty($data['password']) ? $data['password'] : $data['phone'];

        $judge = User::create([
            'name' => trim($data['name']),
            'phone' => $data['phone'],
            'password' => Hash::make($rawPassword),
            'role' => 'judge',
            'status' => 'active',
        ]);

        if ($event) {
            \App\Models\EventUser::firstOrCreate(
                ['event_id' => $event->id, 'user_id' => $judge->id],
                ['event_role' => 'judge']
            );
        }

        return redirect()->route('master.judges')->with('success', "Akun juri '{$judge->name}' berhasil dibuat (Login & Password: {$judge->phone}).");
    }

    public function updateJudge(Request $request, User $judge)
    {
        if ($judge->role !== 'judge') {
            abort(403, 'Hanya akun juri yang dapat diubah di menu ini.');
        }

        $normalizedPhone = $this->normalizePhone((string) $request->input('phone', ''));
        $request->merge(['phone' => $normalizedPhone]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'unique:users,phone,' . $judge->id],
            'password' => ['nullable', 'string', 'min:6'],
            'status' => ['required', 'in:active,inactive'],
        ]);

        $updatePayload = [
            'name' => trim($data['name']),
            'phone' => $data['phone'],
            'status' => $data['status'],
        ];

        if (!empty($data['password'])) {
            $updatePayload['password'] = Hash::make($data['password']);
        } elseif ($request->boolean('sync_password_phone')) {
            $updatePayload['password'] = Hash::make($data['phone']);
        }

        $judge->update($updatePayload);

        return redirect()->route('master.judges')->with('success', "Data juri '{$judge->name}' berhasil diperbarui.");
    }

    public function destroyJudge(User $judge)
    {
        if ($judge->role !== 'judge') {
            abort(403, 'Hanya akun juri yang dapat dihapus di menu ini.');
        }

        $assignmentIds = $judge->judgeAssignments()->pluck('id');
        $hasScores = ScoreSubmission::whereIn('assignment_id', $assignmentIds)->exists();

        if ($hasScores) {
            return redirect()->back()->with('error', "Gagal menghapus: Juri '{$judge->name}' sudah menginput nilai peserta. Anda bisa menonaktifkan akun melalui Edit Juri.");
        }

        $name = $judge->name;
        DB::transaction(function () use ($judge) {
            $judge->judgeAssignments()->delete();
            $judge->eventUsers()->delete();
            $judge->delete();
        });

        return redirect()->route('master.judges')->with('success', "Akun juri '{$name}' berhasil dihapus.");
    }

    public function assignJudge(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'competition_id' => ['required', 'exists:competitions,id'],
            'can_score' => ['nullable', 'boolean'],
            'can_view_peer_scores' => ['nullable', 'boolean'],
            'required_for_completion' => ['nullable', 'boolean'],
        ]);

        JudgeAssignment::updateOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $data['user_id'],
                'competition_id' => $data['competition_id'],
            ],
            [
                'can_view_participants' => true,
                'can_view_scores' => true,
                'can_score' => $request->boolean('can_score', true),
                'can_view_peer_scores' => $request->boolean('can_view_peer_scores', false),
                'required_for_completion' => $request->boolean('required_for_completion', true),
                'active' => true,
            ]
        );

        return back()->with('success', 'Penugasan dan izin juri berhasil disimpan.');
    }

    public function revokeJudgeAssignment(JudgeAssignment $assignment)
    {
        $assignment->delete();
        return back()->with('success', 'Penugasan juri berhasil dicabut.');
    }

    /* -------------------------------------------------------------
     * Queue & Status Management
     * ------------------------------------------------------------- */
    public function queue(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();
        $competitions = Competition::where('event_id', $event->id)->get();

        $selectedComp = null;
        $entries = collect();

        if ($request->filled('competition_id')) {
            $selectedComp = Competition::find($request->input('competition_id'));
            if ($selectedComp) {
                $entries = $selectedComp->entries()
                    ->with(['participant.group', 'team.group'])
                    ->orderByRaw("CASE 
                        WHEN status = 'performing' THEN 1 
                        WHEN status = 'called' THEN 2 
                        WHEN status = 'queued' THEN 3 
                        WHEN status = 'registered' THEN 4 
                        ELSE 5 END")
                    ->orderBy('queue_number')
                    ->get();
            }
        } elseif ($competitions->isNotEmpty()) {
            return redirect()->route('master.queue', ['competition_id' => $competitions->first()->id]);
        }

        return view('master.queue.index', compact('event', 'competitions', 'selectedComp', 'entries'));
    }

    public function updateQueueStatus(Request $request, Entry $entry)
    {
        $request->validate([
            'status' => ['required', 'in:registered,queued,called,performing,completed,disqualified,no_show'],
        ]);

        $entry->update(['status' => $request->input('status')]);

        return back()->with('success', "Status {$entry->display_name} diubah menjadi {$entry->status}.");
    }

    /* -------------------------------------------------------------
     * Score Monitoring Matrix & Audit Logs
     * ------------------------------------------------------------- */
    public function scoresMonitoring(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();
        $competitions = Competition::where('event_id', $event->id)->get();

        $selectedComp = null;
        $matrix = [];
        $judges = collect();

        if ($request->filled('competition_id')) {
            $selectedComp = Competition::with(['judgeAssignments.user', 'activeRubric.criteria', 'activeRubric.ratingOptions'])->find($request->input('competition_id'));
            if ($selectedComp) {
                $judges = $selectedComp->judgeAssignments()->where('active', true)->with('user')->get();
                $entries = $selectedComp->entries()->with([
                    'participant.group', 
                    'team.group', 
                    'scoreSubmissions.items.criterion',
                    'scoreSubmissions.items.ratingOption',
                    'scoreSubmissions.assignment.user'
                ])->get();

                foreach ($entries as $entry) {
                    $finalScore = $this->scoringService->getFinalScore($entry);
                    $judgeScores = [];
                    foreach ($judges as $ja) {
                        $sub = $entry->scoreSubmissions->firstWhere('assignment_id', $ja->id);
                        $judgeScores[$ja->id] = $sub ? [
                            'submission_id' => $sub->id,
                            'judge_name' => $ja->user->name,
                            'score_total' => (float) $sub->score_total,
                            'status' => $sub->status,
                            'notes' => $sub->notes,
                            'submitted_at' => $sub->submitted_at ? $sub->submitted_at->setTimezone('Asia/Makassar')->format('d M Y, H:i') . ' WITA' : null,
                            'items' => $sub->items->map(function ($it) {
                                return [
                                    'title' => $it->criterion ? $it->criterion->title : '-',
                                    'weight_pct' => $it->criterion ? (float)$it->criterion->weight_pct : 0,
                                    'rating_label' => $it->ratingOption ? $it->ratingOption->label : '-',
                                    'numeric_value' => (float)$it->numeric_value,
                                    'weighted_score' => (float)$it->weighted_score,
                                    'comment' => $it->comment,
                                ];
                            })->values()->toArray(),
                        ] : null;
                    }

                    $matrix[] = [
                        'entry' => $entry,
                        'name' => $entry->display_name,
                        'code' => $entry->entity_code,
                        'group' => $entry->group ? $entry->group->name : '-',
                        'status' => $entry->status,
                        'final_score' => $finalScore['final_score'],
                        'is_complete' => $finalScore['is_complete'],
                        'judge_scores' => $judgeScores,
                    ];
                }
            }
        } elseif ($competitions->isNotEmpty()) {
            return redirect()->route('master.scores', ['competition_id' => $competitions->first()->id]);
        }

        return view('master.scores.monitoring', compact('event', 'competitions', 'selectedComp', 'judges', 'matrix'));
    }

    /* -------------------------------------------------------------
     * Reports & Exports
     * ------------------------------------------------------------- */
    public function reports(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();
        $competitions = Competition::where('event_id', $event->id)->with('latestPublication')->get();
        $groupStandings = $this->rankingService->getGroupChampionStandings($event);

        return view('master.reports.index', compact('event', 'competitions', 'groupStandings'));
    }

    public function exportCompetitionCsv(Competition $competition)
    {
        $leaderboard = $this->rankingService->getLeaderboard($competition);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"Rekap_Nilai_{$competition->name}.csv\"",
        ];

        $callback = function () use ($leaderboard, $competition) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Cabang Lomba:', $competition->name]);
            fputcsv($handle, ['Tanggal Cetak:', now()->format('Y-m-d H:i:s')]);
            fputcsv($handle, []);
            fputcsv($handle, ['Peringkat', 'Penghargaan', 'Kode Peserta/Tim', 'Nama', 'Kelompok / Kafilah', 'Status', 'Total Nilai', 'Poin Kafilah']);

            foreach ($leaderboard as $row) {
                fputcsv($handle, [
                    $row['rank'] ?? '-',
                    $row['award_label'] ?? '-',
                    $row['code'],
                    $row['name'],
                    $row['group'],
                    $row['status'],
                    $row['final_score'],
                    $row['group_points'],
                ]);
            }
            fclose($handle);
        };

        return response()->stream($callback, 200, $headers);
    }

    /* -------------------------------------------------------------
     * Certificate / Piagam Penghargaan
     * ------------------------------------------------------------- */
    public function certificates(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();
        $setting = EventSetting::firstOrCreate(['event_id' => $event->id]);
        $template = $setting->getCertificateTemplate();

        $competitions = Competition::where('event_id', $event->id)->get();
        $groups = Group::where('event_id', $event->id)->get();

        $selectedCompId = $request->input('competition_id');
        $recipientType = $request->input('type', 'all');
        $search = $request->input('search');

        $recipients = $this->collectCertificateRecipients($event, $selectedCompId, $recipientType, $search);

        return view('master.certificates.index', compact(
            'event',
            'setting',
            'template',
            'competitions',
            'groups',
            'recipients',
            'selectedCompId',
            'recipientType',
            'search'
        ));
    }

    public function saveCertificateTemplate(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();
        $setting = EventSetting::firstOrCreate(['event_id' => $event->id]);

        $raw = $request->has('template') ? $request->input('template') : $request->all();

        $validator = validator($raw, [
            'background_image' => 'nullable|string',
            'paper_size' => 'nullable|string',
            'orientation' => 'nullable|string',
            'fields' => 'required|array',
        ]);

        $data = $validator->validate();

        $currentConfig = $setting->config_json ?? [];
        $currentConfig['certificate_template'] = [
            'background_image' => $data['background_image'] ?? ($currentConfig['certificate_template']['background_image'] ?? '/images/sample-sertifikat-a4.jpg'),
            'paper_size' => $data['paper_size'] ?? 'a4',
            'orientation' => $data['orientation'] ?? 'landscape',
            'fields' => $data['fields'],
        ];

        $setting->config_json = $currentConfig;
        $setting->save();

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Template dan posisi area sertifikat berhasil disimpan.']);
        }

        return redirect()->back()->with('success', 'Template dan posisi area sertifikat berhasil disimpan.');
    }

    public function uploadCertificateBackground(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();
        $setting = EventSetting::firstOrCreate(['event_id' => $event->id]);

        $request->validate([
            'background' => 'required|image|mimes:jpeg,png,jpg|max:10240',
        ]);

        $file = $request->file('background');
        $uploadDir = public_path('uploads/certificates');
        \Illuminate\Support\Facades\File::ensureDirectoryExists($uploadDir);
        $filename = 'cert_bg_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        $file->move($uploadDir, $filename);

        $bgUrl = '/uploads/certificates/' . $filename;

        $currentConfig = $setting->config_json ?? [];
        $certTemplate = $currentConfig['certificate_template'] ?? EventSetting::defaultCertificateTemplate();
        $certTemplate['background_image'] = $bgUrl;
        $currentConfig['certificate_template'] = $certTemplate;

        $setting->config_json = $currentConfig;
        $setting->save();

        return redirect()->back()->with('success', 'Gambar template sertifikat baru berhasil diunggah.');
    }

    public function resetCertificateTemplate(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();
        $setting = EventSetting::firstOrCreate(['event_id' => $event->id]);

        $currentConfig = $setting->config_json ?? [];
        $currentConfig['certificate_template'] = EventSetting::defaultCertificateTemplate();
        $setting->config_json = $currentConfig;
        $setting->save();

        return redirect()->back()->with('success', 'Template sertifikat berhasil di-reset ke contoh bawaan resmi A4 FAS 2026.');
    }

    public function printCertificates(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();
        $setting = EventSetting::firstOrCreate(['event_id' => $event->id]);
        $template = $setting->getCertificateTemplate();

        $selectedCompId = $request->input('competition_id');
        $recipientType = $request->input('type', 'all');
        $search = $request->input('search');
        $singleEntryId = $request->input('entry_id');
        $singleParticipantId = $request->input('participant_id');

        $recipients = $this->collectCertificateRecipients($event, $selectedCompId, $recipientType, $search);

        if ($singleEntryId) {
            $recipients = array_values(array_filter($recipients, fn($r) => ($r['entry_id'] ?? null) == $singleEntryId));
        } elseif ($singleParticipantId) {
            $recipients = array_values(array_filter($recipients, fn($r) => ($r['participant_id'] ?? null) == $singleParticipantId));
        }

        return view('master.certificates.print', compact('event', 'template', 'recipients'));
    }

    protected function collectCertificateRecipients($event, $competitionId = null, $type = 'all', $search = null): array
    {
        $compQuery = Competition::where('event_id', $event->id);
        if ($competitionId) {
            $compQuery->where('id', $competitionId);
        }
        $competitions = $compQuery->get();

        $recipients = [];
        $certNum = 1;

        foreach ($competitions as $comp) {
            $leaderboard = $this->rankingService->getLeaderboard($comp);

            foreach ($leaderboard as $row) {
                $isWinner = !empty($row['rank']) && $row['rank'] <= 6 && !empty($row['award_label']);

                if ($type === 'winners' && !$isWinner) {
                    continue;
                }
                if ($type === 'participants' && $isWinner) {
                    continue;
                }

                $name = $row['name'] ?? '-';
                $groupName = $row['group'] ?? $row['group_name'] ?? '-';

                if ($search) {
                    $s = strtolower($search);
                    if (strpos(strtolower($name), $s) === false && strpos(strtolower($groupName), $s) === false) {
                        continue;
                    }
                }

                $awardText = $isWinner ? strtoupper($row['award_label']) : 'PESERTA';

                $recipients[] = [
                    'id' => 'entry_' . $row['entry']->id,
                    'entry_id' => $row['entry']->id,
                    'participant_id' => $row['entry']->participant_id,
                    'team_id' => $row['entry']->team_id,
                    'name' => $name,
                    'award' => $awardText,
                    'is_winner' => $isWinner,
                    'rank' => $row['rank'] ?? null,
                    'competition' => $comp->name,
                    'group' => $groupName,
                    'code' => $row['entry']->participant ? $row['entry']->participant->participant_code : ($row['entry']->team ? $row['entry']->team->team_code : '-'),
                    'certificate_number' => sprintf('%03d/FAS-LBK/XII/2026', $certNum++),
                ];
            }
        }

        if (!$competitionId && $type !== 'winners') {
            $unregisteredParticipants = Participant::where('event_id', $event->id)
                ->whereDoesntHave('entries')
                ->with('group')
                ->get();

            foreach ($unregisteredParticipants as $p) {
                if ($search) {
                    $s = strtolower($search);
                    if (strpos(strtolower($p->name), $s) === false && (!$p->group || strpos(strtolower($p->group->name), $s) === false)) {
                        continue;
                    }
                }

                $recipients[] = [
                    'id' => 'part_' . $p->id,
                    'entry_id' => null,
                    'participant_id' => $p->id,
                    'team_id' => null,
                    'name' => $p->name,
                    'award' => 'PESERTA',
                    'is_winner' => false,
                    'rank' => null,
                    'competition' => 'Festival Anak Sholeh 2026',
                    'group' => $p->group ? $p->group->name : '-',
                    'code' => $p->participant_code,
                    'certificate_number' => sprintf('%03d/FAS-LBK/XII/2026', $certNum++),
                ];
            }
        }

        return $recipients;
    }
}
