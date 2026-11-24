<?php

use App\Models\Competition;
use App\Models\Event;
use App\Models\GradeLevel;
use App\Models\Group;
use App\Models\Participant;
use App\Services\QrService;
use Database\Seeders\FestivalSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        $event = Event::where('status', 'active')->first() ?? Event::first();
        if (!$event) {
            return;
        }

        if (Competition::where('event_id', $event->id)->where('name', 'like', '%Yel-Yel%')->exists()) {
            return;
        }

        $datasetPath = database_path('seeders/data/official_2026_competitions.json');
        if (!file_exists($datasetPath)) {
            return;
        }

        $competitionsConfig = json_decode(file_get_contents($datasetPath), true) ?? [];
        $yelYelConfig = collect($competitionsConfig)->first(
            fn($c) => ($c['format'] ?? '') === 'team' || str_contains(strtolower($c['name'] ?? ''), 'yel-yel')
        );

        if (!$yelYelConfig) {
            return;
        }

        $grades = GradeLevel::where('event_id', $event->id)->get()->keyBy('code')->all();
        $groupsMap = Group::where('event_id', $event->id)->get()->keyBy('name')->all();

        $judgeCounter = 43;
        $participantCounter = Participant::where('event_id', $event->id)->count() + 1;

        FestivalSeeder::seedCompetitionFromConfig(
            $event,
            $yelYelConfig,
            app(QrService::class),
            $grades,
            $groupsMap,
            $judgeCounter,
            $participantCounter
        );
    }

    public function down(): void
    {
        // Non-destructive rollback intentionally omitted for seeded competition data
    }
};
