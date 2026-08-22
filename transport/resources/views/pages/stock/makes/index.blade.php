<?php

use App\Models\Make;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Makes & Models')] class extends Component
{
    use WithPagination;

    public string $search = '';

    public string $sortDirection = 'ASC';

    public string $sortBy = 'name';

    public array $expandedMakes = [];

    public function sort(string $sortBy): void
    {
        if ($sortBy !== 'name') {
            return;
        }

        if ($this->sortBy === $sortBy) {
            $this->sortDirection = $this->sortDirection === 'ASC' ? 'DESC' : 'ASC';

            return;
        }

        $this->sortBy = $sortBy;
        $this->sortDirection = 'ASC';
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleMake(int $makeId): void
    {
        if (in_array($makeId, $this->expandedMakes, true)) {
            $this->expandedMakes = array_values(array_diff($this->expandedMakes, [$makeId]));

            return;
        }

        $this->expandedMakes[] = $makeId;
    }

    public function with(): array
    {
        $search = trim($this->search);

        $query = Make::query()
            ->select('makes.*')
            ->withCount('equipmentModels')
            ->orderBy($this->sortBy, $this->sortDirection);

        if ($search === '') {
            $query->with([
                'equipmentModels' => fn ($query) => $query->orderBy('name'),
            ]);
        } else {
            $like = '%'.$search.'%';

            $query
                ->selectRaw('CASE WHEN makes.name LIKE ? THEN 1 ELSE 0 END AS make_matches_search', [$like])
                ->where(function ($query) use ($like) {
                    $query->where('makes.name', 'like', $like)
                        ->orWhereHas('equipmentModels', fn ($query) => $query->where('name', 'like', $like));
                })
                ->with([
                    'equipmentModels' => fn ($query) => $query->orderBy('name'),
                    'matchingEquipmentModels' => fn ($query) => $query
                        ->where('name', 'like', $like)
                        ->orderBy('name'),
                ]);
        }

        return [
            'makes' => $query->paginate(10),
            'normalizedSearch' => $search,
        ];
    }
};
?>

<section class="w-full">
    @include('partials.setup-heading',[
        'section'   => 'Makes & Models',
    ])

    <flux:heading class="sr-only">{{ __('Makes and Models') }}</flux:heading>

    <x-pages::shared.layout
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
                            placeholder="Search using make or model..."
                        />
                    </div>

                    {{-- filter location --}}

                </div>

                <div class="flex shrink-0 gap-2">
                    @can('stock.make-model.create')
                        <flux:button
                            variant="primary"
                            icon="plus"
                            :href="route('stock.makes.create')"
                        >
                            {{ __('Add Make') }}
                        </flux:button>
                    @endcan
                </div>

            </div>

            {{-- Table --}}
            <div class="space-y-6">
                <flux:table :paginate="$makes" :pagination:scroll-to>
                    <flux:table.columns sticky>
                        <flux:table.column><span class="sr-only">{{ __('Expand') }}</span></flux:table.column>
                        <flux:table.column sortable :sorted="$sortBy === 'name'" :direction="$sortDirection" wire:click="sort('name')">{{ __('Manufacturer') }}</flux:table.column>
                        <flux:table.column>{{ __('Models') }}</flux:table.column>
                        <flux:table.column align="end">{{ __('Actions') }}</flux:table.column>
                    </flux:table.columns>


                    <flux:table.rows>
                        @forelse ($makes as $make)
                            @php
                                $searching = $normalizedSearch !== '';
                                $makeMatchesSearch = $searching && (bool) $make->make_matches_search;
                                $isExpanded = $searching || in_array($make->id, $expandedMakes, true);
                                $visibleModels = $makeMatchesSearch
                                    ? $make->equipmentModels
                                    : ($searching ? $make->matchingEquipmentModels : $make->equipmentModels);
                            @endphp
                            <flux:table.row :key="$make->id">
                                <flux:table.cell class="w-16 text-center">
                                    <flux:button
                                        variant="ghost"
                                        size="sm"
                                        icon="chevron-right"
                                        wire:click="toggleMake({{ $make->id }})"
                                        aria-label="{{ $isExpanded ? __('Collapse :make models', ['make' => $make->name]) : __('Expand :make models', ['make' => $make->name]) }}"
                                        aria-expanded="{{ $isExpanded ? 'true' : 'false' }}"
                                        class="transition-transform {{ $isExpanded ? 'rotate-90' : '' }}"
                                    />
                                </flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">{{ $make->name }}</flux:table.cell>
                                <flux:table.cell class="whitespace-nowrap">
                                    @if ($searching && ! $makeMatchesSearch && $visibleModels->count() !== $make->equipment_models_count)
                                        {{ $visibleModels->count() }} {{ __('of') }} {{ $make->equipment_models_count }}
                                    @else
                                        {{ $make->equipment_models_count }}
                                    @endif
                                </flux:table.cell>
                                <flux:table.cell align="end">
                                    @can('stock.make-model.update')
                                        <flux:dropdown size="sm" variant="ghost" position="bottom" align="end">
                                            <flux:button icon="ellipsis-horizontal" variant="ghost" size="sm" />
                                            <flux:menu>
                                                <flux:menu.item icon="pencil" :href="route('stock.makes.update', $make)">{{ __('Manage') }}</flux:menu.item>
                                                <flux:menu.item icon="plus" :href="route('stock.makes.models', $make)">{{ __('Add / manage models') }}</flux:menu.item>
                                            </flux:menu>
                                        </flux:dropdown>
                                    @endcan
                                </flux:table.cell>
                            </flux:table.row>

                            @if ($isExpanded)
                                <flux:table.row :key="'models-'.$make->id">
                                    <flux:table.cell colspan="4" class="bg-zinc-50/70 dark:bg-zinc-800/30">
                                        <div class="divide-y divide-zinc-200/70 py-1 pl-12 dark:divide-zinc-700/70">
                                            @forelse ($visibleModels as $model)
                                                <div class="flex items-center justify-between gap-4 py-2 text-sm text-zinc-700 dark:text-zinc-300">
                                                    <span>{{ $model->name }}</span>
                                                    @can('stock.make-model.update')
                                                        <flux:button size="xs" variant="ghost" :href="route('stock.models.update', $model)">
                                                            {{ __('Manage') }}
                                                        </flux:button>
                                                    @endcan
                                                </div>
                                            @empty
                                                <div class="py-2 text-sm text-zinc-500">{{ __('No models found') }}</div>
                                            @endforelse
                                        </div>
                                    </flux:table.cell>
                                </flux:table.row>
                            @endif

                        @empty
                            <flux:table.row>
                                <flux:table.cell colspan="4" class="py-4 text-center text-zinc-500">
                                    {{ __('No Makes Found') }}
                                </flux:table.cell>
                            </flux:table.row>
                        @endforelse
                    </flux:table.rows>
                </flux:table>
            </div>

        </div>

    </x-pages::shared.layout>
</section>
