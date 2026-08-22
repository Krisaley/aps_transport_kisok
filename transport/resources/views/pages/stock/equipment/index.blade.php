<?php

use App\Models\Equipment;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Equipment')] class extends Component {
    use WithPagination;
    public string $search = '';
    public function updatedSearch(): void { $this->resetPage(); }
    public function with(): array
    {
        return ['equipment' => Equipment::query()->with('equipmentModel.make')
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('stock_number', 'like', '%'.$this->search.'%')->orWhere('serial_number', 'like', '%'.$this->search.'%')->orWhereHas('equipmentModel', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'))))
            ->orderBy('stock_number')->paginate(10)];
    }
};
?>

<section class="w-full p-6">
    <flux:heading size="xl">{{ __('Equipment') }}</flux:heading>
    <x-pages::shared.layout contentclass="max-w-5xl">
        <div class="mb-4 flex justify-between gap-3"><flux:input class="max-w-sm flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search equipment..." />@can('stock.equipment.create')<flux:button variant="primary" :href="route('stock.equipment.create')">Add Equipment</flux:button>@endcan</div>
        <flux:table :paginate="$equipment">
            <flux:table.columns><flux:table.column>{{ __('Stock number') }}</flux:table.column><flux:table.column>{{ __('Make') }}</flux:table.column><flux:table.column>{{ __('Model') }}</flux:table.column><flux:table.column>{{ __('Serial number') }}</flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse ($equipment as $item)
                    <flux:table.row :key="$item->id"><flux:table.cell>{{ $item->stock_number }}</flux:table.cell><flux:table.cell>{{ $item->equipmentModel->make->name }}</flux:table.cell><flux:table.cell>{{ $item->equipmentModel->name }}</flux:table.cell><flux:table.cell>{{ $item->serial_number }}</flux:table.cell><flux:table.cell>@can('stock.equipment.update')<flux:button size="sm" variant="ghost" :href="route('stock.equipment.update',$item)">Manage</flux:button>@endcan</flux:table.cell></flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="4" class="text-center">{{ __('No equipment found') }}</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </x-pages::shared.layout>
</section>
