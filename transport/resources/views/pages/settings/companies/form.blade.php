<?php

use App\Models\{Company, Site};
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Company')] class extends Component {
    use WithFileUploads;
    public ?Company $company = null;
    public string $name = '';
    public ?string $trading_name = null;
    public string $code = '';
    public ?string $address = null;
    public ?int $home_site_id = null;
    public ?string $email = null;
    public ?string $phone = null;
    public string $document_prefix = '';
    public bool $is_active = true;
    public ?string $registration_number = null;
    public ?string $vat_number = null;
    public ?string $brand_primary_color = null;
    public $logo = null;

    public function mount(?Company $company = null): void
    {
        $this->company = $company;
        if (! $company) return;
        foreach (['name','trading_name','code','address','home_site_id','email','phone','document_prefix','registration_number','vat_number','brand_primary_color','is_active'] as $field) $this->{$field} = $company->{$field};
    }

    public function save(): mixed
    {
        Gate::authorize($this->company ? 'admin.company.update' : 'admin.company.create');
        $data = $this->validate([
            'name'=>['required','string','max:255'], 'trading_name'=>['nullable','string','max:255'], 'code'=>['required','string','max:20',Rule::unique('companies')->ignore($this->company?->id)],
            'address'=>['nullable','string','max:2000'], 'home_site_id'=>['nullable',Rule::exists('sites','id')->where('company_id',$this->company?->id)],
            'email'=>['nullable','email','max:255'], 'phone'=>['nullable','string','max:100'], 'document_prefix'=>['required','string','max:20'], 'registration_number'=>['nullable','string','max:100'], 'vat_number'=>['nullable','string','max:100'], 'brand_primary_color'=>['nullable','regex:/^#[0-9A-Fa-f]{6}$/'], 'logo'=>['nullable','image','max:2048'], 'is_active'=>['boolean'],
        ]);
        unset($data['logo']);
        if ($this->logo) $data['logo_path'] = $this->logo->store('company-logos', 'public');
        if ($this->company) $this->company->update($data); else $this->company = Company::create($data);
        Flux::toast(text: 'Company saved', variant: 'success');
        return $this->redirectRoute('settings.companies.index', navigate: true);
    }

    public function with(): array { return ['sites'=>$this->company ? Site::where('company_id',$this->company->id)->orderBy('name')->get() : collect()]; }
};
?>
<section class="w-full">@include('partials.settings-heading',['section'=>$company ? 'Update company' : 'Create company'])<x-pages::settings.layout contentclass="mt-5 w-full max-w-3xl"><div class="mb-5 flex items-center justify-between"><flux:heading size="lg">{{ $company ? 'Update company' : 'Create company' }}</flux:heading><flux:button variant="ghost" :href="route('settings.companies.index')">Back</flux:button></div><form wire:submit="save" class="grid gap-5 md:grid-cols-2"><flux:input wire:model="name" label="Legal company name" required/><flux:input wire:model="trading_name" label="Trading name"/><flux:input wire:model="code" label="Code" required/><flux:switch wire:model="is_active" label="Active tenant"/><flux:input wire:model="email" label="Email"/><flux:input wire:model="phone" label="Phone"/>@if($company)<flux:select class="md:col-span-2" wire:model="home_site_id" label="Home depot and postal address"><flux:select.option value="">Not set</flux:select.option>@foreach($sites as $site)<flux:select.option :value="$site->id">{{ $site->name }} — {{ $site->formattedAddress() }}</flux:select.option>@endforeach</flux:select><flux:text class="md:col-span-2">Addresses are reusable Site records. Create the depot under CRM → Sites, then select it here.</flux:text>@else<flux:callout class="md:col-span-2">Save the company first, create its Site record, then return to select its home depot/postal address.</flux:callout>@endif<flux:separator class="md:col-span-2"/><flux:heading class="md:col-span-2" size="md">Document branding</flux:heading><flux:input wire:model="document_prefix" label="Document prefix" required/><flux:input wire:model="brand_primary_color" type="color" label="Brand colour"/><flux:input wire:model="registration_number" label="Company registration"/><flux:input wire:model="vat_number" label="VAT number"/><flux:input wire:model="logo" type="file" accept="image/png,image/jpeg,image/webp" label="Logo"/>@if($company?->logo_path)<img src="{{ Storage::url($company->logo_path) }}" alt="Current logo" class="max-h-20 max-w-52 object-contain">@endif<div class="md:col-span-2 flex justify-end gap-3"><flux:button variant="ghost" :href="route('settings.companies.index')">Cancel</flux:button><flux:button type="submit" variant="primary">Save company</flux:button></div></form></x-pages::settings.layout></section>
