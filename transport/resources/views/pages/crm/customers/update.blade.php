<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Customer;
use App\Models\Role;
use App\Models\Team;
use Illuminate\Support\Facades\Password;
use Flux\Flux;
use Illuminate\Support\Carbon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use App\Models\Site;
use Illuminate\Validation\Rule;

new #[Title('Update Customer')] class extends Component {
    
    public Customer $customer;

    public string $name             = '';
    public string $account_number   = '';
    public ?int $home_site_id = null;

    public function mount(Customer $customer):void
    {
        $this->customer         = $customer;
        $this->name             = $customer->name;
        $this->account_number   = $customer->account_number;
        $this->home_site_id = $customer->home_site_id;
    }

    public function save()
    {
        Gate::authorize('crm.customer.update');

        $this->validate([
            'name'              => ['required', 'string', 'max:255'],
            'account_number'    => ['required', 'string', 'max:255', 'unique:customers,account_number,'.$this->customer->id],
            'home_site_id' => ['nullable',Rule::exists('sites','id')->where('company_id',$this->customer->company_id)],
        ]);

        $this->customer->update([
            'name'              => $this->name,
            'account_number'    => $this->account_number,
            'home_site_id' => $this->home_site_id,
        ]);
        Site::where('customer_id',$this->customer->id)->where('id','!=',$this->home_site_id)->update(['customer_id'=>null]);
        if ($this->home_site_id) Site::whereKey($this->home_site_id)->update(['customer_id'=>$this->customer->id]);

        Flux::toast(
            text: 'Customer updated successfully',
            variant: 'success',
        );

        return $this->redirectRoute('crm.customers.index', navigate: true);
    }

    public function with(): array
    {
        return ['sites'=>Site::where('company_id',$this->customer->company_id)->where(fn($query)=>$query->whereNull('customer_id')->orWhere('customer_id',$this->customer->id))->orderBy('name')->get()];
    }

};
?>
<section class="w-full">
    @include('partials.setup-heading',[
        'section'   => 'Customers',
    ])

    <flux:heading class="sr-only">{{ __('Manage Customer') }}</flux:heading>

    <x-pages::shared.layout
        :contentclass="__('mt-5 w-full max-w-3xl')"
        >
    
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Edit Customer') }}</flux:heading>
                <flux:button variant="ghost" :href="route('crm.customers.index')" wire:navigate>{{ __('Back') }}</flux:button>
            </div>

            <form wire:submit="save" class="space-y-6">
                <flux:input label="Account Number" placeholder="Acc No" wire:model="account_number" />
                <flux:input label="name" placeholder="Name" wire:model="name" />
                <flux:select wire:model="home_site_id" variant="listbox" searchable label="Home address" placeholder="Select an existing site"><flux:select.option value="">Not set</flux:select.option>@foreach($sites as $site)<flux:select.option :value="$site->id">{{ $site->name }} — {{ $site->formattedAddress() }}</flux:select.option>@endforeach</flux:select>
                
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" :href="route('crm.customers.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </div>

    </x-pages::shared.layout>

</section>
