<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->string('position')->nullable()->after('status');
            $table->index(['activity_id', 'status', 'position'], 'tasks_activity_status_position_index');
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropIndex('tasks_activity_status_position_index');
            $table->dropColumn('position');
        });
    }
};
