<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_result_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_result_id')->constrained('execution_item_results')->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field_changed'); // ej: 'result', 'observations', 'numeric_value'
            $table->text('old_value')->nullable(); // valor anterior en JSON
            $table->text('new_value')->nullable(); // valor nuevo en JSON
            $table->ipAddress('changed_from_ip')->nullable();
            $table->timestamps();
            
            $table->index(['item_result_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_result_history');
    }
};
