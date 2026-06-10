<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_status_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('changed_by')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('submission_status_histories', function (Blueprint $table) {
            $table->unsignedBigInteger('changed_by')->nullable(false)->change();
        });
    }
};
