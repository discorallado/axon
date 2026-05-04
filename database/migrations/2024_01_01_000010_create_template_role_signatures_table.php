<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('template_role_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('fat_templates')->cascadeOnDelete();
            $table->string('role_name'); // ej: 'supervisor', 'calidad', 'cliente'
            $table->string('role_display_name'); // ej: 'Supervisor Eléctrico', 'Control de Calidad'
            $table->integer('approval_order')->default(1); // orden de firma requerido
            $table->boolean('is_required')->default(true);
            $table->enum('signer_type', ['internal', 'external'])->default('internal');
            $table->json('config')->nullable(); // configuración adicional
            $table->timestamps();
            
            $table->index(['template_id', 'approval_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('template_role_signatures');
    }
};
