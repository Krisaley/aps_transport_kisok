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
        Schema::create('sites', function (Blueprint $table) {
            $table->id();
            $table->timestamps();

            $table->string('name');                         // Access Platform Sales
            $table->string('address_line_1');               // Leewood Business Park
            $table->string('address_line_2')->nullable();   // Norawood Way
            $table->string('town')->nullable();             // Upton
            $table->string('county')->nullable();           // Huntingdon
            $table->string('postcode');                     // PE28 5YQ
            $table->string('what_3_words')->nullable();     // canny.petrified.bought
            $table->string('address_code')->nullable();     // ACCESSPLATFORMSALES_LEEWOODBUSINESSPARK_PE285YQ

            $table->unique(['address_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sites');
    }
};
