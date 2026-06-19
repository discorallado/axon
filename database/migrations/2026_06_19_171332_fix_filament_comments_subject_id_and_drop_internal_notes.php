<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // subject_id fue creado como unsignedBigInteger por el plugin,
        // pero este proyecto usa ULIDs (strings de 26 chars)
        Schema::table('filament_comments', function (Blueprint $table) {
            $table->string('subject_id', 26)->change();
        });

        if (Schema::hasColumn('submission_requests', 'internal_notes')) {
            Schema::table('submission_requests', function (Blueprint $table) {
                $table->dropColumn('internal_notes');
            });
        }
    }

    public function down(): void
    {
        Schema::table('filament_comments', function (Blueprint $table) {
            $table->unsignedBigInteger('subject_id')->change();
        });

        if (! Schema::hasColumn('submission_requests', 'internal_notes')) {
            Schema::table('submission_requests', function (Blueprint $table) {
                $table->text('internal_notes')->nullable();
            });
        }
    }
};
