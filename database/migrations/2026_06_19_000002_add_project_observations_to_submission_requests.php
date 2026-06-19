<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->text('project_observations')->nullable()->after('internal_notes');
        });
    }

    public function down(): void
    {
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->dropColumn('project_observations');
        });
    }
};
