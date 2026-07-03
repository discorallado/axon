<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_links', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('organization_id');
            $table->string('project_id');
            $table->string('source_id');   // ULID (task o activity con prefijo act-)
            $table->string('target_id');
            $table->unsignedTinyInteger('type')->default(0); // 0=FS 1=SS 2=FF 3=SF
            $table->timestamps();

            $table->index(['project_id']);
            $table->index(['organization_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_links');
    }
};
