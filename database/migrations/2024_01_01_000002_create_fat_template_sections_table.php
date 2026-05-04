<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fat_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('template_id')->constrained('fat_templates')->cascadeOnDelete();
            $table->string('code'); // ej: "1", "2", "3"
            $table->string('title');
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['template_id', 'code']);
            $table->index(['template_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fat_template_sections');
    }
};
