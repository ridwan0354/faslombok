<?php

namespace App\Services;

use App\Models\Competition;
use App\Models\Entry;
use App\Models\JudgeAssignment;
use App\Models\RatingOption;
use App\Models\Rubric;
use App\Models\RubricCriterion;
use App\Models\ScoreAuditLog;
use App\Models\ScoreItem;
use App\Models\ScoreSubmission;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Exception;

class ScoringService
{
    /**
     * Calculate score total based on rubric criteria weights and rating option numeric values.
     * Formula: Sum(numeric_value * weight_pct / 100)
     */
    public function calculate(Rubric $rubric, array $itemsData): array
    {
        $criteria = $rubric->criteria->keyBy('id');
        $ratingOptions = $rubric->ratingOptions->keyBy('id');

        $totalScore = 0.0;
        $calculatedItems = [];

        foreach ($itemsData as $criterionId => $data) {
            $criterion = $criteria->get($criterionId);
            if (!$criterion) {
                continue;
            }

            $numericValue = 0.0;
            $ratingOptionId = null;
            $comment = $data['comment'] ?? null;

            if (isset($data['rating_option_id']) && $ratingOptions->has($data['rating_option_id'])) {
                $option = $ratingOptions->get($data['rating_option_id']);
                $ratingOptionId = $option->id;
                $numericValue = (float) $option->numeric_value;
            } elseif (isset($data['numeric_value'])) {
                $numericValue = (float) $data['numeric_value'];
            }

            // Weighted score: value * (weight / 100)
            $weightedScore = round($numericValue * ((float) $criterion->weight_pct / 100), 2);
            $totalScore += $weightedScore;

            $calculatedItems[] = [
                'criterion_id' => $criterion->id,
                'rating_option_id' => $ratingOptionId,
                'numeric_value' => $numericValue,
                'weighted_score' => $weightedScore,
                'comment' => $comment,
            ];
        }

        return [
            'total_score' => round($totalScore, 2),
            'items' => $calculatedItems,
        ];
    }

    /**
     * Submit or update a judge's score for an entry idempotently
     */
    public function submitScore(
        Entry $entry,
        JudgeAssignment $assignment,
        array $itemsData,
        ?string $notes = null,
        ?User $actor = null,
        string $status = 'submitted'
    ): ScoreSubmission {
        return DB::transaction(function () use ($entry, $assignment, $itemsData, $notes, $actor, $status) {
            $competition = $entry->competition;

            // Check if competition is locked
            if ($competition->is_locked && (!$actor || !$actor->isMaster())) {
                throw new Exception('Penilaian lomba ini sudah dikunci oleh Master Admin.');
            }

            // Check if assignment can score
            if (!$assignment->can_score && (!$actor || !$actor->isMaster())) {
                throw new Exception('Hak akses Anda untuk menilai lomba ini tidak aktif.');
            }

            $rubric = $competition->activeRubric;
            if (!$rubric) {
                throw new Exception('Rubrik penilaian aktif belum ditentukan untuk lomba ini.');
            }

            if (!$rubric->isWeightValid()) {
                throw new Exception('Rubrik penilaian memiliki bobot yang tidak 100% (' . $rubric->totalWeight() . '%).');
            }

            $calc = $this->calculate($rubric, $itemsData);

            $submission = ScoreSubmission::where('entry_id', $entry->id)
                ->where('assignment_id', $assignment->id)
                ->where('rubric_id', $rubric->id)
                ->first();

            $beforeJson = null;
            $action = 'create';

            if ($submission) {
                if ($submission->status === 'locked' && (!$actor || !$actor->isMaster())) {
                    throw new Exception('Penilaian ini sudah dikunci dan tidak dapat diubah.');
                }

                $action = 'update';
                $beforeJson = [
                    'score_total' => $submission->score_total,
                    'status' => $submission->status,
                    'revision' => $submission->revision,
                    'notes' => $submission->notes,
                ];

                $submission->update([
                    'status' => $status,
                    'score_total' => $calc['total_score'],
                    'revision' => $submission->revision + 1,
                    'notes' => $notes,
                    'submitted_at' => now(),
                ]);
            } else {
                $submission = ScoreSubmission::create([
                    'entry_id' => $entry->id,
                    'assignment_id' => $assignment->id,
                    'rubric_id' => $rubric->id,
                    'status' => $status,
                    'score_total' => $calc['total_score'],
                    'revision' => 1,
                    'notes' => $notes,
                    'submitted_at' => now(),
                ]);
            }

            // Delete old items and re-insert
            $submission->items()->delete();
            foreach ($calc['items'] as $item) {
                ScoreItem::create(array_merge($item, ['submission_id' => $submission->id]));
            }

            // Update Entry status to 'completed' if submitted
            if ($status === 'submitted' && in_array($entry->status, ['registered', 'queued', 'called', 'performing'])) {
                $entry->update(['status' => 'completed']);
            }

            // Audit log
            ScoreAuditLog::create([
                'actor_id' => $actor ? $actor->id : $assignment->user_id,
                'submission_id' => $submission->id,
                'entry_id' => $entry->id,
                'action' => $action,
                'before_json' => $beforeJson,
                'after_json' => [
                    'score_total' => $submission->score_total,
                    'status' => $submission->status,
                    'revision' => $submission->revision,
                    'items_count' => count($calc['items']),
                ],
                'reason' => $actor && $actor->isMaster() ? 'Master entry/edit' : 'Juri submit',
            ]);

            return $submission;
        });
    }

    /**
     * Compute final aggregate score for an entry across all required judges
     */
    public function getFinalScore(Entry $entry): array
    {
        $competition = $entry->competition;
        $requiredAssignments = JudgeAssignment::where('competition_id', $competition->id)
            ->where('active', true)
            ->where('can_score', true)
            ->where('required_for_completion', true)
            ->get();

        $allScoringAssignments = JudgeAssignment::where('competition_id', $competition->id)
            ->where('active', true)
            ->where('can_score', true)
            ->get();

        $submissions = ScoreSubmission::with(['assignment.user', 'items.criterion', 'items.ratingOption'])
            ->where('entry_id', $entry->id)
            ->where('status', 'submitted')
            ->get();

        $requiredCount = $requiredAssignments->count();
        $submittedRequiredCount = 0;
        $totalSum = 0.0;

        foreach ($requiredAssignments as $req) {
            $sub = $submissions->firstWhere('assignment_id', $req->id);
            if ($sub) {
                $submittedRequiredCount++;
                $totalSum += (float) $sub->score_total;
            }
        }

        // If there are no specifically marked required assignments, use all scoring assignments
        if ($requiredCount === 0) {
            $requiredCount = $allScoringAssignments->count();
            foreach ($submissions as $sub) {
                $submittedRequiredCount++;
                $totalSum += (float) $sub->score_total;
            }
        }

        $isComplete = ($requiredCount > 0) && ($submittedRequiredCount >= $requiredCount);

        return [
            'is_complete' => $isComplete,
            'required_judges' => $requiredCount,
            'submitted_judges' => $submittedRequiredCount,
            'final_score' => round($totalSum, 2),
            'average_score' => $submittedRequiredCount > 0 ? round($totalSum / $submittedRequiredCount, 2) : 0,
            'submissions' => $submissions,
        ];
    }
}
