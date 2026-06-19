<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Orden: primero las tablas que tienen FKs que apuntan a otras de esta lista
        Schema::disableForeignKeyConstraints();

        Schema::dropIfExists('form_conditional_rules');
        Schema::dropIfExists('submission_answers');
        Schema::dropIfExists('form_questions');
        Schema::dropIfExists('form_sections');
        Schema::dropIfExists('form_templates');
        Schema::dropIfExists('comments');

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        // Recrea las tablas vacías para que el rollback no deje el esquema roto
        Schema::create('comments', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('commentable_type');
            $table->string('commentable_id');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->timestamps();
            $table->index(['commentable_type', 'commentable_id']);
        });

        Schema::create('form_templates', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255)->unique();
            $table->text('description')->nullable();
            $table->string('view_type', 50)->default('wizard');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('current_version')->default(1);
            $table->json('settings')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('form_sections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('form_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('form_section_id')->constrained('form_sections')->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('label', 255);
            $table->string('type', 50);
            $table->boolean('required')->default(false);
            $table->json('options')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('form_conditional_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('form_question_id')->constrained('form_questions')->cascadeOnDelete();
            $table->string('target_question_key', 100);
            $table->string('condition_value', 255);
            $table->string('action', 50)->default('show');
            $table->timestamps();
        });

        Schema::create('submission_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('submission_request_id')->constrained('submission_requests')->cascadeOnDelete();
            $table->string('form_question_id')->nullable();
            $table->string('question_key', 100)->nullable();
            $table->string('question_label', 255)->nullable();
            $table->text('value')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();
        });
    }
};
