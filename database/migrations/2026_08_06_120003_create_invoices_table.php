<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained()->cascadeOnDelete();
            $table->string('type', 10);
            $table->foreignUlid('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('project_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUlid('purchase_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('code');
            $table->string('number')->nullable();
            $table->date('date');
            $table->date('due_date');
            $table->string('currency', 3)->default('CLP');
            $table->decimal('amount_net', 15, 2)->unsigned();
            $table->decimal('tax_amount', 15, 2)->unsigned()->default(0);
            $table->decimal('amount_total', 15, 2)->unsigned();
            $table->string('status', 20)->default('pendiente');
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['organization_id', 'code']);
            $table->index('organization_id');
            $table->index('status');
            $table->index('type');
            $table->index('project_id');
            $table->index('client_id');
            $table->index('supplier_id');
            $table->index('due_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
