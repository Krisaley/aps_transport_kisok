<?php

use App\Models\Make;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Create Make')] class extends Component
{
    public string $name = '';

    public function save(): void
    {
        $this->validate([
            'name' => ['required', 'string', 'max:255', 'unique:makes,name'],
        ]);

        $make = Make::create([
            'name' => $this->name,
        ]);

        Flux::toast(
            text: 'Make created successfully',
            variant: 'success',
        );

        $this->redirectRoute(
            'setup.makes.models',
            $make,
            navigate: true
        );
    }
};
?>

<section class="w-full">
    @include('partials.setup-heading', [
        'section' => 'Makes & Models',
    ])

    <flux:heading class="sr-only">{{ __('Create Make') }}</flux:heading>

    <x-pages::setup.layout
        :contentclass="__('mt-5 w-full max-w-3xl')"
    >
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">
                    {{ __('Create Make') }}
                </flux:heading>

                <flux:button
                    variant="ghost"
                    :href="route('setup.makes.index')"
                    wire:navigate
                >
                    {{ __('Back') }}
                </flux:button>
            </div>

            <form wire:submit="save" class="space-y-6">
                <flux:input
                    label="{{ __('Name') }}"
                    placeholder="{{ __('Manufacturer name') }}"
                    wire:model="name"
                />

                <div class="flex justify-end gap-3">
                    <flux:button
                        variant="ghost"
                        :href="route('setup.makes.index')"
                        wire:navigate
                    >
                        {{ __('Cancel') }}
                    </flux:button>

                    <flux:button
                        type="submit"
                        variant="primary"
                    >
                        {{ __('Save') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </x-pages::setup.layout>
</section>