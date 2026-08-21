<?php

use App\Models\Movement;
use Carbon\CarbonImmutable;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Movements')] class extends Component {
    use WithPagination;

    public string $search = '';
    public string $statusFilter = 'all';
    public string $typeFilter = 'all';
    #[Session(key: 'operations.movements.calendar-view')]
    public string $calendarView = 'week';
    public string $anchorDate = '';

    public function mount(): void { $this->anchorDate = now()->toDateString(); }
    public function movePeriod(int $direction): void { $date=CarbonImmutable::parse($this->anchorDate); $this->anchorDate=match($this->calendarView){'day'=>$date->addDays($direction)->toDateString(),'month'=>$date->addMonths($direction)->toDateString(),default=>$date->addWeeks($direction)->toDateString()}; }
    public function goToday(): void { $this->anchorDate=now()->toDateString(); }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'statusFilter', 'typeFilter'], true)) {
            $this->resetPage();
        }
    }

    public function with(): array
    {
        $anchor=CarbonImmutable::parse($this->anchorDate ?: now());
        [$start,$end]=match($this->calendarView){'day'=>[$anchor->startOfDay(),$anchor->endOfDay()],'month'=>[$anchor->startOfMonth()->startOfWeek(),$anchor->endOfMonth()->endOfWeek()],default=>[$anchor->startOfWeek(),$anchor->endOfWeek()]};
        $scheduled=Movement::query()->with(['customer','actions.site','actions.driver','actions.vehicle'])->whereBetween('schedule_start',[$start,$end])->orderBy('schedule_start')->get()->groupBy(fn($m)=>$m->schedule_start->toDateString());
        $days=collect(\Carbon\CarbonPeriod::create($start->startOfDay(),$end->startOfDay()))->map(fn($d)=>CarbonImmutable::instance($d));
        return ['scheduled'=>$scheduled,'calendarDays'=>$days,'rangeLabel'=>$this->calendarView==='day'?$start->format('l d F Y'):($this->calendarView==='month'?$anchor->format('F Y'):$start->format('d M').' – '.$end->format('d M Y')),'movements' => Movement::query()
            ->with(['customer', 'deliverySite', 'collectionSite', 'driver', 'vehicle'])
            ->when($this->search !== '', function ($query) {
                $query->where(function ($query) {
                    $query->where('reference', 'like', '%'.$this->search.'%')
                        ->orWhere('advice_note', 'like', '%'.$this->search.'%')
                        ->orWhere('job_number', 'like', '%'.$this->search.'%')
                        ->orWhereHas('customer', fn ($query) => $query->where('name', 'like', '%'.$this->search.'%'));
                });
            })
            ->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->typeFilter !== 'all', fn ($query) => $query->where('movement_type', $this->typeFilter))
            ->orderByDesc('planned_date')->orderByDesc('id')->paginate(10)];
    }
};
?>

<section class="w-full p-6">
    <flux:heading size="xl">{{ __('Movements') }}</flux:heading>
    <x-pages::operations.layout contentclass="max-w-6xl">
        <div class="mb-4 flex flex-wrap gap-2">
            <flux:input class="min-w-64 flex-1" wire:model.live.debounce.300ms="search" icon="magnifying-glass" placeholder="Search movements..." />
            <flux:select wire:model.live="statusFilter"><flux:select.option value="all">{{ __('All statuses') }}</flux:select.option>@foreach(\App\Enums\MovementStatus::cases() as $status)<flux:select.option :value="$status->value">{{ $status->label() }}</flux:select.option>@endforeach</flux:select>
            <flux:select wire:model.live="typeFilter"><flux:select.option value="all">{{ __('All types') }}</flux:select.option><flux:select.option value="delivery">{{ __('Delivery') }}</flux:select.option><flux:select.option value="collection">{{ __('Collection') }}</flux:select.option><flux:select.option value="site_to_site">{{ __('Site to site') }}</flux:select.option></flux:select>
            @can('user.movement.create')<flux:button variant="primary" :href="route('operations.movements.create')">Add Movement</flux:button>@endcan
        </div>
        <flux:card class="mb-5">
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div class="flex gap-2"><flux:button size="sm" wire:click="movePeriod(-1)">Previous</flux:button><flux:button size="sm" wire:click="goToday">Today</flux:button><flux:button size="sm" wire:click="movePeriod(1)">Next</flux:button></div><flux:heading size="lg">{{ $rangeLabel }}</flux:heading><flux:select wire:model.live="calendarView" class="w-36"><flux:select.option value="day">Day</flux:select.option><flux:select.option value="week">Week</flux:select.option><flux:select.option value="month">Month</flux:select.option></flux:select></div>
            <div class="grid gap-2 {{ $calendarView === 'day' ? 'grid-cols-1' : 'grid-cols-2 md:grid-cols-7' }}">@foreach($calendarDays as $day)<div class="min-h-32 rounded-lg border p-2 {{ $day->isToday() ? 'border-blue-500 bg-blue-50' : '' }}"><p class="mb-2 text-xs font-semibold uppercase text-zinc-500">{{ $day->format('D d') }}</p>@foreach($scheduled->get($day->toDateString(),collect()) as $job)<a href="{{ route('operations.movements.update',$job) }}" class="mb-2 block rounded bg-zinc-100 p-2 text-xs hover:bg-zinc-200"><strong>{{ $job->schedule_start->format('H:i') }} {{ $job->reference }}</strong><br>{{ $job->customer->name }}<br>{{ $job->actions->first()?->site?->name }} → {{ $job->actions->last()?->site?->name }} · {{ $job->actions->count() }} actions<br>{{ $job->driver?->name ?: 'Unassigned' }}</a>@endforeach</div>@endforeach</div>
        </flux:card>
        <flux:table :paginate="$movements">
            <flux:table.columns><flux:table.column>{{ __('Reference') }}</flux:table.column><flux:table.column>{{ __('Type') }}</flux:table.column><flux:table.column>{{ __('Customer') }}</flux:table.column><flux:table.column>{{ __('Planned') }}</flux:table.column><flux:table.column>{{ __('Driver') }}</flux:table.column><flux:table.column>{{ __('Status') }}</flux:table.column></flux:table.columns>
            <flux:table.rows>
                @forelse ($movements as $movement)
                    <flux:table.row :key="$movement->id"><flux:table.cell>{{ $movement->reference }}</flux:table.cell><flux:table.cell>{{ str($movement->movement_type)->headline() }}</flux:table.cell><flux:table.cell>{{ $movement->customer->name }}</flux:table.cell><flux:table.cell>{{ $movement->schedule_start?->format('d M Y H:i') ?: '—' }}</flux:table.cell><flux:table.cell>{{ $movement->driver?->name ?: '—' }}</flux:table.cell><flux:table.cell>{{ $movement->status->label() }}</flux:table.cell><flux:table.cell>@can('user.movement.update')<flux:button size="sm" variant="ghost" :href="route('operations.movements.update',$movement)">Manage</flux:button>@endcan</flux:table.cell></flux:table.row>
                @empty
                    <flux:table.row><flux:table.cell colspan="6" class="text-center">{{ __('No movements found') }}</flux:table.cell></flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </x-pages::operations.layout>
</section>
