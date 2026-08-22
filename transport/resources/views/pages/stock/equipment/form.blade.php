<?php

use App\Models\Equipment;
use App\Models\EquipmentModel;
use App\Models\Make;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Equipment')] class extends Component {
    public ?Equipment $equipment = null;
    public ?int $model_id = null;
    public string $stock_number = '';
    public string $serial_number = '';
    public ?int $new_make_id = null;
    public string $new_model_name = '';
    public function createModel(): void
    {
        Gate::authorize('stock.make-model.create');
        $data=$this->validate(['new_make_id'=>['required','exists:makes,id'],'new_model_name'=>['required','max:255',Rule::unique('models','name')->where('make_id',$this->new_make_id)]]);
        $model=EquipmentModel::create(['make_id'=>$data['new_make_id'],'name'=>$data['new_model_name']]);
        $this->model_id=$model->id; $this->reset(['new_make_id','new_model_name']); Flux::modal('create-model')->close(); Flux::toast(text:'Model created',variant:'success');
    }
    public function mount(?Equipment $equipment = null): void { $this->equipment=$equipment; $this->model_id=$equipment?->model_id; $this->stock_number=$equipment?->stock_number??''; $this->serial_number=$equipment?->serial_number??''; }
    public function save(): mixed
    {
        Gate::authorize($this->equipment ? 'stock.equipment.update' : 'stock.equipment.create');
        $data=$this->validate(['model_id'=>['required','integer','exists:models,id'],'stock_number'=>['required','string','max:255',Rule::unique('equipment')->ignore($this->equipment?->id)],'serial_number'=>['required','string','max:255',Rule::unique('equipment')->where('model_id',$this->model_id)->ignore($this->equipment?->id)]]);
        $this->equipment ? $this->equipment->update($data) : Equipment::create($data);
        Flux::toast(text:'Equipment saved successfully',variant:'success'); return $this->redirectRoute('stock.equipment.index',navigate:true);
    }
    public function with(): array { return ['models'=>EquipmentModel::query()->with('make')->orderBy('name')->get(),'makes'=>Make::orderBy('name')->get()]; }
};
?>
<section class="w-full p-6"><flux:heading size="xl">{{ $equipment ? __('Edit Equipment') : __('Create Equipment') }}</flux:heading><x-pages::shared.layout contentclass="max-w-3xl"><form wire:submit="save" class="space-y-6"><div class="flex items-end gap-2"><div class="flex-1"><flux:select wire:model="model_id" variant="combobox" clearable label="Model" placeholder="Search make or model..."><flux:select.option value="">{{ __('Select a model') }}</flux:select.option>@foreach($models as $model)<flux:select.option :value="$model->id" :wire:key="'equipment-model-'.$model->id">{{ $model->make->name }} — {{ $model->name }}</flux:select.option>@endforeach</flux:select></div><flux:modal.trigger name="create-model"><flux:button type="button" icon="plus">New model</flux:button></flux:modal.trigger></div><flux:input wire:model="stock_number" label="Stock number" required /><flux:input wire:model="serial_number" label="Serial number" required /><div class="flex justify-end gap-3"><flux:button :href="route('stock.equipment.index')" variant="ghost">{{ __('Cancel') }}</flux:button><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div></form><flux:modal name="create-model" class="min-w-[28rem]"><div class="space-y-4"><flux:heading size="lg">Create model</flux:heading><flux:select wire:model="new_make_id" variant="combobox" label="Make" placeholder="Search makes...">@foreach($makes as $make)<flux:select.option :value="$make->id">{{ $make->name }}</flux:select.option>@endforeach</flux:select><flux:input wire:model="new_model_name" label="Model name"/><div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close><flux:button type="button" variant="primary" wire:click="createModel">Create and select</flux:button></div></div></flux:modal></x-pages::shared.layout></section>
