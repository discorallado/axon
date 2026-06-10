<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_status_histories', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('submission_request_id')->constrained('submission_requests')->cascadeOnDelete();
            $table->foreignId('from_status_id')->nullable()->constrained('submission_statuses')->nullOnDelete();
            $table->foreignId('to_status_id')->constrained('submission_statuses')->restrictOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['submission_request_id', 'created_at'], 'ssh_request_created_idx');
            $table->index('organization_id', 'ssh_org_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_status_histories');
    }
};
