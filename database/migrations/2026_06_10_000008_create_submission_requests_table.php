<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_requests', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('form_template_id')->constrained('form_templates')->restrictOnDelete();
            $table->unsignedSmallInteger('template_version');
            $table->string('reference_code', 20)->unique();
            $table->foreignId('status_id')->constrained('submission_statuses')->restrictOnDelete();
            $table->string('submitter_name', 150);
            $table->string('submitter_email', 255);
            $table->string('submitter_phone', 30)->nullable();
            $table->string('submitter_company', 150)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 300)->nullable();
            $table->timestamp('submitted_at');
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->text('internal_notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status_id']);
            $table->index(['form_template_id', 'submitted_at']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_requests');
    }
};
