<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_revisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('execution_id')->constrained('fat_executions')->cascadeOnDelete();
            $table->integer('version')->default(1); // v1, v2, v3...
            $table->text('comments')->nullable();
            $table->boolean('is_active')->default(true); // revisión activa actual
            $table->json('snapshot_data')->nullable(); // snapshot de resultados anteriores para comparación
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['execution_id', 'version']);
            $table->index(['execution_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_revisions');
    }
};
