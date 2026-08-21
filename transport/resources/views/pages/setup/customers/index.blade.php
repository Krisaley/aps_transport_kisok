<?php

use App\Models\Customer;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Customers')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $sortDirection = 'ASC';
    public string $sortBy = 'name';

    public function sort(string $sortBy): void
    {
        if ($this->sortBy === $sortBy)
        {
            $this->sortDirection = $this->sortDirection === 'ASC' ? 'DESC' : 'ASC';
            return;
        }

        $this->sortBy = $sortBy;
        $this->sortDirection = 'ASC';
    }

    public function updated(string $property): void
    {
        if (in_array($property, [
            'search',
        ])) {
            $this->resetPage();
        }
    }
    
    public function with(): array
    {
        return [
            'customers' => Customer::query()
                ->when($this->search !== '', function ($query) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('account_number', 'like', '%'.$search.'%');
                })
                ->orderBy($this->sortBy, $this->sortDirection)
                ->paginate(10),
        ];
    }
};
?>
<section class="w-full">
    @include('partials.setup-heading',[
        'section'   => 'Customers',
    ])

    <flux:heading class="sr-only">{{ __('Customer') }}</flux:heading>

    <x-pages::setup.layout
        :contentclass="__('mt-5 w-full max-w-7xl')"
        >

        <div class="space-y-4">

            {{-- Toolbar --}}
            <div class="flex items-center justify-between gap-4">

                <div class="flex flex-1 items-center gap-2">
                    <div class="w-full max-w-sm">
                        <flux:input
                            wire:model.live.debounce.300ms="search"
                            icon="magnifying-glass"
                            placeholder="Search using name or account number..."
                        />
                    </div>

                    {{-- filter location --}}

                </div>

                <div class="flex shrink-0 gap-2">
                    @can('admin.user.create')
                        <flux:button
                            variant="primary"
                            icon="plus"
                            :href="route('setup.customers.create')"
                        >
                            {{ __('Add Customer') }}
                        </flux:button>
                    @endcan
                </div>

            </div>

            {{-- Table --}}
            <div class="space-y-6">
                <flux:table :paginate="$customers" :pagination:scroll-to>
                    <flux:table.columns sticky>
                        <flux:table.column class="flex items-center gap-3">#</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'account_number'" :direction="$sortDirection" wire:click="sort('account_number')">{{ __('Acc No') }}</flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('Name') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>


                    <flux:table.rows>
                        @forelse ($customers as $customer)
                            <flux:table.row :key="$customer->id">
                                <flux:table.cell class="w-16 text-center">{{ $loop->iteration }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $customer->account_number }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $customer->name }}</flux:table.cell>
                                <flux:table.cell align="end">
                                    <flux:dropdown size="sm" variant="ghost" position="bottom" align="end">
                                        <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                        <flux:menu>
                                            <flux:menu.item icon="pencil" :href="route('setup.customers.update', $customer)">{{ __('Manage') }}</flux:menu.item>
                                        </flux:menu>
                                    </flux:dropdown>
                                </flux:table.cell>
                            </flux:table.row>
                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="py-4 text-center text-zinc-500">
                                    {{ __('No Customers Found') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

        </div>

    </x-pages::settings.layout>
</section>
