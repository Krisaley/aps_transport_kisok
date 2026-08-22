<?php

use App\Models\Vehicle;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Support\CurrentCompany;

new #[Title('Vehicle')] class extends Component {
    public ?Vehicle $vehicle=null; public string $name=''; public string $registration=''; public ?string $capacity_tonnes=null; public ?int $company_id=null; public bool $is_active=true;
    public function mount(CurrentCompany $currentCompany, ?Vehicle $vehicle=null): void { $this->company_id=$currentCompany->id(auth()->user()); abort_if($vehicle && (int)$vehicle->company_id !== $this->company_id,404); $this->vehicle=$vehicle; $this->name=$vehicle?->name??''; $this->registration=$vehicle?->registration??''; $this->capacity_tonnes=$vehicle?->capacity_tonnes; $this->is_active=$vehicle?->is_active??true; }
    public function save(): mixed { Gate::authorize($this->vehicle?'transport.vehicle.update':'transport.vehicle.create'); $data=$this->validate(['company_id'=>['required','integer','exists:companies,id'],'name'=>['required','string','max:255'],'registration'=>['required','string','max:255',Rule::unique('vehicles')->ignore($this->vehicle?->id)],'capacity_tonnes'=>['nullable','numeric','min:0.01','max:999.99'],'is_active'=>['boolean']]); $this->vehicle?$this->vehicle->update($data):Vehicle::create($data); Flux::toast(text:'Vehicle saved successfully',variant:'success'); return $this->redirectRoute('transport.vehicles.index',navigate:true); }
};
?>
<section class="w-full p-6"><flux:heading size="xl">{{ $vehicle ? __('Edit Vehicle') : __('Create Vehicle') }}</flux:heading><x-pages::shared.layout contentclass="max-w-3xl"><form wire:submit="save" class="space-y-6"><flux:input wire:model="name" label="Name" required /><flux:input wire:model="registration" label="Registration" required /><flux:input wire:model="capacity_tonnes" type="number" step="0.01" min="0.01" suffix="t" label="Movement capacity" description="Maximum payload/capacity in tonnes, for example 3.5 or 13.5."/><flux:switch wire:model="is_active" label="Vehicle is active" /><div class="flex justify-end gap-3"><flux:button :href="route('transport.vehicles.index')" variant="ghost">{{ __('Cancel') }}</flux:button><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div></form></x-pages::shared.layout></section>
