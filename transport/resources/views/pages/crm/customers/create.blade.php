<?php

use Livewire\Component;
use Livewire\Attributes\Title;
use App\Models\Customer;
use Flux\Flux;
use Illuminate\Support\Facades\Gate;

new #[Title('Create Customer')] class extends Component {

    public Customer $customer;

    public string $name             = '';
    public string $account_number   = '';

    public function save()
    {
        Gate::authorize('crm.customer.create');

        $this->validate([
            'name'              => ['required', 'string', 'max:255'],
            'account_number'    => ['required', 'string', 'max:255', 'unique:customers,account_number'],
        ]);

        $customer = Customer::create([
            'name'              => $this->name,
            'account_number'    => $this->account_number,
        ]);

        Flux::toast(
            text: 'Customer has been created successfully',
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

    <flux:heading class="sr-only">{{ __('Create Customer') }}</flux:heading>

    <x-pages::shared.layout
        :contentclass="__('mt-5 w-full max-w-3xl')"
        >
    
        <div class="space-y-6">
            <div class="flex items-center justify-between">
                <flux:heading size="lg">{{ __('Create User') }}</flux:heading>
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
