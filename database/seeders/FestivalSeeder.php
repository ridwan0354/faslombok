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
use App\Models\Team;
use App\Models\TeamMember;
use App\Models\User;
use App\Services\QrService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class FestivalSeeder extends Seeder
{
    public function run(): void
    {
        $qrService = app(QrService::class);

        // 1. Create Super/Master Admin
        $master = User::firstOrCreate(
            ['phone' => '081234567890'],
            [
                'name' => 'Master Panitia FAS 2026',
                'email' => 'admin@faslombok2026.id',
                'password' => Hash::make('password123'),
                'role' => 'master',
                'status' => 'active',
            ]
        );

        // 2. Create Judges
        $judgeAhmad = User::firstOrCreate(
            ['phone' => '081111111111'],
            [
                'name' => 'Ustadz Ahmad Fauzan, S.Pd.I',
                'email' => 'ahmad@faslombok2026.id',
                'password' => Hash::make('password123'),
                'role' => 'judge',
                'status' => 'active',
            ]
        );

        $judgeFauzi = User::firstOrCreate(
            ['phone' => '082222222222'],
            [
                'name' => 'Ustadz M. Fauzi, M.Ag',
                'email' => 'fauzi@faslombok2026.id',
                'password' => Hash::make('password123'),
                'role' => 'judge',
                'status' => 'active',
            ]
        );

        $judgeSiti = User::firstOrCreate(
            ['phone' => '083333333333'],
            [
                'name' => 'Ustadzah Siti Aminah, S.Pd',
                'email' => 'siti@faslombok2026.id',
                'password' => Hash::make('password123'),
                'role' => 'judge',
                'status' => 'active',
            ]
        );

        $judgeRidwan = User::firstOrCreate(
            ['phone' => '084444444444'],
            [
                'name' => 'Ustadz Ridwan Hakim, M.Pd',
                'email' => 'ridwan@faslombok2026.id',
                'password' => Hash::make('password123'),
                'role' => 'judge',
                'status' => 'active',
            ]
        );

        // 3. Create Event
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
                    'theme' => 'emerald',
                    'location' => 'Lombok Convention Center, Mataram',
                ]
            ]
        );

        // Attach master to event
        EventUser::firstOrCreate(
            ['event_id' => $event->id, 'user_id' => $master->id],
            ['event_role' => 'master']
        );

        foreach ([$judgeAhmad, $judgeFauzi, $judgeSiti, $judgeRidwan] as $j) {
            EventUser::firstOrCreate(
                ['event_id' => $event->id, 'user_id' => $j->id],
                ['event_role' => 'judge']
            );
        }

        // 4. Create Grade Levels (Jenjang kelas satuan/murni agar mudah difilter per peserta)
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

        // 5. Create Groups (Kelompok / TPQ)
        $groupsData = [
            ['name' => 'TPQ Al-Ikhlas Ampenan', 'code' => 'AMP-01'],
            ['name' => 'TPQ Al-Falah Mataram', 'code' => 'MTR-02'],
            ['name' => 'TPQ Nurul Huda Cakranegara', 'code' => 'CKR-03'],
            ['name' => 'TPQ Darul Ulum Gerung', 'code' => 'GRG-04'],
            ['name' => 'TPQ Babul Jannah Praya', 'code' => 'PRY-05'],
        ];

        $groups = [];
        foreach ($groupsData as $grp) {
            $groups[] = Group::firstOrCreate(
                ['event_id' => $event->id, 'name' => $grp['name']],
                ['code' => $grp['code']]
            );
        }

        // 6. Define 12 Competitions with initial Rubrics & Eligible Grades
        $allSd = ['KELAS_1', 'KELAS_2', 'KELAS_3', 'KELAS_4', 'KELAS_5', 'KELAS_6'];
        $competitionsConfig = [
            [
                'name' => 'Hafalan Dalil',
                'format' => 'individual',
                'eligibility_rule' => 'Kelas 5–6 SD/MI',
                'grades' => ['KELAS_5', 'KELAS_6'],
                'criteria' => [
                    ['title' => 'Kelancaran Hafalan', 'weight_pct' => 40.0],
                    ['title' => 'Ketepatan Makna / Terjemah', 'weight_pct' => 30.0],
                    ['title' => 'Tajwid & Lafaz', 'weight_pct' => 20.0],
                    ['title' => 'Adab & Sikap', 'weight_pct' => 10.0],
                ],
            ],
            [
                'name' => 'Yel-Yel 29 Karakter Luhur',
                'format' => 'team',
                'eligibility_rule' => 'Kelas 1–6 SD/MI',
                'grades' => $allSd,
                'criteria' => [
                    ['title' => 'Tema / Pesan 29 Karakter Luhur', 'weight_pct' => 35.0],
                    ['title' => 'Kreativitas Gerak & Formasi', 'weight_pct' => 25.0],
                    ['title' => 'Kekompakan & Semangat', 'weight_pct' => 20.0],
                    ['title' => 'Ekspresi & Vokal', 'weight_pct' => 10.0],
                    ['title' => 'Kostum & Properti', 'weight_pct' => 10.0],
                ],
            ],
            [
                'name' => 'Adzan',
                'format' => 'individual',
                'eligibility_rule' => 'Kelas 1–6 SD/MI',
                'grades' => $allSd,
                'criteria' => [
                    ['title' => 'Makhraj & Tajwid', 'weight_pct' => 25.0],
                    ['title' => 'Ketepatan Lafaz', 'weight_pct' => 25.0],
                    ['title' => 'Irama & Suara', 'weight_pct' => 20.0],
                    ['title' => 'Kelancaran', 'weight_pct' => 15.0],
                    ['title' => 'Adab', 'weight_pct' => 15.0],
                ],
            ],
            [
                'name' => 'Baca Tilawati',
                'format' => 'individual',
                'eligibility_rule' => 'Kelas 1–4 SD/MI',
                'grades' => ['KELAS_1', 'KELAS_2', 'KELAS_3', 'KELAS_4'],
                'criteria' => [
                    ['title' => 'Fashohah & Kebenaran Bacaan', 'weight_pct' => 40.0],
                    ['title' => 'Kaidah Tajwid', 'weight_pct' => 30.0],
                    ['title' => 'Irama / Lagu Tilawati', 'weight_pct' => 20.0],
                    ['title' => 'Adab', 'weight_pct' => 10.0],
                ],
            ],
            [
                'name' => 'Hafalan Doa',
                'format' => 'individual',
                'eligibility_rule' => 'TK A–B, Kelas 1–2 SD/MI',
                'grades' => ['TK_A', 'TK_B', 'KELAS_1', 'KELAS_2'],
                'criteria' => [
                    ['title' => 'Hafalan Lafaz Doa', 'weight_pct' => 40.0],
                    ['title' => 'Terjemah Doa', 'weight_pct' => 30.0],
                    ['title' => 'Makhraj & Artikulasi', 'weight_pct' => 20.0],
                    ['title' => 'Adab', 'weight_pct' => 10.0],
                ],
            ],
            [
                'name' => 'Mewarnai',
                'format' => 'individual',
                'eligibility_rule' => 'PAUD, TK A–B',
                'grades' => ['PAUD', 'TK_A', 'TK_B'],
                'criteria' => [
                    ['title' => 'Kerapian', 'weight_pct' => 20.0],
                    ['title' => 'Harmoni Warna', 'weight_pct' => 25.0],
                    ['title' => 'Kreativitas', 'weight_pct' => 25.0],
                    ['title' => 'Komposisi Bidang', 'weight_pct' => 20.0],
                    ['title' => 'Kebersihan', 'weight_pct' => 10.0],
                ],
            ],
            [
                'name' => 'Kaligrafi',
                'format' => 'individual',
                'eligibility_rule' => 'Kelas 1–6 SD/MI',
                'grades' => $allSd,
                'criteria' => [
                    ['title' => 'Kaidah Khat / Bentuk Huruf', 'weight_pct' => 40.0],
                    ['title' => 'Keindahan & Komposisi', 'weight_pct' => 30.0],
                    ['title' => 'Kreativitas Hiasan', 'weight_pct' => 20.0],
                    ['title' => 'Kebersihan & Kerapian', 'weight_pct' => 10.0],
                ],
            ],
            [
                'name' => 'Baca Al-Qur’an beserta Tajwid',
                'format' => 'individual',
                'eligibility_rule' => 'Kelas 5–6 SD/MI',
                'grades' => ['KELAS_5', 'KELAS_6'],
                'criteria' => [
                    ['title' => 'Penerapan Ilmu Tajwid', 'weight_pct' => 40.0],
                    ['title' => 'Fashohah & Makharijul Huruf', 'weight_pct' => 30.0],
                    ['title' => 'Irama & Suara', 'weight_pct' => 20.0],
                    ['title' => 'Adab', 'weight_pct' => 10.0],
                ],
            ],
            [
                'name' => 'Dai Caberawit',
                'format' => 'individual',
                'eligibility_rule' => 'Kelas 1–6 SD/MI',
                'grades' => $allSd,
                'criteria' => [
                    ['title' => 'Kesesuaian Materi & Dalil', 'weight_pct' => 35.0],
                    ['title' => 'Retorika & Gaya Bahasa', 'weight_pct' => 25.0],
                    ['title' => 'Vokal, Intonasi & Artikulasi', 'weight_pct' => 20.0],
                    ['title' => 'Penguasaan Panggung & Ekspresi', 'weight_pct' => 10.0],
                    ['title' => 'Adab', 'weight_pct' => 10.0],
                ],
            ],
            [
                'name' => 'Hafalan Surat Pendek',
                'format' => 'individual',
                'eligibility_rule' => 'Kelas 1–4 SD/MI',
                'grades' => ['KELAS_1', 'KELAS_2', 'KELAS_3', 'KELAS_4'],
                'criteria' => [
                    ['title' => 'Ketepatan Hafalan', 'weight_pct' => 35.0],
                    ['title' => 'Tajwid & Makharijul Huruf', 'weight_pct' => 30.0],
                    ['title' => 'Kefasihan & Irama', 'weight_pct' => 20.0],
                    ['title' => 'Adab', 'weight_pct' => 15.0],
                ],
            ],
            [
                'name' => 'Hafalan Asmaul Husna',
                'format' => 'individual',
                'eligibility_rule' => 'Kelas 4–6 SD/MI',
                'grades' => ['KELAS_4', 'KELAS_5', 'KELAS_6'],
                'criteria' => [
                    ['title' => 'Kelancaran 99 Nama', 'weight_pct' => 40.0],
                    ['title' => 'Ketepatan Nada & Irama', 'weight_pct' => 25.0],
                    ['title' => 'Makhraj & Pelafalan', 'weight_pct' => 25.0],
                    ['title' => 'Adab', 'weight_pct' => 10.0],
                ],
            ],
            [
                'name' => 'Baca Tulis Pegon',
                'format' => 'individual',
                'eligibility_rule' => 'Kelas 1–6 SD/MI',
                'grades' => $allSd,
                'criteria' => [
                    ['title' => 'Ketepatan Membaca Pegon', 'weight_pct' => 40.0],
                    ['title' => 'Ketepatan Menulis Pegon', 'weight_pct' => 35.0],
                    ['title' => 'Kecepatan & Kelancaran', 'weight_pct' => 15.0],
                    ['title' => 'Kerapian Tulisan', 'weight_pct' => 10.0],
                ],
            ],
        ];

        $createdComps = [];
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

            // Create Rubric
            $rubric = Rubric::firstOrCreate(
                ['competition_id' => $comp->id, 'version' => 1],
                ['status' => 'active']
            );

            // Rating Options: Baik (100), Cukup (75), Kurang (50)
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
            $sort = 1;
            foreach ($cData['criteria'] as $crit) {
                RubricCriterion::firstOrCreate(
                    ['rubric_id' => $rubric->id, 'title' => $crit['title']],
                    [
                        'weight_pct' => $crit['weight_pct'],
                        'sort_order' => $sort++,
                        'required' => true,
                    ]
                );
            }

            // Standard Awards for each competition
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
                    [
                        'rank_label' => $aw['rank_label'],
                        'points_for_group' => $aw['points_for_group'],
                    ]
                );
            }

            $createdComps[$cData['name']] = $comp;
        }

        // 7. Assign Judges to Competitions
        // Judge Ahmad: Adzan, Dai Caberawit
        // Judge Fauzi: Adzan, Hafalan Surat Pendek
        // Judge Siti: Mewarnai, Kaligrafi
        // Judge Ridwan: Yel-Yel 29 Karakter Luhur

        $assignments = [
            // Adzan has 2 required judges: Ahmad & Fauzi
            ['judge' => $judgeAhmad, 'comp' => $createdComps['Adzan'], 'peer' => true, 'req' => true],
            ['judge' => $judgeFauzi, 'comp' => $createdComps['Adzan'], 'peer' => true, 'req' => true],

            // Dai Caberawit: Ahmad
            ['judge' => $judgeAhmad, 'comp' => $createdComps['Dai Caberawit'], 'peer' => false, 'req' => true],

            // Hafalan Surat Pendek: Fauzi
            ['judge' => $judgeFauzi, 'comp' => $createdComps['Hafalan Surat Pendek'], 'peer' => false, 'req' => true],

            // Mewarnai: Siti
            ['judge' => $judgeSiti, 'comp' => $createdComps['Mewarnai'], 'peer' => false, 'req' => true],

            // Kaligrafi: Siti
            ['judge' => $judgeSiti, 'comp' => $createdComps['Kaligrafi'], 'peer' => false, 'req' => true],

            // Yel-Yel 29 Karakter Luhur: Ridwan
            ['judge' => $judgeRidwan, 'comp' => $createdComps['Yel-Yel 29 Karakter Luhur'], 'peer' => false, 'req' => true],
        ];

        foreach ($assignments as $as) {
            JudgeAssignment::firstOrCreate(
                [
                    'event_id' => $event->id,
                    'user_id' => $as['judge']->id,
                    'competition_id' => $as['comp']->id,
                ],
                [
                    'can_view_participants' => true,
                    'can_view_scores' => true,
                    'can_view_peer_scores' => $as['peer'],
                    'can_score' => true,
                    'active' => true,
                    'required_for_completion' => $as['req'],
                ]
            );
        }

        // 8. Sample Participants
        $participantsData = [
            [
                'name' => 'Muhammad Rayyan Al-Farisi',
                'group' => $groups[0], // Ampenan
                'grade' => $grades['KELAS_5'],
                'gender' => 'L',
                'comps' => ['Adzan', 'Dai Caberawit'],
            ],
            [
                'name' => 'Ahmad Bilal Al-Habib',
                'group' => $groups[1], // Mataram
                'grade' => $grades['KELAS_5'],
                'gender' => 'L',
                'comps' => ['Adzan'],
            ],
            [
                'name' => 'Fathir Azzam Robbani',
                'group' => $groups[2], // Cakranegara
                'grade' => $grades['KELAS_5'],
                'gender' => 'L',
                'comps' => ['Adzan'],
            ],
            [
                'name' => 'Aisyah Putri Azzahra',
                'group' => $groups[0], // Ampenan
                'grade' => $grades['KELAS_3'],
                'gender' => 'P',
                'comps' => ['Hafalan Surat Pendek'],
            ],
            [
                'name' => 'Fatimah Khairunnisa',
                'group' => $groups[1], // Mataram
                'grade' => $grades['KELAS_3'],
                'gender' => 'P',
                'comps' => ['Hafalan Surat Pendek'],
            ],
            [
                'name' => 'Khaira Lubna Salsabila',
                'group' => $groups[3], // Gerung
                'grade' => $grades['TK_B'],
                'gender' => 'P',
                'comps' => ['Mewarnai'],
            ],
            [
                'name' => 'Syamil Al-Banna',
                'group' => $groups[4], // Praya
                'grade' => $grades['KELAS_4'],
                'gender' => 'L',
                'comps' => ['Kaligrafi'],
            ],
        ];

        $pCount = 1;
        $createdParticipants = [];
        foreach ($participantsData as $pData) {
            $code = sprintf('FAS-%s-%04d', $event->year, $pCount++);
            $p = Participant::firstOrCreate(
                ['event_id' => $event->id, 'name' => $pData['name']],
                [
                    'group_id' => $pData['group']->id,
                    'grade_id' => $pData['grade']->id,
                    'participant_code' => $code,
                    'gender' => $pData['gender'],
                    'active' => true,
                ]
            );

            // Generate QR Token
            $qrService->getOrCreateForParticipant($p);

            // Create individual entries
            foreach ($pData['comps'] as $cName) {
                if (isset($createdComps[$cName])) {
                    $comp = $createdComps[$cName];
                    $queue = Entry::where('competition_id', $comp->id)->count() + 1;
                    Entry::firstOrCreate(
                        ['competition_id' => $comp->id, 'participant_id' => $p->id],
                        [
                            'event_id' => $event->id,
                            'queue_number' => $queue,
                            'status' => 'registered',
                        ]
                    );
                }
            }

            $createdParticipants[] = $p;
        }

        // 9. Sample Team for Yel-Yel 29 Karakter Luhur
        $teamComp = $createdComps['Yel-Yel 29 Karakter Luhur'];
        $team = Team::firstOrCreate(
            ['event_id' => $event->id, 'competition_id' => $teamComp->id, 'team_code' => 'TIM-YEL-01'],
            [
                'group_id' => $groups[0]->id,
                'name' => 'Laskar 29 Ampenan Juara',
            ]
        );

        // Add some members to team
        TeamMember::firstOrCreate(
            ['team_id' => $team->id, 'participant_id' => $createdParticipants[0]->id],
            ['membership_status' => 'active']
        );

        // Generate QR for team
        $qrService->getOrCreateForTeam($team);

        // Create Team Entry
        Entry::firstOrCreate(
            ['competition_id' => $teamComp->id, 'team_id' => $team->id],
            [
                'event_id' => $event->id,
                'queue_number' => 1,
                'status' => 'registered',
            ]
        );
    }
}
