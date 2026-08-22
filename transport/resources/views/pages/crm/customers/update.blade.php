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

new #[Title('Update Customer')] class extends Component {
    
    public Customer $customer;

    public string $name             = '';
    public string $account_number   = '';

    public function mount(Customer $customer):void
    {
        $this->customer         = $customer;
        $this->name             = $customer->name;
        $this->account_number   = $customer->account_number;
    }

    public function save()
    {
        Gate::authorize('crm.customer.update');

        $this->validate([
            'name'              => ['required', 'string', 'max:255'],
            'account_number'    => ['required', 'string', 'max:255', 'unique:customers,account_number,'.$this->customer->id],
        ]);

        $this->customer->update([
            'name'              => $this->name,
            'account_number'    => $this->account_number,
        ]);

        Flux::toast(
            text: 'Customer updated successfully',
            variant: 'success',
        );

        return $this->redirectRoute('crm.customers.index', navigate: true);
    }

    public function with(): array
    {
        return [];
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
                
                <div class="flex justify-end gap-3">
                    <flux:button variant="ghost" :href="route('crm.customers.index')" wire:navigate>{{ __('Cancel') }}</flux:button>
                    <flux:button type="submit" variant="primary">{{ __('Save') }}</flux:button>
                </div>
            </form>
        </div>

    </x-pages::shared.layout>

</section>
