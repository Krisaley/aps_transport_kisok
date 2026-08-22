<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sites', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->after('company_id')->constrained()->nullOnDelete();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('home_site_id')->nullable()->after('address')->constrained('sites')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('companies', fn (Blueprint $table) => $table->dropConstrainedForeignId('home_site_id'));
        Schema::table('sites', fn (Blueprint $table) => $table->dropConstrainedForeignId('customer_id'));
    }
};
