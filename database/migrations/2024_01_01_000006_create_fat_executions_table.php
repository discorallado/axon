<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fat_executions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ej: PB-001-FAT-2026-001
            $table->foreignId('project_id')->constrained('projects')->cascadeOnDelete();
            $table->foreignId('template_id')->constrained('fat_templates')->restrictOnDelete();
            $table->enum('status', ['draft', 'pending_review', 'approved', 'rejected', 'archived'])->default('draft');
            $table->date('execution_date')->nullable();
            $table->text('comments')->nullable();
            $table->json('metadata')->nullable(); // datos adicionales del contexto de ejecución
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['project_id', 'status']);
            $table->index(['template_id', 'status']);
            $table->index('execution_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fat_executions');
    }
};
