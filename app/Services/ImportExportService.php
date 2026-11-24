<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Event;
use App\Models\GradeLevel;
use App\Models\Group;
use App\Models\Participant;
use App\Models\Team;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ImportExportService
{
    protected QrService $qrService;

    public function __construct(QrService $qrService)
    {
        $this->qrService = $qrService;
    }

    /**
     * Parse CSV contents for preview
     */
    public function parseCsv(string $csvContent): array
    {
        $lines = preg_split('/\r\n|\r|\n/', trim($csvContent));
        if (empty($lines)) {
            return [];
        }

        $header = str_getcsv(array_shift($lines));
        $header = array_map(function ($col) {
            return strtolower(trim(str_replace([' ', '_', '-'], '', $col)));
        }, $header);

        $rows = [];
        foreach ($lines as $line) {
            if (!trim($line)) continue;
            $row = str_getcsv($line);
            if (count($row) === count($header)) {
                $rows[] = array_combine($header, $row);
            }
        }

        return $rows;
    }

    /**
     * Validate and import participants from parsed rows
     */
    public function importParticipants(Event $event, array $rows): array
    {
        $createdCount = 0;
        $updatedCount = 0;
        $entriesCreated = 0;
        $errors = [];

        $groups = Group::where('event_id', $event->id)->get()->keyBy(function ($item) {
            return strtolower(trim($item->name));
        });

        $grades = GradeLevel::where('event_id', $event->id)->get()->keyBy(function ($item) {
            return strtolower(trim($item->code));
        });

        $competitions = Competition::where('event_id', $event->id)->get()->keyBy(function ($item) {
            return strtolower(trim($item->name));
        });

        DB::beginTransaction();
        try {
            foreach ($rows as $index => $row) {
                $lineNum = $index + 2; // account for header
                $name = trim($row['nama'] ?? $row['name'] ?? '');
                $groupName = trim($row['kelompok'] ?? $row['group'] ?? $row['kafilah'] ?? '');
                $gradeCode = trim($row['kelas'] ?? $row['grade'] ?? '');
                $gender = strtoupper(trim($row['jeniskelamin'] ?? $row['gender'] ?? 'L'));
                if (!in_array($gender, ['L', 'P'])) {
                    $gender = 'L';
                }
                $compList = trim($row['lomba'] ?? $row['competitions'] ?? '');

                if (!$name) {
                    $errors[] = "Baris $lineNum: Nama peserta tidak boleh kosong.";
                    continue;
                }

                if (!$groupName) {
                    $groupName = 'Umum';
                }

                // Normalize common aliases for groups
                $groupKey = strtolower($groupName);
                $groupAliases = [
                    'cakra' => 'cakranegara',
                    'perumnas' => 'prumnas',
                    'perum' => 'prumnas',
                ];
                if (isset($groupAliases[$groupKey]) && $groups->has($groupAliases[$groupKey])) {
                    $groupKey = $groupAliases[$groupKey];
                }

                // Find or create group
                if (!$groups->has($groupKey)) {
                    $newGroup = Group::create([
                        'event_id' => $event->id,
                        'name' => $groupName,
                        'code' => strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $groupName), 0, 4)),
                    ]);
                    $groups->put($groupKey, $newGroup);
                }
                $group = $groups->get($groupKey);

                // Find grade
                $grade = null;
                $gradeKey = strtolower($gradeCode);
                if ($grades->has($gradeKey)) {
                    $grade = $grades->get($gradeKey);
                }

                // Check duplicate participant in same event and group
                $participant = Participant::where('event_id', $event->id)
                    ->where('group_id', $group->id)
                    ->where('name', $name)
                    ->first();

                if ($participant) {
                    $updatedCount++;
                } else {
                    $nextCodeNum = Participant::where('event_id', $event->id)->count() + 1;
                    do {
                        $code = sprintf('FAS-%s-%04d', $event->year, $nextCodeNum++);
                    } while (Participant::where('event_id', $event->id)->where('participant_code', $code)->exists());

                    $participant = Participant::create([
                        'event_id' => $event->id,
                        'group_id' => $group->id,
                        'grade_id' => $grade ? $grade->id : null,
                        'participant_code' => $code,
                        'name' => $name,
                        'gender' => $gender,
                        'active' => true,
                    ]);
                    $createdCount++;

                    // Generate QR Token
                    $this->qrService->getOrCreateForParticipant($participant);
                }

                // Register into competitions
                if ($compList) {
                    $targetComps = array_map('trim', explode(',', $compList));
                    foreach ($targetComps as $targetName) {
                        $compKey = strtolower($targetName);
                        $comp = $competitions->get($compKey);

                        if (!$comp) {
                            // Try partial match
                            foreach ($competitions as $ck => $cv) {
                                if (str_contains($ck, $compKey) || str_contains($compKey, $ck)) {
                                    $comp = $cv;
                                    break;
                                }
                            }
                        }

                        if ($comp && $comp->isIndividual()) {
                            $exists = Entry::where('competition_id', $comp->id)
                                ->where('participant_id', $participant->id)
                                ->exists();

                            if (!$exists) {
                                $queueNum = Entry::where('competition_id', $comp->id)->count() + 1;
                                Entry::create([
                                    'event_id' => $event->id,
                                    'competition_id' => $comp->id,
                                    'participant_id' => $participant->id,
                                    'queue_number' => $queueNum,
                                    'status' => 'registered',
                                ]);
                                $entriesCreated++;
                            }
                        }
                    }
                }
            }

            DB::commit();
            return [
                'success' => true,
                'created' => $createdCount,
                'updated' => $updatedCount,
                'entries_created' => $entriesCreated,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Gagal memproses data impor: ' . $e->getMessage(),
                'errors' => [$e->getMessage()],
            ];
        }
    }
}
