<?php

use App\Models\Vehicle;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Vehicle')] class extends Component {
    public ?Vehicle $vehicle=null; public string $name=''; public string $registration=''; public bool $is_active=true;
    public function mount(?Vehicle $vehicle=null): void { $this->vehicle=$vehicle; $this->name=$vehicle?->name??''; $this->registration=$vehicle?->registration??''; $this->is_active=$vehicle?->is_active??true; }
    public function save(): mixed { Gate::authorize($this->vehicle?'transport.vehicle.update':'transport.vehicle.create'); $data=$this->validate(['name'=>['required','string','max:255'],'registration'=>['required','string','max:255',Rule::unique('vehicles')->ignore($this->vehicle?->id)],'is_active'=>['boolean']]); $this->vehicle?$this->vehicle->update($data):Vehicle::create($data); Flux::toast(text:'Vehicle saved successfully',variant:'success'); return $this->redirectRoute('transport.vehicles.index',navigate:true); }
};
?>
<section class="w-full p-6"><flux:heading size="xl">{{ $vehicle ? __('Edit Vehicle') : __('Create Vehicle') }}</flux:heading><x-pages::shared.layout contentclass="max-w-3xl"><form wire:submit="save" class="space-y-6"><flux:input wire:model="name" label="Name" required /><flux:input wire:model="registration" label="Registration" required /><flux:switch wire:model="is_active" label="Vehicle is active" /><div class="flex justify-end gap-3"><flux:button :href="route('transport.vehicles.index')" variant="ghost">{{ __('Cancel') }}</flux:button><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div></form></x-pages::shared.layout></section>
