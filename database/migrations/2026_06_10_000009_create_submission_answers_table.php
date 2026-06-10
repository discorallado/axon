<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_answers', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('submission_request_id')->constrained('submission_requests')->cascadeOnDelete();
            $table->foreignUlid('form_question_id')->constrained('form_questions')->restrictOnDelete();
            $table->string('question_key', 80);
            $table->string('question_label', 255);
            $table->text('value')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->index(['submission_request_id', 'question_key']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_answers');
    }
};
