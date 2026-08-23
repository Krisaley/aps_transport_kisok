<?php

use App\Models\Equipment;
use App\Models\EquipmentModel;
use App\Models\Make;
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Equipment')] class extends Component {
    public ?Equipment $equipment = null;
    public ?int $company_id = null;
    public ?int $make_id = null;
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
        $this->make_id=$model->make_id; $this->model_id=$model->id; $this->reset(['new_make_id','new_model_name']); Flux::modal('create-model')->close(); Flux::toast(text:'Model created',variant:'success');
    }
    public function mount(CurrentCompany $currentCompany, ?Equipment $equipment = null): void { $this->company_id=$currentCompany->id(Auth::user()); abort_if($equipment && (int) $equipment->company_id !== $this->company_id, 404); $this->equipment=$equipment; $this->model_id=$equipment?->model_id; $this->make_id=$equipment?->equipmentModel?->make_id; $this->stock_number=$equipment?->stock_number??''; $this->serial_number=$equipment?->serial_number??''; }
    public function updatedMakeId(): void { $this->model_id = null; }
    public function save(CurrentCompany $currentCompany): mixed
    {
        Gate::authorize($this->equipment ? 'stock.equipment.update' : 'stock.equipment.create');
        abort_unless($this->company_id === $currentCompany->id(Auth::user()), 403);
        $data=$this->validate(['model_id'=>['required','integer','exists:models,id'],'stock_number'=>['required','string','max:255',Rule::unique('equipment')->ignore($this->equipment?->id)],'serial_number'=>['required','string','max:255',Rule::unique('equipment')->where('model_id',$this->model_id)->ignore($this->equipment?->id)]]);
        $this->equipment ? $this->equipment->update($data) : Equipment::create($data + ['company_id' => $this->company_id]);
        Flux::toast(text:'Equipment saved successfully',variant:'success'); return $this->redirectRoute('stock.equipment.index',navigate:true);
    }
    public function with(): array { return ['models'=>EquipmentModel::query()->with('make')->when($this->make_id,fn($query)=>$query->where('make_id',$this->make_id))->orderBy('name')->get(),'makes'=>Make::orderBy('name')->get()]; }
};
?>
<section class="w-full p-6"><flux:heading size="xl">{{ $equipment ? __('Edit Equipment') : __('Create Equipment') }}</flux:heading><x-pages::shared.layout contentclass="max-w-3xl"><form wire:submit="save" class="space-y-6"><div class="grid gap-4 md:grid-cols-[1fr_1fr_auto]"><flux:select wire:model.live="make_id" variant="combobox" clearable label="Make" placeholder="Search makes..."><flux:select.option value="">Select a make</flux:select.option>@foreach($makes as $make)<flux:select.option :value="$make->id">{{ $make->name }}</flux:select.option>@endforeach</flux:select><flux:select wire:model="model_id" variant="combobox" clearable label="Model" placeholder="Search models..." :disabled="!$make_id"><flux:select.option value="">{{ $make_id ? __('Select a model') : __('Select a make first') }}</flux:select.option>@foreach($models as $model)<flux:select.option :value="$model->id" :wire:key="'equipment-model-'.$model->id">{{ $model->name }}</flux:select.option>@endforeach</flux:select><div class="flex items-end"><flux:modal.trigger name="create-model"><flux:button type="button" icon="plus">New model</flux:button></flux:modal.trigger></div></div><flux:input wire:model="stock_number" label="Stock number" required /><flux:input wire:model="serial_number" label="Serial number" required /><div class="flex justify-end gap-3"><flux:button :href="route('stock.equipment.index')" variant="ghost">{{ __('Cancel') }}</flux:button><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div></form><flux:modal name="create-model" class="min-w-[28rem]"><div class="space-y-4"><flux:heading size="lg">Create model</flux:heading><flux:select wire:model="new_make_id" variant="combobox" label="Make" placeholder="Search makes...">@foreach($makes as $make)<flux:select.option :value="$make->id">{{ $make->name }}</flux:select.option>@endforeach</flux:select><flux:input wire:model="new_model_name" label="Model name"/><div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close><flux:button type="button" variant="primary" wire:click="createModel">Create and select</flux:button></div></div></flux:modal></x-pages::shared.layout></section>
