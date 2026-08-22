<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('general.google_address_validation_enabled', false);
        $this->migrator->addEncrypted('general.google_maps_api_key', '');
        $this->migrator->add('general.google_address_country', 'GB');
    }
};
