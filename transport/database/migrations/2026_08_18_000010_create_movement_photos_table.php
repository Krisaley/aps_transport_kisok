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
        Schema::create('movement_photos', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('photo_type')->nullable(); // collection, delivery, damage, accessory, signature, other
            $table->string('disk')->default('local');
            $table->string('path');
            $table->string('original_filename')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('taken_at')->nullable();

            $table->foreignId('movement_id')->constrained('movements')->restrictOnDelete();
            $table->foreignId('movement_item_id')->nullable()->constrained('movement_items')->nullOnDelete();
            $table->foreignId('uploaded_by')->constrained('users')->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movement_photos');
    }
};
