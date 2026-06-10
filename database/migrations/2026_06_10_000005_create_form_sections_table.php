<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_sections', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('form_template_id')->constrained('form_templates')->cascadeOnDelete();
            $table->unsignedSmallInteger('template_version')->default(1);
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_repeatable')->default(false);
            $table->timestamps();

            $table->index(['form_template_id', 'template_version', 'sort_order']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_sections');
    }
};
