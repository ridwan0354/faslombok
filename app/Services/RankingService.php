<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\Event;
use App\Models\Group;
use App\Models\ResultPublication;
use App\Models\ScoreAuditLog;
use App\Models\User;
use Illuminate\Support\Collection;

class RankingService
{
    protected ScoringService $scoringService;

    public function __construct(ScoringService $scoringService)
    {
        $this->scoringService = $scoringService;
    }

    /**
     * Compute leaderboard for a competition
     */
    public function getLeaderboard(Competition $competition): Collection
    {
        $entries = $competition->entries()
            ->with(['participant.group', 'participant.gradeLevel', 'team.group', 'team.members'])
            ->get();

        $awards = $competition->awards->keyBy('rank_position');

        $ranked = $entries->map(function ($entry) {
            $scoreData = $this->scoringService->getFinalScore($entry);

            return [
                'entry' => $entry,
                'name' => $entry->display_name,
                'code' => $entry->entity_code,
                'group' => $entry->group ? $entry->group->name : '-',
                'group_id' => $entry->group ? $entry->group->id : null,
                'status' => $entry->status,
                'is_complete' => $scoreData['is_complete'],
                'required_judges' => $scoreData['required_judges'],
                'submitted_judges' => $scoreData['submitted_judges'],
                'final_score' => $scoreData['final_score'],
                'average_score' => $scoreData['average_score'],
                'submissions' => $scoreData['submissions'],
            ];
        });

        // Sort: completed first, then final_score DESC
        $sorted = $ranked->sort(function ($a, $b) {
            if ($a['status'] === 'disqualified' && $b['status'] !== 'disqualified') return 1;
            if ($b['status'] === 'disqualified' && $a['status'] !== 'disqualified') return -1;
            if ($a['status'] === 'no_show' && $b['status'] !== 'no_show') return 1;
            if ($b['status'] === 'no_show' && $a['status'] !== 'no_show') return -1;

            if ($a['final_score'] != $b['final_score']) {
                return $b['final_score'] <=> $a['final_score'];
            }

            return $a['entry']->id <=> $b['entry']->id;
        })->values();

        // Assign ranks and awards
        $rankPos = 1;
        return $sorted->map(function ($item) use (&$rankPos, $awards) {
            $isEligibleForAward = in_array($item['status'], ['completed', 'registered', 'performing']) && $item['final_score'] > 0;
            $currentRank = $isEligibleForAward ? $rankPos++ : null;

            $award = $currentRank ? $awards->get($currentRank) : null;

            $item['rank'] = $currentRank;
            $item['award_label'] = $award ? $award->rank_label : null;
            $item['group_points'] = $award ? $award->points_for_group : 0;

            return $item;
        });
    }

    /**
     * Publish competition results snapshot
     */
    public function publish(Competition $competition, User $publisher): ResultPublication
    {
        $leaderboard = $this->getLeaderboard($competition);

        // Lock competition if not already locked
        if (!$competition->is_locked) {
            $competition->update([
                'is_locked' => true,
                'locked_at' => now(),
            ]);
        }

        $latestVersion = (int) $competition->publications()->max('version');
        $newVersion = $latestVersion + 1;

        $snapshot = [
            'competition_id' => $competition->id,
            'competition_name' => $competition->name,
            'format' => $competition->format,
            'published_at' => now()->toIso8601String(),
            'publisher_name' => $publisher->name,
            'leaderboard' => $leaderboard->toArray(),
        ];

        $publication = ResultPublication::create([
            'event_id' => $competition->event_id,
            'competition_id' => $competition->id,
            'version' => $newVersion,
            'payload_snapshot' => $snapshot,
            'published_by' => $publisher->id,
            'published_at' => now(),
        ]);

        $competition->update(['status' => 'published']);

        ScoreAuditLog::create([
            'actor_id' => $publisher->id,
            'action' => 'publish',
            'reason' => 'Publikasi hasil versi ' . $newVersion,
            'after_json' => ['version' => $newVersion, 'total_entries' => count($leaderboard)],
        ]);

        return $publication;
    }

    /**
     * Compute Group Champion (Juara Umum) for an event based on published results
     */
    public function getGroupChampionStandings(Event $event): Collection
    {
        $groups = Group::where('event_id', $event->id)->get()->keyBy('id');
        $standings = [];

        foreach ($groups as $id => $group) {
            $standings[$id] = [
                'group' => $group,
                'total_points' => 0,
                'gold_count' => 0,   // Juara 1
                'silver_count' => 0, // Juara 2
                'bronze_count' => 0, // Juara 3
                'other_awards_count' => 0,
                'awards_details' => [],
            ];
        }

        $publishedCompetitions = Competition::where('event_id', $event->id)
            ->where('status', 'published')
            ->with('latestPublication')
            ->get();

        foreach ($publishedCompetitions as $comp) {
            $pub = $comp->latestPublication;
            if (!$pub || !isset($pub->payload_snapshot['leaderboard'])) {
                continue;
            }

            foreach ($pub->payload_snapshot['leaderboard'] as $row) {
                $groupId = $row['group_id'] ?? null;
                $points = $row['group_points'] ?? 0;
                $awardLabel = $row['award_label'] ?? null;
                $rank = $row['rank'] ?? null;

                if ($groupId && isset($standings[$groupId]) && $points > 0) {
                    $standings[$groupId]['total_points'] += $points;

                    if ($rank === 1) {
                        $standings[$groupId]['gold_count']++;
                    } elseif ($rank === 2) {
                        $standings[$groupId]['silver_count']++;
                    } elseif ($rank === 3) {
                        $standings[$groupId]['bronze_count']++;
                    } else {
                        $standings[$groupId]['other_awards_count']++;
                    }

                    $standings[$groupId]['awards_details'][] = [
                        'competition' => $comp->name,
                        'award' => $awardLabel,
                        'participant' => $row['name'],
                        'points' => $points,
                    ];
                }
            }
        }

        // Sort by total_points DESC, then gold_count DESC, silver_count DESC
        $collection = collect($standings)->sort(function ($a, $b) {
            if ($a['total_points'] != $b['total_points']) {
                return $b['total_points'] <=> $a['total_points'];
            }
            if ($a['gold_count'] != $b['gold_count']) {
                return $b['gold_count'] <=> $a['gold_count'];
            }
            if ($a['silver_count'] != $b['silver_count']) {
                return $b['silver_count'] <=> $a['silver_count'];
            }
            return $b['bronze_count'] <=> $a['bronze_count'];
        })->values();

        $rank = 1;
        return $collection->map(function ($item) use (&$rank) {
            $item['rank'] = $rank++;
            return $item;
        });
    }
}
