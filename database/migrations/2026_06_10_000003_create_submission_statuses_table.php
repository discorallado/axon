<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('name', 60);
            $table->string('slug', 40);
            $table->string('color', 7)->default('#6b7280');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->boolean('is_initial')->default(false);
            $table->boolean('is_terminal')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'slug']);
            $table->index(['organization_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_statuses');
    }
};
