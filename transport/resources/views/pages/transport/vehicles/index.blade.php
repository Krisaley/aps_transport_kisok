<?php

use App\Models\Vehicle;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Vehicles')] class extends Component {
    use WithPagination;
    public string $search = '';
    public string $statusFilter = 'all';
    public function updated(string $property): void { if (in_array($property, ['search', 'statusFilter'], true)) { $this->resetPage(); } }
    public function with(): array
    {
        return ['vehicles' => Vehicle::query()
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$this->search.'%')->orWhere('registration', 'like', '%'.$this->search.'%')))
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('is_active', $this->statusFilter === 'active'))
            ->orderBy('name')->paginate(10)];
    }
};
?>

<section class="w-full p-6">
    <flux:heading size="xl">{{ __('Vehicles') }}</flux:heading>
    <x-pages::shared.layout contentclass="max-w-5xl">
        <div class="mb-4 flex gap-2"><flux:input class="max-w-sm flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search vehicles..." /><flux:select wire:model.live="statusFilter"><flux:select.option value="all">{{ __('All') }}</flux:select.option><flux:select.option value="active">{{ __('Active') }}</flux:select.option><flux:select.option value="inactive">{{ __('Inactive') }}</flux:select.option></flux:select>@can('transport.vehicle.create')<flux:button variant="primary" :href="route('transport.vehicles.create')">Add Vehicle</flux:button>@endcan</div>
        <flux:table :paginate="$vehicles">
            <flux:table.columns><flux:table.column>{{ __('Status') }}</flux:table.column><flux:table.column>{{ __('Name') }}</flux:table.column><flux:table.column>{{ __('Registration') }}</flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse ($vehicles as $vehicle)
                    <flux:table.row :key="$vehicle->id"><flux:table.cell>{{ $vehicle->is_active ? __('Active') : __('Inactive') }}</flux:table.cell><flux:table.cell>{{ $vehicle->name }}</flux:table.cell><flux:table.cell>{{ $vehicle->registration }}</flux:table.cell><flux:table.cell>@can('transport.vehicle.update')<flux:button size="sm" variant="ghost" :href="route('transport.vehicles.update',$vehicle)">Manage</flux:button>@endcan</flux:table.cell></flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="3" class="text-center">{{ __('No vehicles found') }}</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </x-pages::shared.layout>
</section>
