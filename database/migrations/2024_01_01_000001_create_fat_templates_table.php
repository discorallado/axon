<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fat_templates', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // ej: CSE-FAT-002
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // ej: 'Tableros Eléctricos', 'Instrumentación'
            $table->json('workflow_config')->nullable(); // configuración de workflow por plantilla
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['category', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fat_templates');
    }
};
