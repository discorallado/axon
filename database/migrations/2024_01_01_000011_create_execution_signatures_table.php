<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revision_id')->constrained('execution_revisions')->cascadeOnDelete();
            $table->foreignId('role_signature_id')->constrained('template_role_signatures')->restrictOnDelete();
            $table->foreignId('signed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('signer_name')->nullable(); // para firmas externas
            $table->string('signer_title')->nullable(); // cargo para firmas externas
            $table->string('signature_file_path')->nullable(); // ruta de firma escaneada (externas)
            $table->ipAddress('signed_from_ip')->nullable();
            $table->timestamp('signed_at');
            $table->text('comments')->nullable();
            $table->timestamps();
            
            $table->index(['revision_id', 'role_signature_id']);
            $table->index('signed_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_signatures');
    }
};
