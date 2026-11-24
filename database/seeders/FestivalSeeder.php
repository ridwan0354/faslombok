<?php

namespace Database\Seeders;

use App\Models\Competition;
use App\Models\CompetitionAward;
use App\Models\Entry;
use App\Models\Event;
use App\Models\EventSetting;
use App\Models\EventUser;
use App\Models\GradeLevel;
use App\Models\Group;
use App\Models\JudgeAssignment;
use App\Models\Participant;
use App\Models\RatingOption;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FestivalSeeder extends Seeder
{
    public function run(): void
    {
        $qrService = app(QrService::class);
        $defaultPasswordHash = Hash::make('password123');

        // 1. Master Admin Account
        $master = User::firstOrCreate(
            ['phone' => '081234567890'],
            [
                'name' => 'Master Panitia FAS 2026',
                'email' => 'admin@faslombok2026.id',
                'password' => $defaultPasswordHash,
                'role' => 'master',
                'status' => 'active',
            ]
        );

        // 2. Active Event
        $event = Event::firstOrCreate(
            ['year' => 2026],
            [
                'name' => 'Festival Anak Sholeh Lombok 2026',
                'start_at' => '2026-11-20 08:00:00',
                'end_at' => '2026-11-22 17:00:00',
                'status' => 'active',
            ]
        );

        EventSetting::firstOrCreate(
            ['event_id' => $event->id],
            [
                'enable_group_champion' => true,
                'config_json' => [
                    'theme' => 'purple',
                    'location' => 'Masjid Uswatun Hasanah - LDII Lombok',
                ],
            ]
        );

        EventUser::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $master->id],
            ['event_role' => 'master']
        );

        // 3. Grade Levels (9 tingkat)
        $gradesData = [
            ['code' => 'PAUD', 'name' => 'PAUD', 'sort_order' => 1],
            ['code' => 'TK_A', 'name' => 'TK A', 'sort_order' => 2],
            ['code' => 'TK_B', 'name' => 'TK B', 'sort_order' => 3],
            ['code' => 'KELAS_1', 'name' => 'Kelas 1 SD/MI', 'sort_order' => 4],
            ['code' => 'KELAS_2', 'name' => 'Kelas 2 SD/MI', 'sort_order' => 5],
            ['code' => 'KELAS_3', 'name' => 'Kelas 3 SD/MI', 'sort_order' => 6],
            ['code' => 'KELAS_4', 'name' => 'Kelas 4 SD/MI', 'sort_order' => 7],
            ['code' => 'KELAS_5', 'name' => 'Kelas 5 SD/MI', 'sort_order' => 8],
            ['code' => 'KELAS_6', 'name' => 'Kelas 6 SD/MI', 'sort_order' => 9],
        ];

        $grades = [];
        foreach ($gradesData as $g) {
            $grades[$g['code']] = GradeLevel::firstOrCreate(
                ['event_id' => $event->id, 'code' => $g['code']],
                ['name' => $g['name'], 'sort_order' => $g['sort_order']]
            );
        }

        // 4. Groups / Kelompok / TPQ (9 official groups)
        $groupsData = [
            ['name' => 'Ampenan', 'code' => 'AMP'],
            ['name' => 'Mataram', 'code' => 'MTR'],
            ['name' => 'Sandik', 'code' => 'SDK'],
            ['name' => 'Pagutan', 'code' => 'PGT'],
            ['name' => 'Prumnas', 'code' => 'PRM'],
            ['name' => 'Cakranegara', 'code' => 'CKR'],
            ['name' => 'Narmada', 'code' => 'NRM'],
            ['name' => 'Selong', 'code' => 'SLG'],
            ['name' => 'Praya', 'code' => 'PRY'],
        ];

        $groupsMap = [];
        foreach ($groupsData as $grp) {
            $groupsMap[$grp['name']] = Group::firstOrCreate(
                ['event_id' => $event->id, 'name' => $grp['name']],
                ['code' => $grp['code']]
            );
        }

        // 5. Load Official 2026 Competitions, Judges & Participants (14 Competitions, No Yel-Yel)
        $datasetPath = database_path('seeders/data/official_2026_competitions.json');
        $competitionsConfig = json_decode(file_get_contents($datasetPath), true);

        $judgeCounter = 1;
        $participantCounter = Participant::where('event_id', $event->id)->count() + 1;

        foreach ($competitionsConfig as $cData) {
            $comp = Competition::firstOrCreate(
                ['event_id' => $event->id, 'name' => $cData['name']],
                [
                    'format' => $cData['format'],
                    'eligibility_rule' => $cData['eligibility_rule'],
                    'status' => 'ready',
                    'is_locked' => false,
                ]
            );

            // Sync eligible grade levels
            $gradeIds = [];
            foreach ($cData['grades'] as $gCode) {
                if (isset($grades[$gCode])) {
                    $gradeIds[] = $grades[$gCode]->id;
                }
            }
            $comp->gradeLevels()->sync($gradeIds);

            // Rubric v1
            $rubric = Rubric::firstOrCreate(
                ['competition_id' => $comp->id, 'version' => 1],
                ['status' => 'active']
            );

            // Quick Rating Options: Baik (100), Cukup (75), Kurang (50) — judges can also input 50-100 manually
            $options = [
                ['label' => 'Baik', 'numeric_value' => 100.0, 'sort_order' => 1],
                ['label' => 'Cukup', 'numeric_value' => 75.0, 'sort_order' => 2],
                ['label' => 'Kurang', 'numeric_value' => 50.0, 'sort_order' => 3],
            ];
            foreach ($options as $opt) {
                RatingOption::firstOrCreate(
                    ['rubric_id' => $rubric->id, 'label' => $opt['label']],
                    ['numeric_value' => $opt['numeric_value'], 'sort_order' => $opt['sort_order']]
                );
            }

            // Criteria
            RubricCriterion::where('rubric_id', $rubric->id)->delete();
            $sort = 1;
            foreach ($cData['criteria'] as $crit) {
                RubricCriterion::create([
                    'rubric_id' => $rubric->id,
                    'title' => $crit['title'],
                    'weight_pct' => $crit['weight_pct'],
                    'sort_order' => $sort++,
                    'required' => true,
                ]);
            }

            // Standard Awards
            $awards = [
                ['rank_position' => 1, 'rank_label' => 'Juara 1', 'points_for_group' => 10],
                ['rank_position' => 2, 'rank_label' => 'Juara 2', 'points_for_group' => 7],
                ['rank_position' => 3, 'rank_label' => 'Juara 3', 'points_for_group' => 5],
                ['rank_position' => 4, 'rank_label' => 'Juara Harapan 1', 'points_for_group' => 3],
                ['rank_position' => 5, 'rank_label' => 'Juara Harapan 2', 'points_for_group' => 2],
                ['rank_position' => 6, 'rank_label' => 'Juara Harapan 3', 'points_for_group' => 1],
            ];
            foreach ($awards as $aw) {
                CompetitionAward::firstOrCreate(
                    ['competition_id' => $comp->id, 'rank_position' => $aw['rank_position']],
                    ['rank_label' => $aw['rank_label'], 'points_for_group' => $aw['points_for_group']]
                );
            }

            // Create & Assign 3 Judges per Competition
            foreach ($cData['judges'] as $judgeName) {
                $phone = sprintf('08520026%04d', $judgeCounter++);
                $judge = User::firstOrCreate(
                    ['phone' => $phone],
                    [
                        'name' => $judgeName,
                        'email' => "juri{$phone}@faslombok2026.id",
                        'password' => $defaultPasswordHash,
                        'role' => 'judge',
                        'status' => 'active',
                    ]
                );

                EventUser::firstOrCreate(
                    ['event_id' => $event->id, 'user_id' => $judge->id],
                    ['event_role' => 'judge']
                );

                JudgeAssignment::firstOrCreate(
                    [
                        'event_id' => $event->id,
                        'user_id' => $judge->id,
                        'competition_id' => $comp->id,
                    ],
                    [
                        'can_view_participants' => true,
                        'can_view_scores' => true,
                        'can_view_peer_scores' => true,
                        'can_score' => true,
                        'active' => true,
                        'required_for_completion' => true,
                    ]
                );
            }

            // Create Participants & Competition Entries
            $queueNumber = Entry::where('competition_id', $comp->id)->count() + 1;
            foreach ($cData['participants'] as $pData) {
                $group = $groupsMap[$pData['group']] ?? Group::firstOrCreate(
                    ['event_id' => $event->id, 'name' => $pData['group']],
                    ['code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $pData['group']), 0, 4))]
                );
                $grade = $grades[$pData['grade']] ?? null;

                $participant = Participant::where('event_id', $event->id)
                    ->where('group_id', $group->id)
                    ->where('name', $pData['name'])
                    ->first();

                if (!$participant) {
                    do {
                        $code = sprintf('FAS-%s-%04d', $event->year, $participantCounter++);
                    } while (Participant::where('event_id', $event->id)->where('participant_code', $code)->exists());

                    $participant = Participant::create([
                        'event_id' => $event->id,
                        'group_id' => $group->id,
                        'grade_id' => $grade?->id,
                        'participant_code' => $code,
                        'name' => $pData['name'],
                        'gender' => $pData['gender'] ?? 'L',
                        'active' => true,
                    ]);

                    $qrService->getOrCreateForParticipant($participant);
                }

                Entry::firstOrCreate(
                    ['competition_id' => $comp->id, 'participant_id' => $participant->id],
                    [
                        'event_id' => $event->id,
                        'queue_number' => $queueNumber++,
                        'status' => 'registered',
                    ]
                );
            }
        }
    }
}
