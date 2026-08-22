<?php

use App\Settings\GeneralSettings;
use Livewire\Component;
use Flux\Flux;

new class extends Component {
    public string $siteName = '';
    public bool $googleAddressValidationEnabled = false;
    public string $googleMapsApiKey = '';
    public string $googleAddressCountry = 'GB';
    public bool $hasGoogleMapsApiKey = false;

    public function mount(GeneralSettings $settings): void
    {
        $this->siteName = $settings->site_name;
        $this->googleAddressValidationEnabled = $settings->google_address_validation_enabled;
        $this->googleAddressCountry = $settings->google_address_country;
        $this->hasGoogleMapsApiKey = filled($settings->google_maps_api_key);
    }

    public function save(GeneralSettings $settings): void
    {
        $this->validate([
            'siteName' => ['required', 'string', 'max:255'],
            'googleAddressValidationEnabled' => ['boolean'],
            'googleMapsApiKey' => ['nullable','string','max:500'],
            'googleAddressCountry' => ['required','string','size:2'],
        ]);

        $settings->site_name = $this->siteName;
        $settings->google_address_validation_enabled = $this->googleAddressValidationEnabled;
        $settings->google_address_country = strtoupper($this->googleAddressCountry);
        if (filled($this->googleMapsApiKey)) $settings->google_maps_api_key = $this->googleMapsApiKey;
        $settings->save();

        Flux::toast(
            text: 'General settings updated successfully',
            variant: 'success',
        );
    }
};
?>
<section class="w-full">
    @include('partials.settings-heading',[
        'section'   => 'Settings',
    ])

    <flux:heading class="sr-only">{{ __('Manage General Settings') }}</flux:heading>

    <x-pages::settings.layout
        :contentclass="__('mt-5 w-full max-w-3xl')"
        >
    
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Edit Role') }}{{ $siteName }}</flux:heading>
            </div>
            <form wire:submit="save" class="space-y-6">
                <flux:input
                    wire:model="siteName"
                    label="Site name"
                    description="The name displayed in browser titles and throughout the application."
                    required
                />
                <flux:separator/><flux:heading size="md">Google address validation</flux:heading><flux:switch wire:model="googleAddressValidationEnabled" label="Enable Google address validation"/><flux:input wire:model="googleMapsApiKey" type="password" label="Google Maps API key" placeholder="{{ $hasGoogleMapsApiKey ? 'Key saved — enter a value to replace it' : 'Enter API key' }}" description="Stored encrypted. Restrict this key to the required Google address APIs and approved hosts."/><flux:input wire:model="googleAddressCountry" label="Default country code" maxlength="2"/>

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">
                        Save changes
                    </flux:button>
                </div>
            </form>

        </div>

    </x-pages::settings.layout>

</section>
