<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('customer_site')) {
            Schema::create('customer_site', function (Blueprint $table) {
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('site_id')->constrained()->cascadeOnDelete();
                $table->primary(['customer_id', 'site_id']);
            });
        }

        if (! Schema::hasTable('customer_equipment')) {
            Schema::create('customer_equipment', function (Blueprint $table) {
                $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
                $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
                $table->primary(['customer_id', 'equipment_id']);
            });
        }

        if (! Schema::hasColumn('customers', 'trading_name')) {
            Schema::table('customers', fn (Blueprint $table) => $table->string('trading_name')->nullable()->after('name'));
        }

        DB::table('sites')->whereNotNull('customer_id')->orderBy('id')->each(function ($site) {
            DB::table('customer_site')->insertOrIgnore(['customer_id' => $site->customer_id, 'site_id' => $site->id]);
        });
    }

    public function down(): void
    {
        // This repair migration is intentionally non-destructive on rollback because
        // installations that ran the preceding migration may already own these objects.
    }
};
