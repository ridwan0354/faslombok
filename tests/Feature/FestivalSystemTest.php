<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Event;
use App\Models\Group;
use App\Models\JudgeAssignment;
use App\Models\Participant;
use App\Models\Rubric;
use App\Models\Team;
use App\Models\User;
use App\Services\ImportExportService;
use App\Services\QrService;
use App\Services\RankingService;
use App\Services\ScoringService;
use Database\Seeders\FestivalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FestivalSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FestivalSeeder::class);
    }

    /**
     * UAT 1: Juri dengan izin Adzan memindai QR peserta Adzan => dapat form Adzan saja.
     */
    public function test_judge_with_adzan_permission_can_scan_and_resolve_adzan_entry(): void
    {
        $judgeAdzan = User::where('phone', '085200260025')->first(); // Bp. Munirullah (Sandik)
        $adzanComp = Competition::where('name', 'Adzan (Kls 1–6)')->first();

        // Peserta Adzan: Muhammad Al Fatih
        $participant = Participant::where('name', 'Muhammad Al Fatih')->first();
        $qrToken = app(QrService::class)->getOrCreateForParticipant($participant);

        $response = $this->actingAs($judgeAdzan)
            ->postJson(route('judge.resolve-qr'), [
                'token' => $qrToken->token_plain,
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('success', $data['status']);
        $this->assertEquals('Muhammad Al Fatih', $data['entity']['name']);
        $this->assertNotEmpty($data['entries']);

        $compNames = collect($data['entries'])->pluck('competition_name')->toArray();
        $this->assertContains('Adzan (Kls 1–6)', $compNames);
    }

    /**
     * UAT 2: Juri Adzan memindai QR peserta yang hanya ikut Kaligrafi => tidak bisa menilai & tidak bocor data sensitif.
     */
    public function test_judge_adzan_scanning_participant_who_only_joined_kaligrafi_is_safely_rejected(): void
    {
        $judgeAdzan = User::where('phone', '085200260025')->first();

        // Alesha Azzahra Alfathunnisa only joined Kaligrafi
        $participant = Participant::where('name', 'Alesha Azzahra Alfathunnisa')->first();
        $qrToken = app(QrService::class)->getOrCreateForParticipant($participant);

        $response = $this->actingAs($judgeAdzan)
            ->postJson(route('judge.resolve-qr'), [
                'token' => $qrToken->token_plain,
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('unauthorized', $data['status']);
        $this->assertNull($data['entity']); // Privacy isolation: no participant data leaked
        $this->assertEmpty($data['entries']);
    }

    /**
     * UAT 3: Peserta ikut Adzan dan Da'i Cilik => QR sama; juri hanya melihat lomba yang ditugaskan.
     */
    public function test_participant_in_multiple_competitions_only_exposes_authorized_competitions_to_judge(): void
    {
        $judgeAdzan = User::where('phone', '085200260025')->first();
        $daiComp = Competition::where('name', "Da'i Cilik (Kls 1–6)")->first();

        // Add Da'i Cilik entry to Muhammad Al Fatih
        $participant = Participant::where('name', 'Muhammad Al Fatih')->first();
        Entry::create([
            'event_id' => $participant->event_id,
            'competition_id' => $daiComp->id,
            'participant_id' => $participant->id,
            'queue_number' => 99,
            'status' => 'registered',
        ]);
        $qrToken = app(QrService::class)->getOrCreateForParticipant($participant);

        $response = $this->actingAs($judgeAdzan)
            ->postJson(route('judge.resolve-qr'), [
                'token' => $qrToken->token_plain,
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('success', $data['status']);
        $compNames = collect($data['entries'])->pluck('competition_name')->toArray();

        $this->assertContains('Adzan (Kls 1–6)', $compNames);
        $this->assertNotContains("Da'i Cilik (Kls 1–6)", $compNames);
    }

    /**
     * UAT 4: Juri akses view-only => endpoint score/write ditolak.
     */
    public function test_view_only_judge_cannot_submit_score(): void
    {
        $judgeAdzan = User::where('phone', '085200260025')->first();
        $adzanComp = Competition::where('name', 'Adzan (Kls 1–6)')->first();

        $assignment = JudgeAssignment::where('user_id', $judgeAdzan->id)
            ->where('competition_id', $adzanComp->id)
            ->first();
        $assignment->update(['can_score' => false]);

        $entry = Entry::where('competition_id', $adzanComp->id)->first();

        $response = $this->actingAs($judgeAdzan)
            ->post(route('judge.scoring.submit', $entry->id), [
                'scores' => [],
            ]);

        $response->assertStatus(403);
    }

    /**
     * UAT 5: Rubrik dengan bobot tidak 100% (misal 95%) ditolak oleh sistem.
     */
    public function test_rubric_with_invalid_weight_is_rejected(): void
    {
        $master = User::where('role', 'master')->first();
        $adzanComp = Competition::where('name', 'Adzan (Kls 1–6)')->first();

        $response = $this->actingAs($master)
            ->post(route('master.competitions.rubric.update', $adzanComp->id), [
                'criteria' => [
                    ['title' => 'Kriteria A', 'weight_pct' => 50],
                    ['title' => 'Kriteria B', 'weight_pct' => 45], // total 95%
                ],
            ]);

        $response->assertSessionHas('error');
    }

    /**
     * UAT 6: Tiga juri menilai peserta yang sama => masing-masing tersimpan independen (termasuk nilai manual 50-100); nilai akhir terakumulasi.
     */
    public function test_two_judges_score_same_entry_independently_and_aggregate(): void
    {
        $judge1 = User::where('phone', '085200260025')->first();
        $judge2 = User::where('phone', '085200260026')->first();
        $judge3 = User::where('phone', '085200260027')->first();
        $adzanComp = Competition::where('name', 'Adzan (Kls 1–6)')->first();
        $entry = Entry::where('competition_id', $adzanComp->id)->first();

        $rubric = $adzanComp->activeRubric;
        $criteria = $rubric->criteria;
        $ratingBaik = $rubric->ratingOptions->firstWhere('label', 'Baik'); // 100
        $ratingCukup = $rubric->ratingOptions->firstWhere('label', 'Cukup'); // 75

        $scoringService = app(ScoringService::class);
        $as1 = JudgeAssignment::where('user_id', $judge1->id)->where('competition_id', $adzanComp->id)->first();
        $as2 = JudgeAssignment::where('user_id', $judge2->id)->where('competition_id', $adzanComp->id)->first();
        $as3 = JudgeAssignment::where('user_id', $judge3->id)->where('competition_id', $adzanComp->id)->first();

        // Juri 1 gives all Baik (100) -> 100
        $scores1 = [];
        foreach ($criteria as $c) {
            $scores1[$c->id] = ['rating_option_id' => $ratingBaik->id];
        }
        $sub1 = $scoringService->submitScore($entry, $as1, $scores1, 'Penilaian Juri 1', $judge1);
        $this->assertEquals(100.0, (float) $sub1->score_total);

        // Before all 3 judges submit -> incomplete
        $finalStatus1 = $scoringService->getFinalScore($entry);
        $this->assertFalse($finalStatus1['is_complete']);
        $this->assertEquals(3, $finalStatus1['required_judges']);
        $this->assertEquals(1, $finalStatus1['submitted_judges']);

        // Juri 2 gives all Cukup (75) -> 75
        $scores2 = [];
        foreach ($criteria as $c) {
            $scores2[$c->id] = ['rating_option_id' => $ratingCukup->id];
        }
        $sub2 = $scoringService->submitScore($entry, $as2, $scores2, 'Penilaian Juri 2', $judge2);
        $this->assertEquals(75.0, (float) $sub2->score_total);

        // Juri 3 inputs exact numeric value 88 (50-100 range) -> 88
        $scores3 = [];
        foreach ($criteria as $c) {
            $scores3[$c->id] = ['rating_option_id' => '', 'numeric_value' => 88];
        }
        $sub3 = $scoringService->submitScore($entry, $as3, $scores3, 'Penilaian Juri 3', $judge3);
        $this->assertEquals(88.0, (float) $sub3->score_total);

        // All 3 required judges done -> 100 + 75 + 88 = 263
        $finalStatus3 = $scoringService->getFinalScore($entry);
        $this->assertTrue($finalStatus3['is_complete']);
        $this->assertEquals(3, $finalStatus3['submitted_judges']);
        $this->assertEquals(263.0, (float) $finalStatus3['final_score']);
    }

    /**
     * UAT 7: Perubahan nilai sebelum lock terekam; setelah lock ditolak hingga Master membuka.
     */
    public function test_score_updates_locked_when_competition_is_locked(): void
    {
        $judgeAdzan = User::where('phone', '085200260025')->first();
        $adzanComp = Competition::where('name', 'Adzan (Kls 1–6)')->first();
        $entry = Entry::where('competition_id', $adzanComp->id)->first();

        $adzanComp->update(['is_locked' => true, 'locked_at' => now()]);

        $response = $this->actingAs($judgeAdzan)
            ->post(route('judge.scoring.submit', $entry->id), [
                'scores' => [],
            ]);

        $response->assertSessionHas('error');
    }

    /**
     * UAT 8: QR beregu (jika Master membuat lomba tim baru) memunculkan identitas tim.
     */
    public function test_team_qr_resolves_team_entry(): void
    {
        $event = Event::first();
        $group = \App\Models\Group::first();
        $judgeAdzan = User::where('phone', '085200260025')->first();

        $teamComp = Competition::create([
            'event_id' => $event->id,
            'name' => 'Lomba Tim Khusus Uji Coba',
            'format' => 'team',
            'eligibility_rule' => 'Kelas 1–6',
            'status' => 'ready',
        ]);

        JudgeAssignment::create([
            'event_id' => $event->id,
            'user_id' => $judgeAdzan->id,
            'competition_id' => $teamComp->id,
            'can_score' => true,
            'active' => true,
            'required_for_completion' => true,
        ]);

        $team = Team::create([
            'event_id' => $event->id,
            'competition_id' => $teamComp->id,
            'group_id' => $group->id,
            'team_code' => 'TIM-TEST-01',
            'name' => 'Tim Uji Coba QR',
        ]);

        Entry::create([
            'event_id' => $event->id,
            'competition_id' => $teamComp->id,
            'team_id' => $team->id,
            'queue_number' => 1,
            'status' => 'registered',
        ]);

        $qrToken = app(QrService::class)->getOrCreateForTeam($team);

        $response = $this->actingAs($judgeAdzan)
            ->postJson(route('judge.resolve-qr'), [
                'token' => $qrToken->token_plain,
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('success', $data['status']);
        $this->assertEquals('team', $data['entity_type']);
        $this->assertEquals('Tim Uji Coba QR', $data['entity']['name']);
    }

    /**
     * UAT 9: Hasil publik belum tampil sebelum publish; setelah publish muncul.
     */
    public function test_public_results_only_available_after_publish(): void
    {
        $adzanComp = Competition::where('name', 'Adzan (Kls 1–6)')->first();
        $master = User::where('role', 'master')->first();

        $resBefore = $this->get(route('pengumuman.detail', $adzanComp->id));
        $resBefore->assertStatus(404);

        app(RankingService::class)->publish($adzanComp, $master);

        $resAfter = $this->get(route('pengumuman.detail', $adzanComp->id));
        $resAfter->assertStatus(200);
        $resAfter->assertSee($adzanComp->name);
    }

    /**
     * UAT 10: Impor CSV peserta berhasil membuat peserta dan QR token otomatis.
     */
    public function test_csv_import_creates_participants_and_qr(): void
    {
        $event = Event::first();
        $importService = app(ImportExportService::class);

        $csvContent = "nama,kelompok,kelas,jenis_kelamin,lomba\n"
                    . "Zaidan Al-Ayyubi,Cakra,KELAS_5,L,Adzan (Kls 1–6)\n"
                    . "Maryam Sholihah,Perumnas,KELAS_3,P,Baca Tilawati Jilid 4–6 (Kls 3–4)\n";

        $rows = $importService->parseCsv($csvContent);
        $result = $importService->importParticipants($event, $rows);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['created']);

        $zaidan = Participant::where('name', 'Zaidan Al-Ayyubi')->first();
        $this->assertNotNull($zaidan);
        $this->assertEquals('Cakranegara', $zaidan->group->name);
        $this->assertNotNull($zaidan->qrToken);
        $this->assertNotEmpty($zaidan->entries);
    }

    /**
     * Test Master can access and render print QR badges page without error
     */
    public function test_master_can_access_print_qr_badges(): void
    {
        $master = User::where('role', 'master')->first();

        $response = $this->actingAs($master)
            ->get(route('master.print.qr'));

        $response->assertStatus(200);
        $response->assertSee('Cetak Kartu & Badge QR Peserta');
        $response->assertSee('--card-w: 90mm;', false);
        $response->assertSee('--card-h: 55mm;', false);
        $response->assertSee('<svg', false);
    }

    /**
     * Test print QR badges supports filtering by group and selecting name tag size preset
     */
    public function test_master_can_filter_print_qr_badges_by_group_and_size(): void
    {
        $master = User::where('role', 'master')->first();
        $event = Event::first();
        $groups = Group::where('event_id', $event->id)->take(2)->get();
        $groupA = $groups[0];
        $groupB = $groups[1];

        $participantA = Participant::where('group_id', $groupA->id)->first();
        $participantB = Participant::where('group_id', $groupB->id)->first();
        $comp = Competition::first();

        $teamA = Team::create([
            'event_id' => $event->id,
            'competition_id' => $comp->id,
            'group_id' => $groupA->id,
            'team_code' => 'TIM-A-01',
            'name' => 'Regu Khusus Grup A',
        ]);
        $teamB = Team::create([
            'event_id' => $event->id,
            'competition_id' => $comp->id,
            'group_id' => $groupB->id,
            'team_code' => 'TIM-B-01',
            'name' => 'Regu Khusus Grup B',
        ]);

        $response = $this->actingAs($master)
            ->get(route('master.print.qr', [
                'group_id' => $groupA->id,
                'size' => '95x60',
            ]));

        $response->assertStatus(200);
        $response->assertSee('--card-w: 95mm;', false);
        $response->assertSee('--card-h: 60mm;', false);
        $response->assertSee($participantA->participant_code);
        $response->assertSee($teamA->team_code);
        $response->assertDontSee($participantB->participant_code);
        $response->assertDontSee($teamB->team_code);
    }

    /**
     * Test Master can edit participant data
     */
    public function test_master_can_update_participant(): void
    {
        $master = User::where('role', 'master')->first();
        $participant = Participant::where('name', 'Muhammad Al Fatih')->first();
        $group = $participant->group;

        $response = $this->actingAs($master)
            ->put(route('master.participants.update', $participant->id), [
                'name' => 'Muhammad Al Fatih (Updated)',
                'group_id' => $group->id,
                'gender' => 'L',
                'active' => 1,
            ]);

        $response->assertRedirect(route('master.participants'));
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'name' => 'Muhammad Al Fatih (Updated)',
        ]);
    }

    /**
     * Test Master can view score monitoring with criteria details
     */
    public function test_master_can_view_scores_monitoring_with_criteria_breakdown(): void
    {
        $master = User::where('role', 'master')->first();
        $kaligrafi = Competition::where('name', 'Kaligrafi Alhamdulillah (Kls 1–6)')->first();

        $response = $this->actingAs($master)
            ->get(route('master.scores', ['competition_id' => $kaligrafi->id]));

        $response->assertStatus(200);
        $response->assertSee('Kriteria Penilaian: Kaligrafi Alhamdulillah (Kls 1–6)');
        $response->assertSee('Kesesuaian Tema');
        $response->assertSee('Kreativitas');
    }

    /**
     * Test Master can create, update, and delete Kelompok / TPQ
     */
    public function test_master_can_manage_group(): void
    {
        $master = User::where('role', 'master')->first();

        // 1. Create group
        $response = $this->actingAs($master)
            ->post(route('master.groups.store'), [
                'name' => 'TPQ An-Nur Selaparang',
                'code' => 'TPQ-ANNUR',
            ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('groups', [
            'name' => 'TPQ An-Nur Selaparang',
            'code' => 'TPQ-ANNUR',
        ]);

        $group = \App\Models\Group::where('name', 'TPQ An-Nur Selaparang')->first();

        // 2. Update group
        $response = $this->actingAs($master)
            ->put(route('master.groups.update', $group->id), [
                'name' => 'TPQ An-Nur Mataram',
                'code' => 'TPQ-ANNUR-MTR',
            ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'name' => 'TPQ An-Nur Mataram',
        ]);

        // 3. Delete group (no participants)
        $response = $this->actingAs($master)
            ->delete(route('master.groups.destroy', $group->id));
        $response->assertRedirect();
        $this->assertDatabaseMissing('groups', [
            'id' => $group->id,
        ]);
    }

    /**
     * Test Master cannot delete group with participants
     */
    public function test_master_cannot_delete_group_with_participants(): void
    {
        $master = User::where('role', 'master')->first();
        $group = \App\Models\Group::where('name', 'Mataram')->first();

        $response = $this->actingAs($master)
            ->delete(route('master.groups.destroy', $group->id));
        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
        ]);
    }

    /**
     * Test Master can create, update, and delete Grade Level
     */
    public function test_master_can_manage_grade_level(): void
    {
        $master = User::where('role', 'master')->first();

        // 1. Create grade level
        $response = $this->actingAs($master)
            ->post(route('master.grade-levels.store'), [
                'name' => 'Kelas Remaja Masjid',
                'code' => 'REMAJA',
            ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('grade_levels', [
            'name' => 'Kelas Remaja Masjid',
            'code' => 'REMAJA',
        ]);

        $grade = \App\Models\GradeLevel::where('name', 'Kelas Remaja Masjid')->first();

        // 2. Update grade level
        $response = $this->actingAs($master)
            ->put(route('master.grade-levels.update', $grade->id), [
                'name' => 'Kelas Remaja Masjid & SMA',
                'code' => 'REMAJA_SMA',
            ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('grade_levels', [
            'id' => $grade->id,
            'name' => 'Kelas Remaja Masjid & SMA',
        ]);

        // 3. Delete grade level (no participants)
        $response = $this->actingAs($master)
            ->delete(route('master.grade-levels.destroy', $grade->id));
        $response->assertRedirect();
        $this->assertDatabaseMissing('grade_levels', [
            'id' => $grade->id,
        ]);
    }

    /**
     * Test Master can create and update competition with eligible grade levels
     */
    public function test_master_can_create_and_update_competition_with_grade_levels(): void
    {
        $master = User::where('role', 'master')->first();
        $grade1 = \App\Models\GradeLevel::where('code', 'KELAS_1')->first();
        $grade2 = \App\Models\GradeLevel::where('code', 'KELAS_2')->first();

        // 1. Create competition with Grade 1 & 2
        $response = $this->actingAs($master)
            ->post(route('master.competitions.store'), [
                'name' => 'Cerdas Cermat Cilik',
                'format' => 'individual',
                'grade_ids' => [$grade1->id, $grade2->id],
            ]);

        $response->assertRedirect(route('master.competitions'));
        $this->assertDatabaseHas('competitions', [
            'name' => 'Cerdas Cermat Cilik',
            'format' => 'individual',
        ]);

        $comp = Competition::where('name', 'Cerdas Cermat Cilik')->first();
        $this->assertEquals(2, $comp->gradeLevels()->count());
        $this->assertTrue($comp->gradeLevels->contains($grade1->id));
        $this->assertTrue($comp->gradeLevels->contains($grade2->id));

        // 2. Update competition to only Grade 1
        $response = $this->actingAs($master)
            ->put(route('master.competitions.update', $comp->id), [
                'name' => 'Cerdas Cermat Cilik (Khusus Kelas 1)',
                'format' => 'individual',
                'grade_ids' => [$grade1->id],
            ]);

        $response->assertRedirect();
        $comp->refresh();
        $this->assertEquals('Cerdas Cermat Cilik (Khusus Kelas 1)', $comp->name);
        $this->assertEquals(1, $comp->gradeLevels()->count());
        $this->assertTrue($comp->gradeLevels->contains($grade1->id));
        $this->assertFalse($comp->gradeLevels->contains($grade2->id));
    }

    /**
     * Test Master can delete a competition without score submissions
     */
    public function test_master_can_delete_competition_without_scores(): void
    {
        $master = User::where('role', 'master')->first();
        $event = Event::where('status', 'active')->first();

        // Create dummy competition
        $comp = Competition::create([
            'event_id' => $event->id,
            'name' => 'Lomba Uji Coba Hapus',
            'format' => 'individual',
            'eligibility_rule' => 'Semua Tingkat',
            'status' => 'ready',
        ]);

        $response = $this->actingAs($master)
            ->delete(route('master.competitions.destroy', $comp->id));

        $response->assertRedirect(route('master.competitions'));
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('competitions', ['id' => $comp->id]);
    }

    /**
     * Test Master cannot delete a competition that has submitted scores
     */
    public function test_master_cannot_delete_competition_with_submitted_scores(): void
    {
        $master = User::where('role', 'master')->first();
        $adzanComp = Competition::where('name', 'Adzan (Kls 1–6)')->first();
        $entry = Entry::where('competition_id', $adzanComp->id)->first();
        $assignment = JudgeAssignment::where('competition_id', $adzanComp->id)->first();

        \App\Models\ScoreSubmission::create([
            'entry_id' => $entry->id,
            'assignment_id' => $assignment->id,
            'rubric_id' => $adzanComp->activeRubric->id,
            'score_total' => 85.0,
            'status' => 'submitted',
            'submitted_at' => now(),
        ]);

        $response = $this->actingAs($master)
            ->delete(route('master.competitions.destroy', $adzanComp->id));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('competitions', ['id' => $adzanComp->id]);
    }

    /**
     * Test Master can access certificate editor page
     */
    public function test_master_can_access_certificates_page(): void
    {
        $master = User::where('role', 'master')->first();

        $response = $this->actingAs($master)
            ->get(route('master.certificates'));

        $response->assertStatus(200);
        $response->assertSee('Visual Kanvas Piagam');
        $response->assertSee('sample-sertifikat-a4.jpg');
    }

    /**
     * Test Master can save certificate template coordinates and styles
     */
    public function test_master_can_save_certificate_template(): void
    {
        $master = User::where('role', 'master')->first();
        $defaults = \App\Models\EventSetting::defaultCertificateTemplate();

        // Modify X/Y coordinate for name
        $fields = $defaults['fields'];
        $fields['name']['x'] = 52.5;
        $fields['name']['y'] = 40.0;
        $fields['name']['font_size'] = 42;

        $response = $this->actingAs($master)
            ->post(route('master.certificates.template.save'), [
                'template' => [
                    'background_image' => '/images/sample-sertifikat-a4.jpg',
                    'paper_size' => 'a4',
                    'orientation' => 'landscape',
                    'fields' => $fields,
                ]
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $setting = \App\Models\EventSetting::first();
        $tpl = $setting->getCertificateTemplate();
        $this->assertEquals(52.5, $tpl['fields']['name']['x']);
        $this->assertEquals(40.0, $tpl['fields']['name']['y']);
        $this->assertEquals(42, $tpl['fields']['name']['font_size']);
    }

    /**
     * Test Master can access print certificates page
     */
    public function test_master_can_access_print_certificates_page(): void
    {
        $master = User::where('role', 'master')->first();

        $response = $this->actingAs($master)
            ->get(route('master.certificates.print'));

        $response->assertStatus(200);
        $response->assertSee('Cetak Piagam / Sertifikat');
        $response->assertSee('Muhammad Al Fatih');
    }

    /**
     * Test official 2026 seeded data integrity (14 competitions, 42 judges, 208 participants, no Yel-Yel)
     */
    public function test_official_2026_seeded_dataset_integrity(): void
    {
        $this->assertEquals(14, Competition::count());
        $this->assertEquals(42, User::where('role', 'judge')->count());
        $this->assertEquals(208, Participant::count());
        $this->assertEquals(208, Entry::count());
        $this->assertEquals(0, Team::count());
        $this->assertNull(Competition::where('name', 'like', '%Yel-Yel%')->first());

        // Ambiguous entries preserved so Master can review/edit/delete
        $this->assertDatabaseHas('participants', ['name' => 'Vina Aprilia Putri']);
        $this->assertDatabaseHas('participants', ['name' => 'Lalu Rahadean (Partisipan)']);
        $this->assertEquals(2, Participant::where('name', 'Venita Alexandria Bagus')->count());
    }

    /**
     * Test Master can update and delete a judge, and cannot delete judge who submitted scores
     */
    public function test_master_can_update_and_delete_judge(): void
    {
        $master = User::where('role', 'master')->first();

        // Create judge
        $this->actingAs($master)->post(route('master.judges.store'), [
            'name' => 'Ustadz Uji Coba',
            'phone' => '089999888777',
            'password' => 'secret123',
        ])->assertRedirect(route('master.judges'));

        $judge = User::where('phone', '089999888777')->first();
        $this->assertNotNull($judge);

        // Update judge
        $this->actingAs($master)->put(route('master.judges.update', $judge->id), [
            'name' => 'Ustadz Uji Coba (Diedit)',
            'phone' => '089999888778',
            'status' => 'inactive',
        ])->assertRedirect(route('master.judges'));

        $this->assertDatabaseHas('users', [
            'id' => $judge->id,
            'name' => 'Ustadz Uji Coba (Diedit)',
            'phone' => '089999888778',
            'status' => 'inactive',
        ]);

        // Delete judge (no scores)
        $this->actingAs($master)->delete(route('master.judges.destroy', $judge->id))
            ->assertRedirect(route('master.judges'));
        $this->assertDatabaseMissing('users', ['id' => $judge->id]);
    }

    /**
     * Test Master can remove an unscored entry directly from competition detail
     */
    public function test_master_can_remove_entry_from_competition_detail(): void
    {
        $master = User::where('role', 'master')->first();
        $tilawati = Competition::where('name', 'Baca Tilawati Jilid 1–3 (Kls 1–2)')->first();
        $vina = Participant::where('name', 'Vina Aprilia Putri')->first();
        $entry = Entry::where('competition_id', $tilawati->id)->where('participant_id', $vina->id)->first();

        $this->assertNotNull($entry);

        $response = $this->actingAs($master)
            ->delete(route('master.competitions.entries.destroy', [$tilawati->id, $entry->id]));

        $response->assertRedirect();
        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('entries', ['id' => $entry->id]);
    }

    /**
     * Test judges log in using their phone number as both username and password
     */
    public function test_judge_can_login_with_phone_as_username_and_password(): void
    {
        // 1. Seeded judge logs in with phone as both username & password
        $res1 = $this->post(route('login.post'), [
            'phone' => '085200260025',
            'password' => '085200260025',
        ]);
        $res1->assertRedirect(route('judge.dashboard'));
        $this->assertAuthenticatedAs(User::where('phone', '085200260025')->first());

        $this->post(route('logout'));

        // 2. Master creates a judge leaving password blank -> defaults to normalized phone
        $master = User::where('role', 'master')->first();
        $this->actingAs($master)->post(route('master.judges.store'), [
            'name' => 'Juri Baru HP',
            'phone' => '+62 819-0711-2233',
            'password' => '',
        ])->assertRedirect(route('master.judges'));

        $newJudge = User::where('phone', '081907112233')->first();
        $this->assertNotNull($newJudge);

        $this->post(route('logout'));

        // New judge logs in using phone (even with +62 format)
        $res2 = $this->post(route('login.post'), [
            'phone' => '+6281907112233',
            'password' => '081907112233',
        ]);
        $res2->assertRedirect(route('judge.dashboard'));

        // 3. Master updates judge's phone and enables sync_password_phone -> new phone becomes new password
        $this->actingAs($master)->put(route('master.judges.update', $newJudge->id), [
            'name' => 'Juri Baru HP',
            'phone' => '081907119999',
            'sync_password_phone' => 1,
            'status' => 'active',
        ])->assertRedirect(route('master.judges'));

        $this->post(route('logout'));

        $res3 = $this->post(route('login.post'), [
            'phone' => '081907119999',
            'password' => '081907119999',
        ]);
        $res3->assertRedirect(route('judge.dashboard'));
    }

    /**
     * Test Cloudflare Flexible SSL (HTTP origin with X-Forwarded-Proto: https) works without mixed content or redirect loop
     */
    public function test_cloudflare_flexible_ssl_proxy_headers_produce_https_urls(): void
    {
        $response = $this->get('http://faslombok.galipatsistem.com/login', [
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host' => 'faslombok.galipatsistem.com',
            'X-Forwarded-Port' => '443',
        ]);

        $response->assertStatus(200);
        $response->assertSee('https://faslombok.galipatsistem.com', false);
        $this->assertTrue(request()->isSecure());
    }

    /**
     * Test event date is 20 Desember 2026, location is Lombok, and no Asia/Makassar text is exposed
     */
    public function test_event_date_location_and_wita_lombok_timezone_labels(): void
    {
        $home = $this->get(route('home'));
        $home->assertStatus(200);
        $home->assertSee('20 Desember 2026');
        $home->assertSee('Masjid Uswatun Hasanah ~ LDII Lombok');
        $home->assertSee('WITA (Lombok)');
        $home->assertDontSee('Asia/Makassar');
        $home->assertDontSee('Makassar');

        $master = User::where('role', 'master')->first();
        $masterDash = $this->actingAs($master)->get(route('master.dashboard'));
        $masterDash->assertStatus(200);
        $masterDash->assertSee('WITA (Lombok)');
        $masterDash->assertDontSee('Asia/Makassar');
        $masterDash->assertDontSee('Makassar');

        $this->assertSame('20 Dec 2026, 08:00', \App\Models\Event::formatWita('2026-12-20 00:00:00'));
    }

    /**
     * Test sayamaster / master354 master account login and ensure credentials stay hidden from login UI
     */
    public function test_new_master_sayamaster_can_login_and_is_not_leaked_on_login_page(): void
    {
        $loginPage = $this->get(route('login'));
        $loginPage->assertStatus(200);
        $loginPage->assertSee('type="text"', false);
        $loginPage->assertDontSee('sayamaster');
        $loginPage->assertDontSee('master354');

        $res = $this->post(route('login.post'), [
            'phone' => 'sayamaster',
            'password' => 'master354',
        ]);

        $res->assertRedirect(route('master.dashboard'));
        $this->assertAuthenticated();
        $user = auth()->user();
        $this->assertSame('master', $user->role);
        $this->assertSame('sayamaster', $user->phone);
    }

    /**
     * Test all GET pages (public, master, judge) including /juara-umum render cleanly with HTTP 200
     */
    public function test_all_public_master_and_judge_pages_render_without_server_error(): void
    {
        $event = Event::first();
        $comp = Competition::first();
        $entry = Entry::where('competition_id', $comp->id)->first();
        $master = User::where('phone', 'sayamaster')->first();
        $assignment = JudgeAssignment::where('competition_id', $comp->id)->first();
        $judge = $assignment->user;

        // Public pages (before any competition is published)
        foreach ([
            route('home'),
            route('pengumuman'),
            route('juara-umum'),
            route('login'),
        ] as $url) {
            $this->get($url)->assertStatus(200);
        }

        // Verify groups show on /juara-umum
        $group = Group::where('event_id', $event->id)->orderBy('name')->first();
        $this->get(route('juara-umum'))
            ->assertStatus(200)
            ->assertSee($group->name);

        // Master pages
        foreach ([
            route('master.dashboard'),
            route('master.competitions'),
            route('master.competitions.detail', $comp),
            route('master.participants'),
            route('master.print.qr'),
            route('master.judges'),
            route('master.queue', ['competition_id' => $comp->id]),
            route('master.scores', ['competition_id' => $comp->id]),
            route('master.reports'),
            route('master.certificates'),
            route('master.certificates.print'),
        ] as $url) {
            $this->actingAs($master)->get($url)->assertStatus(200);
        }

        // Judge pages
        foreach ([
            route('judge.dashboard'),
            route('judge.scanner'),
            route('judge.competition.entries', $comp),
            route('judge.scoring.form', $entry),
        ] as $url) {
            $this->actingAs($judge)->get($url)->assertStatus(200);
        }
    }
}
