<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->string('project_name', 255)->nullable()->after('form_template_id');
            $table->string('installation_location', 255)->nullable()->after('project_name');
            $table->string('cost_center', 100)->nullable()->after('installation_location');
            $table->date('desired_delivery_date')->nullable()->after('cost_center');
            $table->string('engineering_by', 30)->nullable()->after('desired_delivery_date');
            $table->string('technical_specs_path', 500)->nullable()->after('internal_notes');
            $table->json('site_photos_paths')->nullable()->after('technical_specs_path');
            $table->json('raw_data')->nullable()->after('site_photos_paths');
        });

        // Make submitted_at nullable so drafts can be saved before submission
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->dropColumn([
                'project_name',
                'installation_location',
                'cost_center',
                'desired_delivery_date',
                'engineering_by',
                'technical_specs_path',
                'site_photos_paths',
                'raw_data',
            ]);
        });

        Schema::table('submission_requests', function (Blueprint $table) {
            $table->timestamp('submitted_at')->nullable(false)->change();
        });
    }
};
