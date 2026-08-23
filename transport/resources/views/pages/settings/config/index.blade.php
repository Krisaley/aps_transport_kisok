<?php

use App\Settings\GeneralSettings;
use Livewire\Component;
use Flux\Flux;

new class extends Component {
    public string $configurationTab = 'general';
    public string $siteName = '';
    public string $googleMapsApiKey = '';
    public string $googleAddressCountry = 'GB';
    public bool $hasGoogleMapsApiKey = false;
    public string $postcodeValidationProvider = 'postcodes_io';

    public function mount(GeneralSettings $settings): void
    {
        $this->siteName = $settings->site_name;
        $this->googleAddressCountry = $settings->google_address_country;
        $this->hasGoogleMapsApiKey = filled($settings->google_maps_api_key);
        $this->postcodeValidationProvider = $settings->postcode_validation_provider;
    }

    public function save(GeneralSettings $settings): void
    {
        $this->validate([
            'siteName' => ['required', 'string', 'max:255'],
            'googleMapsApiKey' => ['nullable','string','max:500'],
            'googleAddressCountry' => ['required','string','size:2'],
            'postcodeValidationProvider'=>['required','in:postcodes_io,google,manual'],
        ]);

        $settings->site_name = $this->siteName;
        $settings->google_address_validation_enabled = $this->postcodeValidationProvider === 'google';
        $settings->google_address_country = strtoupper($this->googleAddressCountry);
        $settings->postcode_validation_provider = $this->postcodeValidationProvider;
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

    <x-pages::settings.layout :contentclass="__('mt-5 w-full max-w-4xl')">
        <div class="space-y-6">
            <div>
                <flux:heading size="lg">Configuration</flux:heading>
                <flux:text>Manage application-wide identity and service integrations.</flux:text>
            </div>

            <div class="flex gap-2 border-b pb-3" role="tablist" aria-label="Configuration sections">
                <flux:button type="button" :variant="$configurationTab === 'general' ? 'primary' : 'ghost'" wire:click="$set('configurationTab', 'general')">General settings</flux:button>
                <flux:button type="button" :variant="$configurationTab === 'services' ? 'primary' : 'ghost'" wire:click="$set('configurationTab', 'services')">Services</flux:button>
            </div>

            <form wire:submit="save" class="space-y-6">
                @if ($configurationTab === 'general')
                    <flux:card class="space-y-5">
                        <div><flux:heading size="md">General settings</flux:heading><flux:text>Application identity shown to users.</flux:text></div>
                        <flux:input wire:model="siteName" label="Site name" description="The name displayed in browser titles and throughout the application." required />
                    </flux:card>
                @else
                    <flux:card class="space-y-5">
                        <div><flux:heading size="md">Address validation</flux:heading><flux:text>Choose the service used when finding and validating addresses.</flux:text></div>
                        <flux:select wire:model.live="postcodeValidationProvider" variant="listbox" label="Address validation service">
                            <flux:select.option value="postcodes_io">Postcodes.io (UK, no API key)</flux:select.option>
                            <flux:select.option value="google">Google Address Validation</flux:select.option>
                            <flux:select.option value="manual">Manual only</flux:select.option>
                        </flux:select>

                        @if ($postcodeValidationProvider === 'postcodes_io')
                            <flux:callout>Postcodes.io requires no API key and validates UK postcodes.</flux:callout>
                        @elseif ($postcodeValidationProvider === 'google')
                            <div class="grid gap-5 md:grid-cols-2">
                                <flux:input wire:model="googleMapsApiKey" type="password" label="Google Maps API key" placeholder="{{ $hasGoogleMapsApiKey ? 'Key saved — enter a value to replace it' : 'Enter API key' }}" description="Stored encrypted. Restrict this key to the required Google address APIs and approved hosts." />
                                <flux:input wire:model="googleAddressCountry" label="Default country code" maxlength="2" />
                            </div>
                        @else
                            <flux:callout variant="warning">Addresses will be entered manually and will not be checked by an external service.</flux:callout>
                        @endif
                    </flux:card>
                @endif

                <div class="flex justify-end border-t pt-4"><flux:button type="submit" variant="primary">Save changes</flux:button></div>
            </form>
        </div>
    </x-pages::settings.layout>

</section>
