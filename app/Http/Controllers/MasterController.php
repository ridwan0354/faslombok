<?php

namespace App\Http\Controllers;

use App\Models\Competition;
use App\Models\CompetitionAward;
use App\Models\Entry;
use App\Models\Event;
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
            ->with(['activeRubric', 'judgeAssignments.user', 'latestPublication'])
            ->get();

        return view('master.competitions.index', compact('event', 'competitions'));
    }

    public function storeCompetition(Request $request)
    {
        $event = Event::where('status', 'active')->first() ?? Event::latest()->first();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'format' => ['required', 'in:individual,team'],
            'eligibility_rule' => ['nullable', 'string'],
            'location' => ['nullable', 'string'],
        ]);

        DB::transaction(function () use ($event, $data) {
            $competition = Competition::create(array_merge($data, [
                'event_id' => $event->id,
                'status' => 'ready',
            ]));

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

    public function competitionDetail(Competition $competition)
    {
        $competition->load([
            'activeRubric.criteria',
            'activeRubric.ratingOptions',
            'judgeAssignments.user',
            'entries.participant.group',
            'entries.team.group',
            'awards',
        ]);

        $availableJudges = User::where('role', 'judge')->where('status', 'active')->get();

        return view('master.competitions.detail', compact('competition', 'availableJudges'));
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
        $groups = Group::where('event_id', $event->id)->get();
        $grades = GradeLevel::where('event_id', $event->id)->get();
        $competitions = Competition::where('event_id', $event->id)->where('format', 'individual')->get();

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
        $code = sprintf('FAS-%s-%04d', $event->year, $nextCodeNum);

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
            ->get();
        $competitions = Competition::where('event_id', $event->id)->get();

        return view('master.judges.index', compact('event', 'judges', 'competitions'));
    }

    public function storeJudge(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'unique:users,phone'],
            'password' => ['required', 'string', 'min:6'],
        ]);

        User::create([
            'name' => $data['name'],
            'phone' => trim($data['phone']),
            'password' => Hash::make($data['password']),
            'role' => 'judge',
            'status' => 'active',
        ]);

        return redirect()->route('master.judges')->with('success', 'Akun juri baru berhasil dibuat.');
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
            $selectedComp = Competition::with(['judgeAssignments.user', 'activeRubric.criteria'])->find($request->input('competition_id'));
            if ($selectedComp) {
                $judges = $selectedComp->judgeAssignments()->where('active', true)->with('user')->get();
                $entries = $selectedComp->entries()->with(['participant.group', 'team.group', 'scoreSubmissions.items'])->get();

                foreach ($entries as $entry) {
                    $finalScore = $this->scoringService->getFinalScore($entry);
                    $judgeScores = [];
                    foreach ($judges as $ja) {
                        $sub = $entry->scoreSubmissions->firstWhere('assignment_id', $ja->id);
                        $judgeScores[$ja->id] = $sub ? [
                            'submission_id' => $sub->id,
                            'score_total' => $sub->score_total,
                            'status' => $sub->status,
                            'items' => $sub->items,
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
}
