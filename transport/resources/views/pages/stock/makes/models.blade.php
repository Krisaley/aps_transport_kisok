<?php

use App\Models\EquipmentModel;
use App\Models\Make;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Manage Models')] class extends Component
{
    public Make $make;

    public string $name = '';

    public function save(): void
    {
        Gate::authorize('stock.make-model.create');

        $this->validate([
            'name' => [
                'required',
                'string',
                'max:255',

                Rule::unique(
                    (new EquipmentModel)->getTable(),
                    'name'
                )->where(
                    fn ($query) => $query->where('make_id', $this->make->id)
                ),
            ],
        ]);

        $this->make->equipmentModels()->create([
            'name' => $this->name,
        ]);

        $this->reset('name');

        Flux::modal('add-model-modal')->close();

        Flux::toast(
            text: 'Model created successfully',
            variant: 'success',
        );
    }

    public function delete(int $modelId): void
    {
        Gate::authorize('stock.make-model.delete');

        $model = $this->make
            ->equipmentModels()
            ->findOrFail($modelId);

        $model->delete();

        Flux::toast(
            text: 'Model deleted successfully',
            variant: 'success',
        );
    }

    public function with(): array
    {
        return [
            'models' => $this->make
                ->equipmentModels()
                ->orderBy('name')
                ->get(),
        ];
    }
};
?>

<section class="w-full">
    @include('partials.setup-heading', [
        'section' => 'Makes & Models',
    ])

    <flux:heading class="sr-only">
        {{ __('Manage Models') }}
    </flux:heading>

    <x-pages::shared.layout
        :contentclass="__('mt-5 w-full max-w-5xl')"
    >
        <div class="space-y-6">

            {{-- Header --}}
            <div class="flex items-center justify-between gap-4">
                <div>
                    <flux:heading size="lg">
                        {{ $make->name }}
                    </flux:heading>

                    <flux:text class="mt-1">
                        {{ __('Manage models for this manufacturer') }}
                    </flux:text>
                </div>

                <div class="flex gap-2">
                    <flux:button
                        variant="ghost"
                        :href="route('stock.makes.index')"
                        wire:navigate
                    >
                        {{ __('Back') }}
                    </flux:button>

                    @can('stock.make-model.create')
                        <flux:modal.trigger name="add-model-modal">
                            <flux:button
                                variant="primary"
                                icon="plus"
                            >
                                {{ __('Add Model') }}
                            </flux:button>
                        </flux:modal.trigger>
                    @endcan
                </div>
            </div>

            {{-- Models --}}
            <flux:table>
                <flux:table.columns>
                    <flux:table.column>
                        {{ __('Model') }}
                    </flux:table.column>

                    <flux:table.column align="end">
                        {{ __('Actions') }}
                    </flux:table.column>
                </flux:table.columns>

                <flux:table.rows>
                    @forelse ($models as $model)
                        <flux:table.row :key="$model->id">
                            <flux:table.cell>
                                {{ $model->name }}
                            </flux:table.cell>

                            <flux:table.cell align="end">
                                <flux:dropdown
                                    position="bottom"
                                    align="end"
                                >
                                    <flux:button
                                        icon="ellipsis-horizontal"
                                        variant="ghost"
                                        size="sm"
                                    />

                                    <flux:menu>
                                        @can('stock.make-model.update')
                                            <flux:menu.item
                                                icon="pencil"
                                                :href="route('stock.models.update', $model)"
                                                wire:navigate
                                            >
                                                {{ __('Manage') }}
                                            </flux:menu.item>
                                        @endcan

                                        @can('stock.make-model.delete')
                                            <flux:menu.separator />

                                            <flux:menu.item
                                                icon="trash"
                                                variant="danger"
                                                wire:click="delete({{ $model->id }})"
                                                wire:confirm="{{ __('Are you sure you want to delete this model?') }}"
                                            >
                                                {{ __('Delete') }}
                                            </flux:menu.item>
                                        @endcan
                                    </flux:menu>
                                </flux:dropdown>
                            </flux:table.cell>
                        </flux:table.row>
                    @empty
                        <flux:table.row>
                            <flux:table.cell
                                colspan="2"
                                class="py-8 text-center text-zinc-500"
                            >
                                {{ __('No models have been added for this manufacturer.') }}
                            </flux:table.cell>
                        </flux:table.row>
                    @endforelse
                </flux:table.rows>
            </flux:table>
        </div>

        {{-- Add Model --}}
        <flux:modal
            name="add-model-modal"
            class="min-w-[22rem]"
        >
            <form wire:submit="save" class="space-y-6">
                <div>
                    <flux:heading size="lg">
                        {{ __('Add Model') }}
                    </flux:heading>

                    <flux:text class="mt-1">
                        {{ __('Add a model to :make', ['make' => $make->name]) }}
                    </flux:text>
                </div>

                <flux:input
                    wire:model="name"
                    label="{{ __('Model Name') }}"
                    placeholder="{{ __('Model name') }}"
                    autofocus
                />

                <div class="flex justify-end gap-2">
                    <flux:modal.close>
                        <flux:button variant="ghost">
                            {{ __('Cancel') }}
                        </flux:button>
                    </flux:modal.close>

                    <flux:button
                        type="submit"
                        variant="primary"
                    >
                        {{ __('Add Model') }}
                    </flux:button>
                </div>
            </form>
        </flux:modal>

    </x-pages::shared.layout>
</section>
