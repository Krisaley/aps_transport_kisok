<?php

namespace App\Settings;

use Spatie\LaravelSettings\Attributes\ShouldBeEncrypted;
use Spatie\LaravelSettings\Settings;

class GeneralSettings extends Settings
{
    public string $site_name;

    public bool $google_address_validation_enabled;

    #[ShouldBeEncrypted]
    public string $google_maps_api_key;

    public string $google_address_country;

    public static function group(): string
    {
        return 'general';
    }
}
