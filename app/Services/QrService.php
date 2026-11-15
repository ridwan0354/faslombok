<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\Event;
use App\Models\JudgeAssignment;
use App\Models\Participant;
use App\Models\QrToken;
use App\Models\Team;
use App\Models\User;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;
use Illuminate\Support\Str;

class QrService
{
    /**
     * Generate or retrieve QR token for participant
     */
    public function getOrCreateForParticipant(Participant $participant): QrToken
    {
        $token = QrToken::where('event_id', $participant->event_id)
            ->where('entity_type', 'participant')
            ->where('entity_id', $participant->id)
            ->whereNull('revoked_at')
            ->first();

        if ($token) {
            return $token;
        }

        $plain = 'FAS-P-' . strtoupper(Str::random(12));
        $hash = hash('sha256', $plain);

        return QrToken::create([
            'event_id' => $participant->event_id,
            'entity_type' => 'participant',
            'entity_id' => $participant->id,
            'token_plain' => $plain,
            'token_hash' => $hash,
            'issued_at' => now(),
        ]);
    }

    /**
     * Generate or retrieve QR token for team
     */
    public function getOrCreateForTeam(Team $team): QrToken
    {
        $token = QrToken::where('event_id', $team->event_id)
            ->where('entity_type', 'team')
            ->where('entity_id', $team->id)
            ->whereNull('revoked_at')
            ->first();

        if ($token) {
            return $token;
        }

        $plain = 'FAS-T-' . strtoupper(Str::random(12));
        $hash = hash('sha256', $plain);

        return QrToken::create([
            'event_id' => $team->event_id,
            'entity_type' => 'team',
            'entity_id' => $team->id,
            'token_plain' => $plain,
            'token_hash' => $hash,
            'issued_at' => now(),
        ]);
    }

    /**
     * Generate SVG string for QR Code
     */
    public function renderSvg(string $text, int $size = 200): string
    {
        $options = new QROptions([
            'version'         => 5,
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64'    => false,
            'svgAddXmlHeader' => false,
            'eccLevel'        => EccLevel::M,
            'addQuietzone'    => true,
        ]);

        $svg = (new QRCode($options))->render($text);

        // Inject width and height into root <svg> tag for clean rendering
        if ($size > 0 && !str_contains($svg, 'width=')) {
            $svg = preg_replace('/<svg /', "<svg width=\"{$size}\" height=\"{$size}\" ", $svg, 1);
        }

        return $svg;
    }

    /**
     * Resolve a scanned QR string and filter entries eligible for the current user/judge
     */
    public function resolve(string $rawToken, User $user, Event $event): array
    {
        $rawToken = trim($rawToken);
        $hash = hash('sha256', $rawToken);

        $qr = QrToken::where('event_id', $event->id)
            ->where(function ($query) use ($rawToken, $hash) {
                $query->where('token_plain', $rawToken)
                      ->orWhere('token_hash', $hash);
            })
            ->whereNull('revoked_at')
            ->first();

        if (!$qr) {
            return [
                'status' => 'not_found',
                'message' => 'QR Code tidak ditemukan atau sudah tidak berlaku.',
                'entity' => null,
                'entries' => [],
            ];
        }

        $entity = null;
        $entriesQuery = Entry::with(['competition.activeRubric.criteria', 'competition.activeRubric.ratingOptions', 'scoreSubmissions'])
            ->where('event_id', $event->id);

        if ($qr->entity_type === 'participant') {
            $entity = Participant::with(['group', 'gradeLevel'])->find($qr->entity_id);
            if (!$entity || !$entity->active) {
                return [
                    'status' => 'inactive',
                    'message' => 'Peserta tidak aktif atau sudah dihapus.',
                    'entity' => null,
                    'entries' => [],
                ];
            }
            $entriesQuery->where('participant_id', $entity->id);
        } else {
            $entity = Team::with(['group', 'competition', 'members'])->find($qr->entity_id);
            if (!$entity) {
                return [
                    'status' => 'inactive',
                    'message' => 'Tim tidak ditemukan atau sudah dihapus.',
                    'entity' => null,
                    'entries' => [],
                ];
            }
            $entriesQuery->where('team_id', $entity->id);
        }

        $allEntries = $entriesQuery->get();

        // If Master Admin, master has full visibility
        if ($user->isMaster()) {
            return [
                'status' => 'success',
                'entity_type' => $qr->entity_type,
                'entity' => $entity,
                'entries' => $allEntries->map(function ($entry) {
                    return [
                        'id' => $entry->id,
                        'competition_id' => $entry->competition_id,
                        'competition_name' => $entry->competition->name,
                        'queue_number' => $entry->queue_number,
                        'status' => $entry->status,
                        'can_score' => true,
                        'can_view' => true,
                    ];
                }),
            ];
        }

        // Judge permissions check
        $assignments = JudgeAssignment::where('event_id', $event->id)
            ->where('user_id', $user->id)
            ->where('active', true)
            ->get()
            ->keyBy('competition_id');

        $filteredEntries = [];
        foreach ($allEntries as $entry) {
            $assignment = $assignments->get($entry->competition_id);
            if ($assignment && ($assignment->can_view_participants || $assignment->can_score)) {
                $filteredEntries[] = [
                    'id' => $entry->id,
                    'competition_id' => $entry->competition_id,
                    'competition_name' => $entry->competition->name,
                    'queue_number' => $entry->queue_number,
                    'status' => $entry->status,
                    'can_score' => (bool) $assignment->can_score,
                    'can_view' => (bool) $assignment->can_view_participants,
                    'can_view_peer_scores' => (bool) $assignment->can_view_peer_scores,
                    'assignment_id' => $assignment->id,
                ];
            }
        }

        if (empty($filteredEntries)) {
            return [
                'status' => 'unauthorized',
                'message' => 'Peserta terdaftar, tetapi tidak ada cabang lomba yang ditugaskan kepada Anda.',
                'entity' => null, // Privacy protection: Do not leak participant details
                'entries' => [],
            ];
        }

        return [
            'status' => 'success',
            'entity_type' => $qr->entity_type,
            'entity' => [
                'name' => $entity->name,
                'code' => $qr->entity_type === 'participant' ? $entity->participant_code : $entity->team_code,
                'group' => $entity->group ? $entity->group->name : '-',
                'grade' => ($qr->entity_type === 'participant' && $entity->gradeLevel) ? $entity->gradeLevel->name : '-',
            ],
            'entries' => $filteredEntries,
        ];
    }
}
