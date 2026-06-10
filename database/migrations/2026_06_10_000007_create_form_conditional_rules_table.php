<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_conditional_rules', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->unsignedSmallInteger('template_version')->default(1);
            $table->foreignUlid('trigger_question_id')->constrained('form_questions')->cascadeOnDelete();
            $table->string('operator', 20);
            $table->string('trigger_value', 255)->nullable();
            $table->string('action', 20)->default('show');
            $table->string('target_type', 20);
            $table->foreignUlid('target_question_id')->nullable()->constrained('form_questions')->nullOnDelete();
            $table->foreignUlid('target_section_id')->nullable()->constrained('form_sections')->nullOnDelete();
            $table->timestamps();

            $table->index(['form_template_id', 'template_version']);
            $table->index('trigger_question_id');
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_conditional_rules');
    }
};
