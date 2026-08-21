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
        Schema::create('movements', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->softDeletes();

            $table->string('status')->default('draft');
            $table->string('movement_type');
            $table->text('notes')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->date('planned_date')->nullable();
            $table->string('reference');
            $table->string('advice_note')->nullable();
            $table->string('job_number')->nullable();
            $table->string('contact_name')->nullable();
            $table->string('contact_number')->nullable();

            $table->foreignId('driver_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained('vehicles')->restrictOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->restrictOnDelete();
            $table->foreignId('delivery_site_id')->constrained('sites')->restrictOnDelete();
            $table->foreignId('collection_site_id')->constrained('sites')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('updated_by')->constrained('users')->restrictOnDelete();
            $table->foreignId('completed_by')->nullable()->constrained('users')->restrictOnDelete();

            $table->unique(['reference']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movements');
    }
};

//
