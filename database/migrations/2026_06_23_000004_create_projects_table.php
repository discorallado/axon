<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('projects', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('client_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->foreignUlid('submission_request_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('status_id')->constrained('project_statuses');
            $table->foreignId('manager_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code')->unique();
            $table->string('code_prefix', 10);
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('priority')->default('media');
            $table->string('color', 7)->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('organization_id');
            $table->index('client_id');
            $table->index('status_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('projects');
    }
};
