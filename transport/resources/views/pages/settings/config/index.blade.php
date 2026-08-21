<?php

use App\Settings\GeneralSettings;
use Livewire\Component;
use Flux\Flux;

new class extends Component {
    public string $siteName = '';

    public function mount(GeneralSettings $settings): void
    {
        $this->siteName = $settings->site_name;
    }

    public function save(GeneralSettings $settings): void
    {
        $this->validate([
            'siteName' => ['required', 'string', 'max:255'],
        ]);

        $settings->site_name = $this->siteName;
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

                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary">
                        Save changes
                    </flux:button>
                </div>
            </form>

        </div>

    </x-pages::settings.layout>

</section>