<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('movement_items', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->foreignId('movement_id')->constrained('movements')->cascadeOnDelete();
            $table->foreignId('equipment_id')->nullable()->constrained('equipment')->nullOnDelete();

            $table->string('stock_number')->nullable();
            $table->string('make')->nullable();
            $table->string('model')->nullable();
            $table->string('description');
            $table->string('serial_number')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->string('movement_action');

            $table->boolean('completed')->default(false);

            $table->unique(['movement_id', 'equipment_id', 'movement_action'], 'movement_items_movement_equipment_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_items');
    }
};
