<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fat_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('section_id')->constrained('fat_template_sections')->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('fat_template_items')->nullOnDelete();
            $table->string('code'); // ej: "1.1", "1.1.1"
            $table->string('path'); // materialized path: "1/1.1/1.1.1" para consultas jerárquicas eficientes
            $table->text('description');
            $table->enum('result_type', ['ternary', 'numeric', 'text'])->default('ternary');
            $table->json('result_config')->nullable(); // {min, max, unit} para numeric, {options} para text
            $table->boolean('is_required')->default(false);
            $table->boolean('allow_evidence')->default(true);
            $table->integer('depth')->default(1); // nivel de jerarquía (1-4)
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->index(['section_id', 'path']);
            $table->index(['section_id', 'order']);
            $table->index('depth');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fat_template_items');
    }
};
