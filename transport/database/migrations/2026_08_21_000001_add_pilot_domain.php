<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('name');
            $table->text('address')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('document_prefix')->nullable();
            $table->unsignedBigInteger('next_document_number')->default(1);
            $table->string('logo_path')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
        });

        Schema::create('company_user', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->primary(['company_id', 'user_id']);
        });

        foreach (['customers', 'sites', 'equipment', 'vehicles', 'movements'] as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->foreignId('company_id')->nullable()->after('id')->constrained()->restrictOnDelete();
            });
        }

        Schema::table('movements', function (Blueprint $table) {
            $table->dateTime('schedule_start')->nullable()->after('planned_date');
            $table->dateTime('schedule_end')->nullable()->after('schedule_start');
            $table->unsignedSmallInteger('estimated_minutes')->nullable()->after('schedule_end');
            $table->string('schedule_window')->nullable()->after('estimated_minutes');
            $table->text('status_reason')->nullable()->after('status');
            $table->unsignedInteger('lock_version')->default(1);
        });

        Schema::create('movement_actions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence')->default(1);
            $table->string('action_type');
            $table->foreignId('site_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('driver_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->foreignId('vehicle_id')->nullable()->constrained()->restrictOnDelete();
            $table->dateTime('schedule_start')->nullable();
            $table->dateTime('schedule_end')->nullable();
            $table->dateTime('arrived_at')->nullable();
            $table->dateTime('departed_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['movement_id', 'sequence']);
            $table->index(['driver_id', 'schedule_start', 'schedule_end']);
            $table->index(['vehicle_id', 'schedule_start', 'schedule_end']);
        });

        Schema::table('movement_items', function (Blueprint $table) {
            $table->foreignId('movement_action_id')->nullable()->after('movement_id')->constrained('movement_actions')->nullOnDelete();
            $table->boolean('is_temporary')->default(false)->after('completed');
            $table->text('condition_notes')->nullable()->after('is_temporary');
        });

        Schema::create('movement_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('movement_id')->constrained()->cascadeOnDelete();
            $table->string('from_status')->nullable();
            $table->string('to_status');
            $table->text('reason')->nullable();
            $table->foreignId('changed_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movement_status_history');
        Schema::table('movement_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('movement_action_id');
            $table->dropColumn(['is_temporary', 'condition_notes']);
        });
        Schema::dropIfExists('movement_actions');
        Schema::table('movements', function (Blueprint $table) {
            $table->dropColumn(['schedule_start', 'schedule_end', 'estimated_minutes', 'schedule_window', 'status_reason', 'lock_version']);
        });
        foreach (['customers', 'sites', 'equipment', 'vehicles', 'movements'] as $tableName) {
            Schema::table($tableName, fn (Blueprint $table) => $table->dropConstrainedForeignId('company_id'));
        }
        Schema::dropIfExists('company_user');
        Schema::table('users', fn (Blueprint $table) => $table->dropConstrainedForeignId('company_id'));
        Schema::dropIfExists('companies');
    }
};
