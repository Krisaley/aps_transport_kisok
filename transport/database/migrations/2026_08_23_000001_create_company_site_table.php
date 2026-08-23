<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_site', function (Blueprint $table) {
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->primary(['company_id', 'site_id']);
        });
        Schema::create('customer_site', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('site_id')->constrained()->cascadeOnDelete();
            $table->primary(['customer_id', 'site_id']);
        });
        Schema::create('customer_equipment', function (Blueprint $table) {
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('equipment_id')->constrained('equipment')->cascadeOnDelete();
            $table->primary(['customer_id', 'equipment_id']);
        });
        Schema::table('customers', fn (Blueprint $table) => $table->string('trading_name')->nullable()->after('name'));

        DB::table('sites')->whereNotNull('company_id')->orderBy('id')->each(function ($site) {
            DB::table('company_site')->insertOrIgnore(['company_id' => $site->company_id, 'site_id' => $site->id]);
        });
        DB::table('sites')->whereNotNull('customer_id')->orderBy('id')->each(function ($site) {
            DB::table('customer_site')->insertOrIgnore(['customer_id' => $site->customer_id, 'site_id' => $site->id]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_site');
        Schema::dropIfExists('customer_equipment');
        Schema::dropIfExists('company_site');
        Schema::table('customers', fn (Blueprint $table) => $table->dropColumn('trading_name'));
    }
};
