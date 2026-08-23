<?php

use App\Models\{Customer, Site};
use App\Support\CurrentCompany;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Illuminate\Support\Str;
use App\Services\PostcodeLookup;
use App\Settings\GeneralSettings;

new #[Title('Site')] class extends Component {
    public ?Site $site = null;
    public string $name = '';
    public string $address_line_1 = '';
    public ?string $address_line_2 = null;
    public ?string $town = null;
    public ?string $county = null;
    public string $postcode = '';
    public ?string $what_3_words = null;
    public ?string $address_code = null;
    public ?int $customer_id = null;
    public ?int $company_id = null;
    public function mount(CurrentCompany $currentCompany, ?Site $site = null): void { $this->company_id=$currentCompany->id(auth()->user()); $this->site = $site; foreach (['name','address_line_1','address_line_2','town','county','postcode','what_3_words','address_code','access_instructions','customer_id'] as $field) { $this->{$field} = $site?->{$field} ?? ($this->{$field} ?? null); } }
    public function save(CurrentCompany $currentCompany): mixed
    {
        Gate::authorize($this->site ? 'crm.site.update' : 'crm.site.create');
        $data = $this->validate(['customer_id'=>['nullable',Rule::exists('customers','id')->where('company_id',$this->company_id)],'name'=>['required','string','max:255'],'address_line_1'=>['required','string','max:255'],'address_line_2'=>['nullable','string','max:255'],'town'=>['nullable','string','max:255'],'county'=>['nullable','string','max:255'],'postcode'=>['required','string','max:20'],'what_3_words'=>['nullable','string','max:255'],'access_instructions'=>['nullable','string','max:2000']]);
        $normalised = Str::upper(preg_replace('/[^A-Z0-9]/i','',implode('|',[$data['address_line_1'],$data['address_line_2']??'',$data['town']??'',$data['county']??'',$data['postcode']]))) ;
        $data['address_code'] = hash('sha256', $normalised);
        validator($data, ['address_code'=>[Rule::unique('sites','address_code')->ignore($this->site?->id)]], ['address_code.unique'=>'This physical address already exists. Select the existing Site instead.'])->validate();
        $this->site ? $this->site->update($data) : Site::create($data);
        Flux::toast(text: 'Site saved successfully', variant: 'success'); return $this->redirectRoute('crm.sites.index', navigate: true);
    }
    public ?string $access_instructions = null;
    public function validatePostcode(PostcodeLookup $lookup, GeneralSettings $settings): void
    {
        if($settings->postcode_validation_provider!=='postcodes_io'){ Flux::toast(text:'Postcodes.io is not the configured provider.',variant:'warning'); return; }
        $result=$lookup->lookup($this->postcode); if(!$result){$this->addError('postcode','Postcode could not be validated. Check it or continue with manual entry.');return;}
        $this->postcode=$result['postcode']; $this->county=$this->county ?: ($result['admin_district']??$result['region']??null); $this->resetErrorBag('postcode'); Flux::toast(text:'Postcode validated',variant:'success');
    }
    public function with(): array { return ['customers'=>Customer::where('company_id',$this->company_id)->orderBy('name')->get()]; }
};
?>
<section class="w-full p-6"><flux:heading size="xl">{{ $site ? __('Edit Site') : __('Create Site') }}</flux:heading><x-pages::shared.layout contentclass="max-w-3xl"><form wire:submit="save" class="grid gap-5 sm:grid-cols-2"><flux:input wire:model="name" label="Site name" required /><flux:select wire:model="customer_id" variant="combobox" clearable label="Customer (blank for depot/internal site)" placeholder="Search customers..."><flux:select.option value="">No customer</flux:select.option>@foreach($customers as $customer)<flux:select.option :value="$customer->id">{{ $customer->name }}</flux:select.option>@endforeach</flux:select><flux:callout class="sm:col-span-2">Enter the physical address. A hidden fingerprint prevents duplicates. Postcodes.io can validate UK postcodes; manual entry remains available.</flux:callout><flux:input wire:model="address_line_1" label="Address line 1" required /><flux:input wire:model="address_line_2" label="Address line 2" /><flux:input wire:model="town" label="Town / city" /><flux:input wire:model="county" label="County" /><div class="flex items-end gap-2"><div class="flex-1"><flux:input wire:model="postcode" label="Postcode" required /></div><flux:button type="button" wire:click="validatePostcode">Validate</flux:button></div><flux:input wire:model="what_3_words" label="What3Words" /><flux:textarea class="sm:col-span-2" wire:model="access_instructions" label="Instructions / easy to find"/><div class="flex justify-end gap-3 sm:col-span-2"><flux:button :href="route('crm.sites.index')" variant="ghost">{{ __('Cancel') }}</flux:button><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div></form></x-pages::shared.layout></section>
