<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_questions', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->foreignUlid('form_section_id')->constrained('form_sections')->cascadeOnDelete();
            $table->unsignedSmallInteger('template_version')->default(1);
            $table->string('key', 80);
            $table->string('label', 255);
            $table->string('type', 30)->default('text');
            $table->json('options')->nullable();
            $table->string('placeholder', 255)->nullable();
            $table->string('help_text', 500)->nullable();
            $table->boolean('is_required')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->json('validation_rules')->nullable();
            $table->timestamps();

            $table->unique(['form_template_id', 'template_version', 'key']);
            $table->index(['form_section_id', 'template_version', 'sort_order']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_questions');
    }
};
