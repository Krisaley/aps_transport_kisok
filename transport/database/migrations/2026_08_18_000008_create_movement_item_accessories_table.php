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
        Schema::create('movement_item_accessories', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('type');
            $table->string('description');
            $table->string('serial_number')->nullable();
            $table->decimal('quantity', 10, 2)->default(1);
            $table->boolean('completed')->default(false);

            $table->foreignId('movement_item_id')->constrained('movement_items')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_item_accessories');
    }
};
