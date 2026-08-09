<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignUlid('supplier_id')->constrained()->restrictOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('number')->nullable();
            $table->date('date');
            $table->string('currency', 3)->default('CLP');
            $table->decimal('amount_net', 15, 2)->unsigned();
            $table->decimal('tax_amount', 15, 2)->unsigned()->default(0);
            $table->decimal('amount_total', 15, 2)->unsigned();
            $table->string('status', 20)->default('borrador');
            $table->text('description')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index('organization_id');
            $table->index('status');
            $table->index('project_id');
            $table->index('supplier_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
