<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->integer('year')->default(2026);
            $table->dateTime('start_at')->nullable();
            $table->dateTime('end_at')->nullable();
            $table->string('status')->default('active'); // draft, active, completed, archived
            $table->timestamps();
        });

        Schema::create('event_users', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('event_role')->default('judge'); // master, judge, admin
            $table->timestamps();
            $table->unique(['event_id', 'user_id']);
        });

        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->string('code')->nullable();
            $table->timestamps();
        });

        Schema::create('grade_levels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('code');
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->foreignId('grade_id')->nullable()->constrained('grade_levels')->nullOnDelete();
            $table->string('participant_code');
            $table->string('name');
            $table->string('gender', 10)->nullable(); // L / P
            $table->date('birth_date')->nullable();
            $table->string('phone')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->unique(['event_id', 'participant_code']);
        });

        Schema::create('competitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('name');
            $table->string('format')->default('individual'); // individual, team
            $table->text('eligibility_rule')->nullable();
            $table->string('location')->nullable();
            $table->string('status')->default('ready'); // draft, ready, ongoing, locked, published, archived
            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('team_code');
            $table->string('name');
            $table->timestamps();

            $table->unique(['event_id', 'competition_id', 'team_code']);
        });

        Schema::create('team_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained('teams')->cascadeOnDelete();
            $table->foreignId('participant_id')->constrained('participants')->cascadeOnDelete();
            $table->string('membership_status')->default('active');
            $table->timestamps();

            $table->unique(['team_id', 'participant_id']);
        });

        Schema::create('entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->foreignId('participant_id')->nullable()->constrained('participants')->cascadeOnDelete();
            $table->foreignId('team_id')->nullable()->constrained('teams')->cascadeOnDelete();
            $table->integer('queue_number')->nullable();
            $table->string('status')->default('registered'); // registered, queued, called, performing, completed, disqualified, no_show
            $table->timestamps();

            $table->unique(['competition_id', 'participant_id']);
            $table->unique(['competition_id', 'team_id']);
        });

        Schema::create('qr_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->string('entity_type'); // participant, team
            $table->unsignedBigInteger('entity_id');
            $table->string('token_hash')->unique();
            $table->string('token_plain');
            $table->timestamp('issued_at')->useCurrent();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['entity_type', 'entity_id']);
        });

        Schema::create('judge_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->boolean('can_view_participants')->default(true);
            $table->boolean('can_view_scores')->default(true);
            $table->boolean('can_view_peer_scores')->default(false);
            $table->boolean('can_score')->default(true);
            $table->boolean('active')->default(true);
            $table->boolean('required_for_completion')->default(true);
            $table->timestamps();

            $table->unique(['event_id', 'user_id', 'competition_id']);
        });

        Schema::create('rubrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->integer('version')->default(1);
            $table->string('status')->default('active'); // draft, active, archived
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();
        });

        Schema::create('rubric_criteria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('rubrics')->cascadeOnDelete();
            $table->string('title');
            $table->decimal('weight_pct', 5, 2);
            $table->integer('sort_order')->default(1);
            $table->boolean('required')->default(true);
            $table->timestamps();
        });

        Schema::create('rating_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('rubric_id')->constrained('rubrics')->cascadeOnDelete();
            $table->string('label'); // Baik, Cukup, Kurang
            $table->decimal('numeric_value', 6, 2); // 100.00, 75.00, 50.00
            $table->integer('sort_order')->default(1);
            $table->timestamps();
        });

        Schema::create('score_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('entry_id')->constrained('entries')->cascadeOnDelete();
            $table->foreignId('assignment_id')->constrained('judge_assignments')->cascadeOnDelete();
            $table->foreignId('rubric_id')->constrained('rubrics')->cascadeOnDelete();
            $table->string('status')->default('draft'); // draft, submitted, locked
            $table->decimal('score_total', 6, 2)->default(0);
            $table->integer('revision')->default(1);
            $table->text('notes')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('locked_at')->nullable();
            $table->timestamps();

            $table->unique(['entry_id', 'assignment_id', 'rubric_id']);
        });

        Schema::create('score_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('submission_id')->constrained('score_submissions')->cascadeOnDelete();
            $table->foreignId('criterion_id')->constrained('rubric_criteria')->cascadeOnDelete();
            $table->foreignId('rating_option_id')->nullable()->constrained('rating_options')->nullOnDelete();
            $table->decimal('numeric_value', 6, 2);
            $table->decimal('weighted_score', 6, 2);
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('score_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('actor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submission_id')->nullable()->constrained('score_submissions')->nullOnDelete();
            $table->foreignId('entry_id')->nullable()->constrained('entries')->nullOnDelete();
            $table->string('action'); // create, update, lock, unlock, publish, override
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->text('reason')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('result_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('events')->cascadeOnDelete();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->integer('version')->default(1);
            $table->json('payload_snapshot');
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->useCurrent();
            $table->timestamps();
        });

        Schema::create('competition_awards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('competition_id')->constrained('competitions')->cascadeOnDelete();
            $table->integer('rank_position'); // 1 = Juara 1, 2 = Juara 2, etc.
            $table->string('rank_label'); // Juara 1, Juara 2, Juara 3, Juara Harapan 1
            $table->integer('points_for_group')->default(0);
            $table->timestamps();
        });

        Schema::create('event_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->unique()->constrained('events')->cascadeOnDelete();
            $table->boolean('enable_group_champion')->default(true);
            $table->json('config_json')->nullable();
            $table->timestamps();
        });

        Schema::create('sync_operations', function (Blueprint $table) {
            $table->id();
            $table->string('client_uuid')->unique();
            $table->foreignId('actor_id')->constrained('users')->cascadeOnDelete();
            $table->integer('resource_version')->default(1);
            $table->string('request_digest')->nullable();
            $table->string('status')->default('synced'); // pending, synced, rejected, conflict
            $table->text('rejection_reason')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_operations');
        Schema::dropIfExists('event_settings');
        Schema::dropIfExists('competition_awards');
        Schema::dropIfExists('result_publications');
        Schema::dropIfExists('score_audit_logs');
        Schema::dropIfExists('score_items');
        Schema::dropIfExists('score_submissions');
        Schema::dropIfExists('rating_options');
        Schema::dropIfExists('rubric_criteria');
        Schema::dropIfExists('rubrics');
        Schema::dropIfExists('judge_assignments');
        Schema::dropIfExists('qr_tokens');
        Schema::dropIfExists('entries');
        Schema::dropIfExists('team_members');
        Schema::dropIfExists('teams');
        Schema::dropIfExists('competitions');
        Schema::dropIfExists('participants');
        Schema::dropIfExists('grade_levels');
        Schema::dropIfExists('groups');
        Schema::dropIfExists('event_users');
        Schema::dropIfExists('events');
    }
};
