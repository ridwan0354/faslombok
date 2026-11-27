<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $datasetPath = database_path('seeders/data/official_2026_competitions.json');
        if (!file_exists($datasetPath)) {
            return;
        }

        $competitionsConfig = json_decode(file_get_contents($datasetPath), true) ?? [];

        foreach ($competitionsConfig as $cData) {
            foreach ($cData['judges'] ?? [] as $judgeEntry) {
                if (!is_array($judgeEntry) || empty($judgeEntry['phone'])) {
                    continue;
                }

                $name = $judgeEntry['name'];
                $newPhone = $judgeEntry['phone'];
                $legacyName = $judgeEntry['legacy_name'] ?? $name;
                $dummyPhone = $judgeEntry['dummy_phone'] ?? null;

                // Skip entries that still use dummy numbers (awaiting real WhatsApp numbers)
                if ($dummyPhone && $newPhone === $dummyPhone) {
                    continue;
                }

                $judge = User::where('role', 'judge')
                    ->where(function ($q) use ($dummyPhone, $name, $legacyName, $newPhone) {
                        if ($dummyPhone) {
                            $q->where('phone', $dummyPhone);
                        }
                        $q->orWhere('phone', $newPhone)
                            ->orWhere('name', $name)
                            ->orWhere('name', $legacyName);
                    })
                    ->first();

                if (!$judge) {
                    continue;
                }

                if (User::where('phone', $newPhone)->where('id', '!=', $judge->id)->exists()) {
                    continue;
                }

                $judge->update([
                    'name' => $name,
                    'phone' => $newPhone,
                    'email' => "juri{$newPhone}@faslombok2026.id",
                    'password' => Hash::make($newPhone),
                ]);
            }
        }
    }

    public function down(): void
    {
        // Intentional non-destructive migration for real judge WhatsApp numbers
    }
};
