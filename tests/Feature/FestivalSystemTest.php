<?php

namespace Tests\Feature;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Event;
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
        $judgeAhmad = User::where('phone', '081111111111')->first(); // Juri Adzan
        $event = Event::first();
        $adzanComp = Competition::where('name', 'Adzan')->first();

        // Peserta Adzan: Muhammad Rayyan Al-Farisi
        $participant = Participant::where('name', 'Muhammad Rayyan Al-Farisi')->first();
        $qrToken = app(QrService::class)->getOrCreateForParticipant($participant);

        $response = $this->actingAs($judgeAhmad)
            ->postJson(route('judge.resolve-qr'), [
                'token' => $qrToken->token_plain,
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('success', $data['status']);
        $this->assertEquals('Muhammad Rayyan Al-Farisi', $data['entity']['name']);
        $this->assertNotEmpty($data['entries']);

        // Judge Ahmad is assigned to Adzan & Dai Caberawit. Both are entered by Rayyan.
        $compNames = collect($data['entries'])->pluck('competition_name')->toArray();
        $this->assertContains('Adzan', $compNames);
    }

    /**
     * UAT 2: Juri Adzan memindai QR peserta yang hanya ikut Kaligrafi => tidak bisa menilai & tidak bocor data sensitif.
     */
    public function test_judge_adzan_scanning_participant_who_only_joined_kaligrafi_is_safely_rejected(): void
    {
        // Judge Fauzi only has Adzan & Hafalan Surat Pendek
        $judgeFauzi = User::where('phone', '082222222222')->first();

        // Syamil Al-Banna only joined Kaligrafi
        $participant = Participant::where('name', 'Syamil Al-Banna')->first();
        $qrToken = app(QrService::class)->getOrCreateForParticipant($participant);

        $response = $this->actingAs($judgeFauzi)
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
     * UAT 3: Peserta ikut Adzan dan Dai => QR sama; juri hanya melihat lomba yang ditugaskan.
     */
    public function test_participant_in_multiple_competitions_only_exposes_authorized_competitions_to_judge(): void
    {
        // Judge Fauzi only has Adzan & Surat Pendek (NOT Dai Caberawit)
        $judgeFauzi = User::where('phone', '082222222222')->first();

        // Rayyan joins Adzan & Dai Caberawit
        $participant = Participant::where('name', 'Muhammad Rayyan Al-Farisi')->first();
        $qrToken = app(QrService::class)->getOrCreateForParticipant($participant);

        $response = $this->actingAs($judgeFauzi)
            ->postJson(route('judge.resolve-qr'), [
                'token' => $qrToken->token_plain,
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('success', $data['status']);
        $compNames = collect($data['entries'])->pluck('competition_name')->toArray();

        // Must contain Adzan, but must NOT contain Dai Caberawit for Judge Fauzi
        $this->assertContains('Adzan', $compNames);
        $this->assertNotContains('Dai Caberawit', $compNames);
    }

    /**
     * UAT 4: Juri akses view-only => endpoint score/write ditolak.
     */
    public function test_view_only_judge_cannot_submit_score(): void
    {
        $judgeAhmad = User::where('phone', '081111111111')->first();
        $adzanComp = Competition::where('name', 'Adzan')->first();

        // Change judge Ahmad's assignment on Adzan to view-only (can_score = false)
        $assignment = JudgeAssignment::where('user_id', $judgeAhmad->id)
            ->where('competition_id', $adzanComp->id)
            ->first();
        $assignment->update(['can_score' => false]);

        $entry = Entry::where('competition_id', $adzanComp->id)->first();

        $response = $this->actingAs($judgeAhmad)
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
        $adzanComp = Competition::where('name', 'Adzan')->first();

        // Try to update rubric with criteria totaling 95%
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
     * UAT 6: Dua juri menilai peserta yang sama => masing-masing tersimpan independen; nilai akhir total keduanya terakumulasi saat seluruh juri wajib selesai.
     */
    public function test_two_judges_score_same_entry_independently_and_aggregate(): void
    {
        $judgeAhmad = User::where('phone', '081111111111')->first();
        $judgeFauzi = User::where('phone', '082222222222')->first();
        $adzanComp = Competition::where('name', 'Adzan')->first();
        $entry = Entry::where('competition_id', $adzanComp->id)->first();

        $rubric = $adzanComp->activeRubric;
        $criteria = $rubric->criteria;
        $ratingBaik = $rubric->ratingOptions->firstWhere('label', 'Baik'); // 100
        $ratingCukup = $rubric->ratingOptions->firstWhere('label', 'Cukup'); // 75

        $scoringService = app(ScoringService::class);
        $asAhmad = JudgeAssignment::where('user_id', $judgeAhmad->id)->where('competition_id', $adzanComp->id)->first();
        $asFauzi = JudgeAssignment::where('user_id', $judgeFauzi->id)->where('competition_id', $adzanComp->id)->first();

        // Juri Ahmad gives all Baik (100) -> single score = 100
        $scoresAhmad = [];
        foreach ($criteria as $c) {
            $scoresAhmad[$c->id] = ['rating_option_id' => $ratingBaik->id];
        }
        $subAhmad = $scoringService->submitScore($entry, $asAhmad, $scoresAhmad, 'Penilaian Ahmad', $judgeAhmad);
        $this->assertEquals(100.0, (float) $subAhmad->score_total);

        // Before Fauzi grades, required_judges = 2, submitted = 1 -> incomplete
        $finalStatus1 = $scoringService->getFinalScore($entry);
        $this->assertFalse($finalStatus1['is_complete']);
        $this->assertEquals(1, $finalStatus1['submitted_judges']);

        // Juri Fauzi gives all Cukup (75) -> single score = 75
        $scoresFauzi = [];
        foreach ($criteria as $c) {
            $scoresFauzi[$c->id] = ['rating_option_id' => $ratingCukup->id];
        }
        $subFauzi = $scoringService->submitScore($entry, $asFauzi, $scoresFauzi, 'Penilaian Fauzi', $judgeFauzi);
        $this->assertEquals(75.0, (float) $subFauzi->score_total);

        // Now both required judges have submitted -> complete, total = 100 + 75 = 175
        $finalStatus2 = $scoringService->getFinalScore($entry);
        $this->assertTrue($finalStatus2['is_complete']);
        $this->assertEquals(2, $finalStatus2['submitted_judges']);
        $this->assertEquals(175.0, (float) $finalStatus2['final_score']);
    }

    /**
     * UAT 7: Perubahan nilai sebelum lock terekam; setelah lock ditolak hingga Master membuka.
     */
    public function test_score_updates_locked_when_competition_is_locked(): void
    {
        $judgeAhmad = User::where('phone', '081111111111')->first();
        $adzanComp = Competition::where('name', 'Adzan')->first();
        $entry = Entry::where('competition_id', $adzanComp->id)->first();

        // Lock competition
        $adzanComp->update(['is_locked' => true, 'locked_at' => now()]);

        $response = $this->actingAs($judgeAhmad)
            ->post(route('judge.scoring.submit', $entry->id), [
                'scores' => [],
            ]);

        $response->assertSessionHas('error');
    }

    /**
     * UAT 8: Tim Yel-Yel memiliki QR tim yang memunculkan identitas tim.
     */
    public function test_team_qr_resolves_team_entry(): void
    {
        $judgeRidwan = User::where('phone', '084444444444')->first(); // Juri Yel-Yel
        $team = Team::where('name', 'Laskar 29 Ampenan Juara')->first();
        $qrToken = app(QrService::class)->getOrCreateForTeam($team);

        $response = $this->actingAs($judgeRidwan)
            ->postJson(route('judge.resolve-qr'), [
                'token' => $qrToken->token_plain,
            ]);

        $response->assertStatus(200);
        $data = $response->json();

        $this->assertEquals('success', $data['status']);
        $this->assertEquals('team', $data['entity_type']);
        $this->assertEquals('Laskar 29 Ampenan Juara', $data['entity']['name']);
    }

    /**
     * UAT 9: Hasil publik belum tampil sebelum publish; setelah publish muncul.
     */
    public function test_public_results_only_available_after_publish(): void
    {
        $adzanComp = Competition::where('name', 'Adzan')->first();
        $master = User::where('role', 'master')->first();

        // Before publish: public detail gives 404
        $resBefore = $this->get(route('pengumuman.detail', $adzanComp->id));
        $resBefore->assertStatus(404);

        // Master publishes
        app(RankingService::class)->publish($adzanComp, $master);

        // After publish: 200 OK
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
                    . "Zaidan Al-Ayyubi,TPQ Al-Ikhlas Ampenan,KELAS_1_6,L,Adzan\n"
                    . "Maryam Sholihah,TPQ Al-Falah Mataram,KELAS_1_4,P,Baca Tilawati\n";

        $rows = $importService->parseCsv($csvContent);
        $result = $importService->importParticipants($event, $rows);

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['created']);

        $zaidan = Participant::where('name', 'Zaidan Al-Ayyubi')->first();
        $this->assertNotNull($zaidan);
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
        $response->assertSee('<svg', false);
    }

    /**
     * Test Master can edit participant data
     */
    public function test_master_can_update_participant(): void
    {
        $master = User::where('role', 'master')->first();
        $participant = Participant::where('name', 'Muhammad Rayyan Al-Farisi')->first();
        $group = $participant->group;

        $response = $this->actingAs($master)
            ->put(route('master.participants.update', $participant->id), [
                'name' => 'Muhammad Rayyan Al-Farisi (Updated)',
                'group_id' => $group->id,
                'gender' => 'L',
                'active' => 1,
            ]);

        $response->assertRedirect(route('master.participants'));
        $this->assertDatabaseHas('participants', [
            'id' => $participant->id,
            'name' => 'Muhammad Rayyan Al-Farisi (Updated)',
        ]);
    }
}
