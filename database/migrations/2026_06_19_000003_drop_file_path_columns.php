<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->dropColumn(['technical_specs_path', 'site_photos_paths']);
        });

        Schema::table('submission_items', function (Blueprint $table) {
            $table->dropColumn(['load_list_file_path', 'unilineal_diagram_path', 'mechanical_plans_path']);
        });
    }

    public function down(): void
    {
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->string('technical_specs_path', 500)->nullable();
            $table->json('site_photos_paths')->nullable();
        });

        Schema::table('submission_items', function (Blueprint $table) {
            $table->string('load_list_file_path', 500)->nullable();
            $table->string('unilineal_diagram_path', 500)->nullable();
            $table->string('mechanical_plans_path', 500)->nullable();
        });
    }
};
