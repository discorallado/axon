<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('color')->default('#6b7280');
            $table->unsignedInteger('order')->default(0);
            $table->boolean('is_completed')->default(false);
            $table->timestamps();

            $table->index(['organization_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_statuses');
    }
};
