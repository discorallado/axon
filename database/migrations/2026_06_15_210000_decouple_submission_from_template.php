<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->dropForeign(['form_template_id']);
            $table->dropIndex(['form_template_id', 'submitted_at']);
        });

        Schema::table('submission_requests', function (Blueprint $table) {
            $table->string('form_template_id', 26)->nullable()->change();
            $table->dropColumn('template_version');
        });
    }

    public function down(): void
    {
        Schema::table('submission_requests', function (Blueprint $table) {
            $table->unsignedSmallInteger('template_version')->default(1)->after('form_template_id');
            $table->string('form_template_id', 26)->nullable(false)->change();
        });

        Schema::table('submission_requests', function (Blueprint $table) {
            $table->foreign('form_template_id')->references('id')->on('form_templates')->restrictOnDelete();
            $table->index(['form_template_id', 'submitted_at']);
        });
    }
};
