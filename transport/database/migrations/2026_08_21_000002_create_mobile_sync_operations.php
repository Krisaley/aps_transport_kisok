<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('mobile_sync_operations', function (Blueprint $table) {
            $table->id();
            $table->uuid('operation_id')->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('operation_type');
            $table->json('result')->nullable();
            $table->timestamp('processed_at')->useCurrent();
        });
    }

    public function down(): void { Schema::dropIfExists('mobile_sync_operations'); }
};
