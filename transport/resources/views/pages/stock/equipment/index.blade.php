<?php

use App\Models\Company;
use App\Models\Customer;
use App\Models\Equipment;
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Equipment')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $transferEquipmentId = null;

    public string $ownerType = 'tenant';

    public ?int $ownerCompanyId = null;

    public ?int $ownerCustomerId = null;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function transferOwner(int $equipmentId, CurrentCompany $currentCompany): void
    {
        Gate::authorize('stock.equipment.update');
        $equipment = Equipment::where('company_id', $currentCompany->id(Auth::user()))->findOrFail($equipmentId);
        $this->transferEquipmentId = $equipment->id;
        $this->ownerCustomerId = $equipment->customers()->value('customers.id');
        $this->ownerType = $this->ownerCustomerId ? 'customer' : 'tenant';
        $this->ownerCompanyId = $equipment->company_id;
        Flux::modal('transfer-owner')->show();
    }

    public function saveOwner(CurrentCompany $currentCompany): void
    {
        Gate::authorize('stock.equipment.update');
        $companyId = $currentCompany->id(Auth::user());
        $data = $this->validate(['ownerType' => ['required', Rule::in(['tenant', 'customer'])], 'ownerCompanyId' => [Rule::requiredIf($this->ownerType === 'tenant'), 'nullable', Rule::exists('companies', 'id')->where('is_active', true)], 'ownerCustomerId' => [Rule::requiredIf($this->ownerType === 'customer'), 'nullable', 'exists:customers,id']]);
        $equipment = Equipment::where('company_id', $companyId)->findOrFail($this->transferEquipmentId);
        $ownerCompanyId = $this->ownerType === 'customer' ? Customer::findOrFail($data['ownerCustomerId'])->company_id : $data['ownerCompanyId'];
        $equipment->update(['company_id' => $ownerCompanyId]);
        $equipment->customers()->sync($this->ownerType === 'customer' ? [$data['ownerCustomerId']] : []);
        $this->reset(['transferEquipmentId', 'ownerCompanyId', 'ownerCustomerId']);
        $this->ownerType = 'tenant';
        Flux::modal('transfer-owner')->close();
        Flux::toast(text: 'Equipment owner updated', variant: 'success');
    }

    public function with(CurrentCompany $currentCompany): array
    {
        $companyId = $currentCompany->id(Auth::user());

        return ['equipment' => Equipment::query()->where('company_id', $companyId)->with(['equipmentModel.make', 'company', 'customers'])
            ->when($this->search !== '', function ($query) {
                foreach (preg_split('/\s+/', trim($this->search)) ?: [] as $term) {
                    $term = strtolower($term);
                    $query->where(fn ($query) => $query->whereRaw('LOWER(stock_number) LIKE ?', ['%'.$term.'%'])->orWhereRaw('LOWER(serial_number) LIKE ?', ['%'.$term.'%'])->orWhereHas('equipmentModel', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.$term.'%'])->orWhereHas('make', fn ($query) => $query->whereRaw('LOWER(name) LIKE ?', ['%'.$term.'%'])))->orWhereHas('customers', fn ($query) => $query->whereRaw('LOWER(customers.name) LIKE ?', ['%'.$term.'%'])));
                }
            })
            ->orderBy('stock_number')->paginate(10), 'customers' => Customer::with('company')->orderBy('name')->get(), 'companies' => Company::where('is_active', true)->orderBy('name')->get()];
    }
};
?>

<section class="w-full p-6">
    <flux:heading size="xl">{{ __('Equipment') }}</flux:heading>
    <x-pages::shared.layout contentclass="max-w-5xl">
        <div class="mb-4 flex justify-between gap-3"><flux:input class="max-w-sm flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search equipment..." />@can('stock.equipment.create')<flux:button variant="primary" :href="route('stock.equipment.create')">Add Equipment</flux:button>@endcan</div>
        <flux:table :paginate="$equipment">
            <flux:table.columns><flux:table.column>{{ __('Iteration') }}</flux:table.column><flux:table.column>{{ __('Stock number') }}</flux:table.column><flux:table.column>{{ __('Name') }}</flux:table.column><flux:table.column>{{ __('Serial number') }}</flux:table.column><flux:table.column>{{ __('Owner') }}</flux:table.column><flux:table.column align="end">{{ __('Actions') }}</flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse ($equipment as $item)
                    <flux:table.row :key="$item->id"><flux:table.cell>{{ $equipment->firstItem()+$loop->index }}</flux:table.cell><flux:table.cell>{{ $item->stock_number }}</flux:table.cell><flux:table.cell>{{ $item->equipmentModel->make->name }} {{ $item->equipmentModel->name }}</flux:table.cell><flux:table.cell>{{ $item->serial_number }}</flux:table.cell><flux:table.cell>{{ $item->customers->first()?->name??$item->company->name }}</flux:table.cell><flux:table.cell align="end"><flux:dropdown position="bottom" align="end"><flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Actions for {{ $item->stock_number }}"/><flux:menu>@can('stock.equipment.update')<flux:menu.item icon="pencil" :href="route('stock.equipment.update',$item)">Manage</flux:menu.item><flux:menu.item icon="arrow-path" wire:click="transferOwner({{ $item->id }})">Transfer owner</flux:menu.item>@endcan @can('operations.movement.create')<flux:menu.item icon="truck" :href="route('operations.movements.create',['equipment'=>$item->id])">Raise movement</flux:menu.item>@endcan</flux:menu></flux:dropdown></flux:table.cell></flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="6" class="text-center">{{ __('No equipment found') }}</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
        <flux:modal name="transfer-owner" class="min-w-[28rem]"><div class="space-y-5"><flux:heading size="lg">Transfer owner</flux:heading><flux:select wire:model.live="ownerType" label="Owner type"><flux:select.option value="tenant">Tenant</flux:select.option><flux:select.option value="customer">Customer</flux:select.option></flux:select>@if($ownerType==='tenant')<flux:select wire:model="ownerCompanyId" variant="combobox" label="Tenant"><flux:select.option value="">Select tenant</flux:select.option>@foreach($companies as $company)<flux:select.option :value="$company->id">{{ $company->name }}</flux:select.option>@endforeach</flux:select>@else<flux:select wire:model="ownerCustomerId" variant="combobox" label="Customer"><flux:select.option value="">Select customer</flux:select.option>@foreach($customers as $customer)<flux:select.option :value="$customer->id">{{ $customer->name }} — {{ $customer->company->name }}</flux:select.option>@endforeach</flux:select>@endif<div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close><flux:button type="button" variant="primary" wire:click="saveOwner">Transfer</flux:button></div></div></flux:modal>
    </x-pages::shared.layout>
</section>
