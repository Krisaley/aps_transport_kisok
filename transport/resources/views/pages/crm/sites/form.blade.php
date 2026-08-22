<?php

use App\Models\Site;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

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
    public function mount(?Site $site = null): void { $this->site = $site; foreach (['name','address_line_1','address_line_2','town','county','postcode','what_3_words','address_code'] as $field) { $this->{$field} = $site?->{$field} ?? ($this->{$field} ?? null); } }
    public function save(): mixed
    {
        Gate::authorize($this->site ? 'crm.site.update' : 'crm.site.create');
        $data = $this->validate(['name'=>['required','string','max:255'],'address_line_1'=>['required','string','max:255'],'address_line_2'=>['nullable','string','max:255'],'town'=>['nullable','string','max:255'],'county'=>['nullable','string','max:255'],'postcode'=>['required','string','max:20'],'what_3_words'=>['nullable','string','max:255'],'address_code'=>['nullable','string','max:255',Rule::unique('sites')->ignore($this->site?->id)]]);
        $this->site ? $this->site->update($data) : Site::create($data);
        Flux::toast(text: 'Site saved successfully', variant: 'success'); return $this->redirectRoute('crm.sites.index', navigate: true);
    }
};
?>
<section class="w-full p-6"><flux:heading size="xl">{{ $site ? __('Edit Site') : __('Create Site') }}</flux:heading><x-pages::shared.layout contentclass="max-w-3xl"><form wire:submit="save" class="grid gap-5 sm:grid-cols-2"><flux:input wire:model="name" label="Name" required /><flux:input wire:model="address_code" label="Address code" /><flux:input wire:model="address_line_1" label="Address line 1" required /><flux:input wire:model="address_line_2" label="Address line 2" /><flux:input wire:model="town" label="Town" /><flux:input wire:model="county" label="County" /><flux:input wire:model="postcode" label="Postcode" required /><flux:input wire:model="what_3_words" label="What3Words" /><div class="flex justify-end gap-3 sm:col-span-2"><flux:button :href="route('crm.sites.index')" variant="ghost">{{ __('Cancel') }}</flux:button><flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button></div></form></x-pages::shared.layout></section>
