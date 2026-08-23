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

        DB::table('sites')->whereNotNull('company_id')->orderBy('id')->each(function ($site) {
            DB::table('company_site')->insertOrIgnore(['company_id' => $site->company_id, 'site_id' => $site->id]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_site');
    }
};
