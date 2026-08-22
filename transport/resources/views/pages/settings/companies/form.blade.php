<?php

use App\Models\{Company, Site};
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Company')] class extends Component {
    public ?Company $company = null;
    public string $name = '';
    public string $code = '';
    public ?string $address = null;
    public ?int $home_site_id = null;
    public ?string $email = null;
    public ?string $phone = null;
    public string $document_prefix = '';
    public bool $is_active = true;

    public function mount(?Company $company = null): void
    {
        $this->company = $company;
        if (! $company) return;
        foreach (['name','code','address','home_site_id','email','phone','document_prefix','is_active'] as $field) $this->{$field} = $company->{$field};
    }

    public function save(): mixed
    {
        Gate::authorize($this->company ? 'admin.company.update' : 'admin.company.create');
        $data = $this->validate([
            'name'=>['required','string','max:255'], 'code'=>['required','string','max:20',Rule::unique('companies')->ignore($this->company?->id)],
            'address'=>['nullable','string','max:2000'], 'home_site_id'=>['nullable',Rule::exists('sites','id')->where('company_id',$this->company?->id)],
            'email'=>['nullable','email','max:255'], 'phone'=>['nullable','string','max:100'], 'document_prefix'=>['required','string','max:20'], 'is_active'=>['boolean'],
        ]);
        if ($this->company) $this->company->update($data); else $this->company = Company::create($data);
        Flux::toast(text: 'Company saved', variant: 'success');
        return $this->redirectRoute('settings.companies.index', navigate: true);
    }

    public function with(): array { return ['sites'=>$this->company ? Site::where('company_id',$this->company->id)->orderBy('name')->get() : collect()]; }
};
?>
<section class="w-full">@include('partials.settings-heading',['section'=>$company ? 'Update company' : 'Create company'])<x-pages::settings.layout contentclass="mt-5 w-full max-w-3xl"><div class="mb-5 flex items-center justify-between"><flux:heading size="lg">{{ $company ? 'Update company' : 'Create company' }}</flux:heading><flux:button variant="ghost" :href="route('settings.companies.index')">Back</flux:button></div><form wire:submit="save" class="grid gap-5 md:grid-cols-2"><flux:input wire:model="name" label="Company name" required/><flux:input wire:model="code" label="Code" required/><flux:textarea class="md:col-span-2" wire:model="address" label="Postal address"/><flux:input wire:model="email" label="Email"/><flux:input wire:model="phone" label="Phone"/><flux:input wire:model="document_prefix" label="Document prefix" required/><flux:switch wire:model="is_active" label="Active tenant"/>@if($company)<flux:select class="md:col-span-2" wire:model="home_site_id" label="Home depot site"><flux:select.option value="">Not set</flux:select.option>@foreach($sites as $site)<flux:select.option :value="$site->id">{{ $site->name }} — {{ $site->postcode }}</flux:select.option>@endforeach</flux:select><flux:text class="md:col-span-2">Create depot and customer addresses under CRM → Sites. Sites are automatically assigned to the active tenant.</flux:text>@else<flux:callout class="md:col-span-2">Save the company first, then add its depot site and return here to select it as the home depot.</flux:callout>@endif<div class="md:col-span-2 flex justify-end gap-3"><flux:button variant="ghost" :href="route('settings.companies.index')">Cancel</flux:button><flux:button type="submit" variant="primary">Save company</flux:button></div></form></x-pages::settings.layout></section>
