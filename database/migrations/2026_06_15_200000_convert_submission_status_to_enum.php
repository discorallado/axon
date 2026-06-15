<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- submission_requests: status_id (FK bigint) → status (string) ---
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->dropForeign(['status_id']);
            $table->dropColumn('status_id');
        });
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->string('status', 30)->default('nueva')->after('reference_code');
        });

        // --- submission_status_histories: FKs bigint → strings ---
        Schema::table('submission_status_histories', function (Blueprint $table) {
            $table->dropForeign(['from_status_id']);
            $table->dropForeign(['to_status_id']);
            $table->dropColumn(['from_status_id', 'to_status_id']);
        });
        Schema::table('submission_status_histories', function (Blueprint $table) {
            $table->string('from_status', 30)->nullable()->after('submission_request_id');
            $table->string('to_status', 30)->after('from_status');
        });

        Schema::dropIfExists('submission_statuses');
    }

    public function down(): void
    {
        Schema::create('submission_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('organization_id', 26);
            $table->string('name');
            $table->string('slug', 50);
            $table->string('color', 20)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();
        });

        Schema::table('submission_status_histories', function (Blueprint $table) {
            $table->dropColumn(['from_status', 'to_status']);
        });
        Schema::table('submission_status_histories', function (Blueprint $table) {
            $table->foreignId('from_status_id')->nullable()->constrained('submission_statuses')->nullOnDelete();
            $table->foreignId('to_status_id')->constrained('submission_statuses')->restrictOnDelete();
        });

        Schema::table('submission_requests', function (Blueprint $table) {
            $table->dropColumn('status');
        });
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->foreignId('status_id')->constrained('submission_statuses')->restrictOnDelete();
        });
    }
};
