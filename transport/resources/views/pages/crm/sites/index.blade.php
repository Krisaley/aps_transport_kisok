<?php

use App\Models\Site;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Sites')] class extends Component {
    use WithPagination;
    public string $search = '';
    public function updatedSearch(): void { $this->resetPage(); }
    public function with(): array
    {
        return ['sites' => Site::query()
            ->when($this->search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', '%'.$this->search.'%')->orWhere('postcode', 'like', '%'.$this->search.'%')->orWhere('town', 'like', '%'.$this->search.'%')))
            ->orderBy('name')->paginate(10)];
    }
};
?>

<section class="w-full p-6">
    <flux:heading size="xl">{{ __('Sites') }}</flux:heading>
    <x-pages::shared.layout contentclass="max-w-5xl">
        <div class="mb-4 flex justify-between gap-3"><flux:input class="max-w-sm flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search sites..." />@can('crm.site.create')<flux:button variant="primary" :href="route('crm.sites.create')">Add Site</flux:button>@endcan</div>
        <flux:table :paginate="$sites">
            <flux:table.columns><flux:table.column>{{ __('Name') }}</flux:table.column><flux:table.column>{{ __('Town') }}</flux:table.column><flux:table.column>{{ __('Postcode') }}</flux:table.column><flux:table.column>{{ __('What3Words') }}</flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse ($sites as $site)
                    <flux:table.row :key="$site->id"><flux:table.cell>{{ $site->name }}</flux:table.cell><flux:table.cell>{{ $site->town ?: '—' }}</flux:table.cell><flux:table.cell>{{ $site->postcode }}</flux:table.cell><flux:table.cell>{{ $site->what_3_words ?: '—' }}</flux:table.cell><flux:table.cell>@can('crm.site.update')<flux:button size="sm" variant="ghost" :href="route('crm.sites.update',$site)">Manage</flux:button>@endcan</flux:table.cell></flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="4" class="text-center">{{ __('No sites found') }}</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </x-pages::shared.layout>
</section>
