<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->string('trading_name')->nullable()->after('name');
            $table->string('registration_number')->nullable()->after('document_prefix');
            $table->string('vat_number')->nullable()->after('registration_number');
            $table->string('brand_primary_color', 7)->nullable()->after('logo_path');
        });
        Schema::table('customers', fn (Blueprint $table) => $table->foreignId('home_site_id')->nullable()->after('company_id')->constrained('sites')->nullOnDelete());
        Schema::table('sites', function (Blueprint $table) {
            $table->string('google_place_id')->nullable()->unique()->after('address_code');
            $table->text('access_instructions')->nullable()->after('google_place_id');
        });
        Schema::table('vehicles', fn (Blueprint $table) => $table->decimal('capacity_tonnes', 6, 2)->nullable()->after('registration'));
        Schema::table('movement_actions', function (Blueprint $table) {
            $table->string('contact_name')->nullable()->after('site_id');
            $table->string('contact_number')->nullable()->after('contact_name');
            $table->text('access_instructions')->nullable()->after('contact_number');
        });
        Schema::table('movements', fn (Blueprint $table) => $table->text('driver_notes')->nullable()->after('notes'));
    }

    public function down(): void
    {
        Schema::table('movements', fn (Blueprint $table) => $table->dropColumn('driver_notes'));
        Schema::table('movement_actions', fn (Blueprint $table) => $table->dropColumn(['contact_name', 'contact_number', 'access_instructions']));
        Schema::table('vehicles', fn (Blueprint $table) => $table->dropColumn('capacity_tonnes'));
        Schema::table('sites', function (Blueprint $table) {
            $table->dropColumn('access_instructions');
            $table->dropUnique(['google_place_id']);
            $table->dropColumn('google_place_id');
        });
        Schema::table('customers', fn (Blueprint $table) => $table->dropConstrainedForeignId('home_site_id'));
        Schema::table('companies', fn (Blueprint $table) => $table->dropColumn(['trading_name', 'registration_number', 'vat_number', 'brand_primary_color']));
    }
};
