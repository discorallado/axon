<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('execution_item_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('revision_id')->constrained('execution_revisions')->cascadeOnDelete();
            $table->foreignId('template_item_id')->constrained('fat_template_items')->restrictOnDelete();
            $table->enum('result', ['C', 'NC', 'NA', null])->nullable()->comment('C=Conforme, NC=No Conforme, NA=No Aplica');
            $table->text('observations')->nullable();
            $table->json('numeric_value')->nullable(); // {value, unit, min, max} para tipo numeric
            $table->text('text_value')->nullable(); // para tipo text
            $table->boolean('has_evidence')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
            
            $table->unique(['revision_id', 'template_item_id']);
            $table->index(['revision_id', 'result']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('execution_item_results');
    }
};
