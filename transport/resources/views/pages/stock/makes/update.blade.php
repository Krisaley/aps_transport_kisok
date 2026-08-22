<?php

use App\Models\Make;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Update Make')] class extends Component
{
    public Make $make;

    public string $name = '';

    public function mount(): void
    {
        $this->name = $this->make->name;
    }

    public function save(): void
    {
        Gate::authorize('stock.make-model.update');

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('makes', 'name')->ignore($this->make->id),
            ],
        ]);

        $this->make->update([
            'name' => $this->name,
        ]);

        Flux::toast(
            text: 'Make updated successfully',
            variant: 'success',
        );

        $this->redirectRoute(
            'stock.makes.index',
            navigate: true
        );
    }
};
?>

<section class="w-full">
    @include('partials.setup-heading', [
        'section' => 'Makes & Models',
    ])

    <flux:heading class="sr-only">{{ __('Update Make') }}</flux:heading>

    <x-pages::shared.layout
        :contentclass="__('mt-5 w-full max-w-3xl')"
    >
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <div>
                    <flux:heading size="lg">
                        {{ __('Update Make') }}
                    </flux:heading>

                    <flux:text class="mt-1">
                        {{ $make->name }}
                    </flux:text>
                </div>

                <flux:button
                    variant="ghost"
                    :href="route('stock.makes.index')"
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
                        :href="route('stock.makes.index')"
                        wire:navigate
                    >
                        {{ __('Cancel') }}
                    </flux:button>

                    <flux:button
                        type="submit"
                        variant="primary"
                    >
                        {{ __('Save Changes') }}
                    </flux:button>
                </div>
            </form>
        </div>
    </x-pages::shared.layout>
</section>
