<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('submission_items', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->foreignUlid('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignUlid('submission_request_id')->constrained('submission_requests')->cascadeOnDelete();

            // Identificación
            $table->string('label', 150)->default('Tablero');
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->string('delivery_type', 30)->nullable();
            $table->string('is_new_installation', 30)->nullable();
            $table->string('board_type', 50)->nullable();
            $table->string('other_board_type', 255)->nullable();
            $table->text('board_function')->nullable();

            // Cargas
            $table->text('loads_to_feed')->nullable();
            $table->unsignedSmallInteger('number_of_circuits')->nullable();
            $table->string('load_list_file_path', 500)->nullable();

            // Instalación
            $table->string('location_type', 30)->nullable();
            $table->json('special_environment')->nullable();
            $table->string('other_special_environment', 255)->nullable();
            $table->string('ip_rating', 10)->nullable();
            $table->string('ik_rating', 10)->nullable();
            $table->string('mounting_type', 30)->nullable();
            $table->boolean('has_dimension_restrictions')->default(false);
            $table->unsignedSmallInteger('max_height')->nullable();
            $table->unsignedSmallInteger('max_width')->nullable();
            $table->unsignedSmallInteger('max_depth')->nullable();
            $table->text('additional_installation_conditions')->nullable();

            // Eléctrico
            $table->string('supply_voltage', 10)->nullable();
            $table->string('supply_voltage_other', 20)->nullable();
            $table->string('electrical_system', 20)->nullable();
            $table->string('electrical_system_other', 100)->nullable();
            $table->decimal('estimated_power', 10, 2)->nullable();
            $table->string('power_unit', 10)->nullable()->default('kW');
            $table->decimal('nominal_current', 10, 2)->nullable();
            $table->string('frequency', 10)->nullable();
            $table->string('other_frequency', 20)->nullable();
            $table->json('required_protections')->nullable();
            $table->json('preferred_brands')->nullable();

            // Constructivo
            $table->string('cabinet_material', 50)->nullable();
            $table->string('special_color', 10)->nullable();
            $table->string('ventilation_type', 20)->nullable();
            $table->string('future_expansion', 10)->nullable();

            // Archivos del ítem
            $table->string('unilineal_diagram_path', 500)->nullable();
            $table->string('mechanical_plans_path', 500)->nullable();

            $table->text('additional_observations')->nullable();

            $table->timestamps();

            $table->index(['submission_request_id', 'sort_order']);
            $table->index('organization_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('submission_items');
    }
};
