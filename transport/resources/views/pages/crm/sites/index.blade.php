<?php
use App\Models\Site;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Sites')] class extends Component {
    use WithPagination;
    public string $search='';
    public function updatedSearch():void{$this->resetPage();}
    public function with():array
    {
        $term='%'.$this->search.'%';
        return ['sites'=>Site::query()->when($this->search!=='',fn($q)=>$q->where(fn($q)=>$q
            ->where('name','like',$term)->orWhere('address_line_1','like',$term)->orWhere('address_line_2','like',$term)
            ->orWhere('town','like',$term)->orWhere('county','like',$term)->orWhere('postcode','like',$term)
            ->orWhere('what_3_words','like',$term)->orWhere('access_instructions','like',$term)))
            ->orderBy('name')->paginate(10)];
    }
}; ?>
<section class="w-full p-6"><flux:heading size="xl">Sites</flux:heading><x-pages::shared.layout contentclass="max-w-5xl"><div class="mb-4 flex justify-between gap-3"><flux:input class="max-w-sm flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search all site fields..."/>@can('crm.site.create')<flux:button variant="primary" :href="route('crm.sites.create')">Add Site</flux:button>@endcan</div><flux:table :paginate="$sites"><flux:table.columns><flux:table.column>Iteration</flux:table.column><flux:table.column>Name</flux:table.column><flux:table.column>Town</flux:table.column><flux:table.column>Postcode</flux:table.column><flux:table.column>W3W</flux:table.column><flux:table.column align="end">Actions</flux:table.column></flux:table.columns><flux:table.rows>@forelse($sites as $site)<flux:table.row :key="$site->id"><flux:table.cell>{{ $sites->firstItem()+$loop->index }}</flux:table.cell><flux:table.cell>{{ $site->name }}</flux:table.cell><flux:table.cell>{{ $site->town?:'—' }}</flux:table.cell><flux:table.cell>{{ $site->postcode }}</flux:table.cell><flux:table.cell>{{ $site->what_3_words?:'—' }}</flux:table.cell><flux:table.cell align="end"><flux:dropdown position="bottom" align="end"><flux:button size="sm" variant="ghost" icon="ellipsis-horizontal" aria-label="Actions for {{ $site->name }}"/><flux:menu>@can('crm.site.update')<flux:menu.item icon="pencil" :href="route('crm.sites.update',$site)">Manage</flux:menu.item>@endcan</flux:menu></flux:dropdown></flux:table.cell></flux:table.row>@empty<flux:table.row><flux:table.cell colspan="6">No sites found</flux:table.cell></flux:table.row>@endforelse</flux:table.rows></flux:table></x-pages::shared.layout></section>
