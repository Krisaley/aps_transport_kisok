<?php

use App\Enums\MovementStatus;
use App\Models\{Company, Customer, Movement, Site, User, Vehicle};
use App\Services\MovementWorkflow;
use Illuminate\Support\Facades\{Auth, DB, Gate};
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Movement')] class extends Component {
    public ?Movement $movement = null;
    public ?int $company_id = null;
    public string $reference = '';
    public string $movement_type = 'delivery';
    public string $status = 'draft';
    public string $transitionReason = '';
    public ?string $advice_note = null;
    public ?string $job_number = null;
    public ?string $contact_name = null;
    public ?string $contact_number = null;
    public ?string $notes = null;
    public ?int $customer_id = null;
    public array $actions = [];
    public array $items = [];

    public function mount(?Movement $movement = null): void
    {
        $this->movement = $movement;

        if ($movement) {
            abort_unless(Auth::user()->canAccessCompany((int) $movement->company_id), 403);
            $movement->load(['actions', 'items.accessories']);

            foreach (['company_id', 'reference', 'movement_type', 'advice_note', 'job_number', 'contact_name', 'contact_number', 'notes', 'customer_id'] as $field) {
                $this->{$field} = $movement->{$field};
            }

            $this->status = $movement->status->value;
            $this->actions = $movement->actions->values()->map(fn ($action) => [
                'id' => $action->id,
                'action_type' => in_array($action->action_type->value, ['collection', 'delivery'], true) ? $action->action_type->value : 'collection',
                'site_id' => $action->site_id,
                'driver_id' => $action->driver_id,
                'vehicle_id' => $action->vehicle_id,
                'schedule_start' => $action->schedule_start?->format('Y-m-d\TH:i'),
                'schedule_end' => $action->schedule_end?->format('Y-m-d\TH:i'),
                'notes' => $action->notes,
            ])->all();

            $actionIndexes = $movement->actions->values()->mapWithKeys(fn ($action, $index) => [$action->id => $index]);
            $this->items = $movement->items->map(function ($item) use ($actionIndexes) {
                $legacyIndex = $item->movement_action_id ? $actionIndexes->get($item->movement_action_id) : null;

                return [
                    'id' => $item->id,
                    'collection_action_index' => $item->collection_action_id ? $actionIndexes->get($item->collection_action_id) : ($item->movement_action === 'collection' ? $legacyIndex : null),
                    'delivery_action_index' => $item->delivery_action_id ? $actionIndexes->get($item->delivery_action_id) : ($item->movement_action === 'delivery' ? $legacyIndex : null),
                    'stock_number' => $item->stock_number,
                    'serial_number' => $item->serial_number,
                    'description' => $item->description,
                    'accessories' => $item->accessories->pluck('description')->join(', '),
                ];
            })->all();

            return;
        }

        $user = Auth::user();
        $this->company_id = $user->company_id ?? ($user->hasRole('Super-Admin') ? Company::value('id') : $user->companies()->value('companies.id'));
        $this->reference = 'MOV-'.now()->format('Ymd-His');
        $this->addAction('collection');
        $this->addAction('delivery');
        $this->addItem();
    }

    public function addAction(string $type = 'collection'): void
    {
        $this->actions[] = [
            'id' => null,
            'action_type' => in_array($type, ['collection', 'delivery'], true) ? $type : 'collection',
            'site_id' => null,
            'driver_id' => null,
            'vehicle_id' => null,
            'schedule_start' => null,
            'schedule_end' => null,
            'notes' => null,
        ];
    }

    public function removeAction(int $index): void
    {
        if (count($this->actions) <= 2) {
            return;
        }

        unset($this->actions[$index]);
        $this->actions = array_values($this->actions);

        foreach ($this->items as &$item) {
            foreach (['collection_action_index', 'delivery_action_index'] as $field) {
                if ($item[$field] !== null && (int) $item[$field] === $index) {
                    $item[$field] = null;
                } elseif ($item[$field] !== null && (int) $item[$field] > $index) {
                    $item[$field] = (int) $item[$field] - 1;
                }
            }
        }
    }

    public function addItem(): void
    {
        $collectionIndex = collect($this->actions)->search(fn ($action) => $action['action_type'] === 'collection');
        $deliveryIndex = collect($this->actions)->search(fn ($action) => $action['action_type'] === 'delivery');

        $this->items[] = [
            'id' => null,
            'collection_action_index' => $collectionIndex === false ? null : $collectionIndex,
            'delivery_action_index' => $deliveryIndex === false ? null : $deliveryIndex,
            'stock_number' => null,
            'serial_number' => null,
            'description' => '',
            'accessories' => null,
        ];
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(): mixed
    {
        Gate::authorize($this->movement ? 'user.movement.update' : 'user.movement.create');
        abort_unless(Auth::user()->canAccessCompany((int) $this->company_id), 403);

        $data = $this->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'reference' => ['required', 'max:255', Rule::unique('movements')->ignore($this->movement?->id)],
            'movement_type' => ['required', Rule::in(['delivery', 'collection', 'site_to_site'])],
            'customer_id' => ['required', 'exists:customers,id'],
            'advice_note' => ['nullable', 'max:255'],
            'job_number' => ['nullable', 'max:255'],
            'contact_name' => ['nullable', 'max:255'],
            'contact_number' => ['nullable', 'max:255'],
            'notes' => ['nullable', 'string'],
            'actions' => ['required', 'array', 'min:2'],
            'actions.*.id' => ['nullable'],
            'actions.*.action_type' => ['required', Rule::in(['collection', 'delivery'])],
            'actions.*.site_id' => ['required', 'exists:sites,id'],
            'actions.*.driver_id' => ['nullable', 'exists:users,id'],
            'actions.*.vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'actions.*.schedule_start' => ['nullable', 'date'],
            'actions.*.schedule_end' => ['nullable', 'date'],
            'actions.*.notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable'],
            'items.*.collection_action_index' => ['required', 'integer', 'min:0'],
            'items.*.delivery_action_index' => ['required', 'integer', 'min:0'],
            'items.*.stock_number' => ['nullable', 'max:255'],
            'items.*.serial_number' => ['nullable', 'max:255'],
            'items.*.description' => ['required', 'max:255'],
            'items.*.accessories' => ['nullable', 'max:2000'],
        ]);

        $this->validateItemRoutes($data);

        if ($this->movement && $this->movement->status->locksRouting() && ($this->actionsChanged() || $this->itemsChanged())) {
            $this->addError('actions', 'Return this movement to awaiting schedule before changing route actions or machines.');

            return null;
        }

        DB::transaction(function () use ($data) {
            $first = $data['actions'][0];
            $last = $data['actions'][array_key_last($data['actions'])];
            $movementData = collect($data)->except(['actions', 'items'])->merge([
                'status' => $this->movement?->status ?? MovementStatus::Draft,
                'planned_date' => filled($first['schedule_start']) ? substr($first['schedule_start'], 0, 10) : null,
                'schedule_start' => $first['schedule_start'],
                'schedule_end' => $last['schedule_end'],
                'collection_site_id' => $first['site_id'],
                'delivery_site_id' => $last['site_id'],
                'driver_id' => $first['driver_id'],
                'vehicle_id' => $first['vehicle_id'],
                'updated_by' => Auth::id(),
            ])->all();

            if ($this->movement) {
                $this->movement->update($movementData);
            } else {
                $movementData['created_by'] = Auth::id();
                $this->movement = Movement::create($movementData);
            }

            $actionIds = [];
            foreach ($data['actions'] as $index => $row) {
                $action = $this->movement->actions()->updateOrCreate(
                    ['id' => $row['id'] ?? null],
                    collect($row)->except('id')->merge(['sequence' => $index + 1])->all(),
                );
                $actionIds[] = $action->id;
            }

            $this->movement->actions()->whereNotIn('id', $actionIds)->delete();

            $itemIds = [];
            foreach ($data['items'] as $row) {
                $collectionActionId = $actionIds[$row['collection_action_index']];
                $deliveryActionId = $actionIds[$row['delivery_action_index']];
                $item = $this->movement->items()->updateOrCreate(
                    ['id' => $row['id'] ?? null],
                    collect($row)->except(['id', 'accessories', 'collection_action_index', 'delivery_action_index'])->merge([
                        'movement_action_id' => $deliveryActionId,
                        'collection_action_id' => $collectionActionId,
                        'delivery_action_id' => $deliveryActionId,
                        'movement_action' => 'delivery',
                    ])->all(),
                );
                $itemIds[] = $item->id;
                $item->accessories()->delete();
                if (filled($row['accessories'])) {
                    $item->accessories()->create(['type' => 'standard', 'description' => $row['accessories'], 'completed' => false]);
                }
            }

            $this->movement->items()->whereNotIn('id', $itemIds)->delete();
        });

        Flux::toast(text: 'Movement saved', variant: 'success');

        return $this->redirectRoute('operations.movements.index', navigate: true);
    }

    private function validateItemRoutes(array $data): void
    {
        $errors = [];
        $usedActions = [];

        foreach ($data['items'] as $index => $item) {
            $collectionIndex = $item['collection_action_index'];
            $deliveryIndex = $item['delivery_action_index'];
            $collection = $data['actions'][$collectionIndex] ?? null;
            $delivery = $data['actions'][$deliveryIndex] ?? null;

            if (! $collection || $collection['action_type'] !== 'collection') {
                $errors["items.{$index}.collection_action_index"] = 'Choose a valid collection action.';
            }
            if (! $delivery || $delivery['action_type'] !== 'delivery') {
                $errors["items.{$index}.delivery_action_index"] = 'Choose a valid delivery action.';
            }
            if ($collection && $delivery && $deliveryIndex <= $collectionIndex) {
                $errors["items.{$index}.delivery_action_index"] = 'Delivery must occur after collection.';
            }

            $usedActions[$collectionIndex] = true;
            $usedActions[$deliveryIndex] = true;
        }

        foreach (array_keys($data['actions']) as $index) {
            if (! isset($usedActions[$index])) {
                $errors["actions.{$index}.action_type"] = 'Each route action must collect or deliver at least one machine.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function itemsChanged(): bool
    {
        $actionIds = collect($this->actions)->pluck('id');
        $old = $this->movement->items->map(fn ($item) => [$item->id, $item->stock_number, $item->serial_number, $item->description, $item->collection_action_id, $item->delivery_action_id])->values()->all();
        $new = collect($this->items)->map(fn ($item) => [$item['id'], $item['stock_number'], $item['serial_number'], $item['description'], $actionIds->get($item['collection_action_index']), $actionIds->get($item['delivery_action_index'])])->values()->all();

        return $old !== $new;
    }

    private function actionsChanged(): bool
    {
        $old = $this->movement->actions->map(fn ($action) => [
            (string) $action->id,
            $action->action_type->value,
            (string) $action->site_id,
            $action->driver_id ? (string) $action->driver_id : null,
            $action->vehicle_id ? (string) $action->vehicle_id : null,
            $action->schedule_start?->format('Y-m-d\TH:i'),
            $action->schedule_end?->format('Y-m-d\TH:i'),
            $action->notes,
        ])->values()->all();
        $new = collect($this->actions)->map(fn ($action) => [
            (string) $action['id'],
            $action['action_type'],
            (string) $action['site_id'],
            $action['driver_id'] ? (string) $action['driver_id'] : null,
            $action['vehicle_id'] ? (string) $action['vehicle_id'] : null,
            $action['schedule_start'] ?: null,
            $action['schedule_end'] ?: null,
            $action['notes'] ?: null,
        ])->values()->all();

        return $old !== $new;
    }

    public function changeStatus(string $to, MovementWorkflow $workflow): void
    {
        abort_unless($this->movement, 404);
        $target = MovementStatus::from($to);
        if (in_array($target, [MovementStatus::Scheduled, MovementStatus::AwaitingSchedule, MovementStatus::OnHold, MovementStatus::Cancelled], true)) {
            Gate::authorize('user.movement.schedule');
        } elseif ($target === MovementStatus::Assigned) {
            Gate::authorize('user.movement.assign');
        } else {
            Gate::authorize('user.movement.complete');
        }
        $this->movement = $workflow->transition($this->movement, $target, Auth::user(), $this->transitionReason ?: null);
        $this->status = $this->movement->status->value;
        $this->transitionReason = '';
        Flux::toast(text: 'Status updated', variant: 'success');
    }

    public function with(): array
    {
        $user = Auth::user();
        $companyIds = $user->hasRole('Super-Admin') ? Company::pluck('id') : $user->companies()->pluck('companies.id')->push($user->company_id)->filter()->unique();

        return [
            'companies' => Company::whereIn('id', $companyIds)->where('is_active', true)->orderBy('name')->get(),
            'customers' => Customer::orderBy('name')->get(),
            'sites' => Site::orderBy('name')->get(),
            'drivers' => User::whereHas('roles', fn ($query) => $query->where('name', 'Driver'))->where('is_active', true)->orderBy('name')->get(),
            'vehicles' => Vehicle::where('is_active', true)->orderBy('name')->get(),
        ];
    }
};
?>

<section class="w-full p-4 sm:p-6">
    <div class="mb-5 flex justify-between"><div><flux:heading size="xl">{{ $movement ? 'Edit movement' : 'Create movement' }}</flux:heading><flux:text>{{ str($status)->headline() }}</flux:text></div><flux:button :href="route('operations.movements.index')" variant="ghost">Back</flux:button></div>

    @if ($movement)
        <flux:card class="mx-auto mb-6 max-w-6xl">
            <flux:heading size="sm">Workflow</flux:heading><div class="mt-3 flex flex-wrap gap-2">@foreach ($movement->status->next() as $next)<flux:button type="button" wire:click="changeStatus('{{ $next->value }}')" variant="{{ in_array($next, [\App\Enums\MovementStatus::Cancelled, \App\Enums\MovementStatus::OnHold]) ? 'danger' : 'primary' }}">{{ $next->label() }}</flux:button>@endforeach</div>
            <flux:textarea class="mt-3" wire:model="transitionReason" label="Reason (required for postponing, hold, cancellation, or rescheduling)" />
            @foreach (['status', 'reason', 'schedule', 'assignment'] as $error)<flux:error :name="$error" />@endforeach
            <hr class="my-4"><flux:heading size="sm">Documents</flux:heading><div class="mt-3 flex flex-wrap gap-2">@foreach (\App\Services\MovementDocumentService::TYPES as $documentType)<a class="rounded border px-3 py-2 text-sm" target="_blank" href="{{ route('documents.preview', [$movement, $documentType]) }}">Preview {{ str($documentType)->headline() }}</a>@can('user.document.issue')<form method="POST" action="{{ route('documents.issue', [$movement, $documentType]) }}">@csrf<button class="rounded bg-slate-800 px-3 py-2 text-sm text-white" type="submit">Issue PDF</button></form>@endcan @endforeach</div>
        </flux:card>
    @endif

    <form wire:submit="save" class="mx-auto max-w-6xl space-y-6">
        <flux:card class="grid gap-4 md:grid-cols-3">
            <flux:select wire:model="company_id" label="Company">@foreach ($companies as $company)<flux:select.option :value="$company->id">{{ $company->name }}</flux:select.option>@endforeach</flux:select>
            <flux:input wire:model="reference" label="Reference" required />
            <flux:select wire:model="movement_type" label="Type"><flux:select.option value="delivery">Delivery</flux:select.option><flux:select.option value="collection">Collection</flux:select.option><flux:select.option value="site_to_site">Site to site</flux:select.option></flux:select>
            <flux:select wire:model="customer_id" label="Customer">@foreach ($customers as $customer)<flux:select.option :value="$customer->id">{{ $customer->name }}</flux:select.option>@endforeach</flux:select>
            <flux:input wire:model="advice_note" label="Advice note" /><flux:input wire:model="job_number" label="Job number" /><flux:input wire:model="contact_name" label="Contact" /><flux:input wire:model="contact_number" label="Phone" /><flux:textarea class="md:col-span-3" wire:model="notes" label="Instructions" />
        </flux:card>

        <div class="flex justify-between"><div><flux:heading size="lg">Route actions</flux:heading><flux:text>Use only Collect and Deliver actions, in journey order.</flux:text></div><div class="flex gap-2"><flux:button type="button" wire:click="addAction('collection')">Add collection</flux:button><flux:button type="button" wire:click="addAction('delivery')">Add delivery</flux:button></div></div>
        @error('actions')<flux:callout variant="danger">{{ $message }}</flux:callout>@enderror
        @foreach ($actions as $index => $action)
            <flux:card wire:key="action-{{ $index }}" class="grid gap-4 md:grid-cols-3">
                <div class="md:col-span-3 flex items-center justify-between"><flux:heading size="sm">{{ $index + 1 }}. {{ str($action['action_type'])->headline() }}</flux:heading>@if(count($actions) > 2)<flux:button type="button" variant="danger" wire:click="removeAction({{ $index }})">Remove</flux:button>@endif</div>
                <flux:select wire:model.live="actions.{{ $index }}.action_type" label="Action"><flux:select.option value="collection">Collect</flux:select.option><flux:select.option value="delivery">Deliver</flux:select.option></flux:select>
                <flux:select wire:model.live="actions.{{ $index }}.site_id" label="Location">@foreach ($sites as $site)<flux:select.option :value="$site->id">{{ $site->name }} — {{ $site->postcode }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="actions.{{ $index }}.driver_id" label="Driver"><flux:select.option value="">Unassigned</flux:select.option>@foreach ($drivers as $driver)<flux:select.option :value="$driver->id">{{ $driver->name }}</flux:select.option>@endforeach</flux:select>
                <flux:select wire:model="actions.{{ $index }}.vehicle_id" label="Vehicle"><flux:select.option value="">Unassigned</flux:select.option>@foreach ($vehicles as $vehicle)<flux:select.option :value="$vehicle->id">{{ $vehicle->name }} — {{ $vehicle->registration }}</flux:select.option>@endforeach</flux:select>
                <flux:input wire:model="actions.{{ $index }}.schedule_start" type="datetime-local" label="Start / earliest" /><flux:input wire:model="actions.{{ $index }}.schedule_end" type="datetime-local" label="End / latest" /><flux:textarea class="md:col-span-3" wire:model="actions.{{ $index }}.notes" label="Notes" /><div class="md:col-span-3"><flux:error name="actions.{{ $index }}.action_type" /></div>
            </flux:card>
        @endforeach

        <div class="flex justify-between"><div><flux:heading size="lg">Machines and accessories</flux:heading><flux:text>Choose where each machine is collected and where it is delivered.</flux:text></div><flux:button type="button" wire:click="addItem">Add machine</flux:button></div>
        @error('items')<flux:callout variant="danger">{{ $message }}</flux:callout>@enderror
        @foreach ($items as $index => $item)
            <flux:card wire:key="item-{{ $index }}" class="grid gap-4 md:grid-cols-4">
                <flux:input wire:model="items.{{ $index }}.stock_number" label="Stock number" /><flux:input wire:model="items.{{ $index }}.serial_number" label="Serial number" /><flux:input class="md:col-span-2" wire:model="items.{{ $index }}.description" label="Description" required />
                <flux:select wire:model="items.{{ $index }}.collection_action_index" label="Collect at">@foreach ($actions as $actionIndex => $routeAction) @if ($routeAction['action_type'] === 'collection')<flux:select.option :value="$actionIndex">{{ $actionIndex + 1 }}. {{ $sites->firstWhere('id', $routeAction['site_id'])?->name ?? 'Select location' }}</flux:select.option>@endif @endforeach</flux:select>
                <flux:select wire:model="items.{{ $index }}.delivery_action_index" label="Deliver at">@foreach ($actions as $actionIndex => $routeAction) @if ($routeAction['action_type'] === 'delivery')<flux:select.option :value="$actionIndex">{{ $actionIndex + 1 }}. {{ $sites->firstWhere('id', $routeAction['site_id'])?->name ?? 'Select location' }}</flux:select.option>@endif @endforeach</flux:select>
                <flux:input class="md:col-span-2" wire:model="items.{{ $index }}.accessories" label="Accessories" /><flux:error name="items.{{ $index }}.collection_action_index" /><flux:error name="items.{{ $index }}.delivery_action_index" /><div class="md:col-span-2 flex justify-end"><flux:button type="button" variant="danger" wire:click="removeItem({{ $index }})">Remove</flux:button></div>
            </flux:card>
        @endforeach

        <div class="sticky bottom-0 flex justify-end gap-3 border-t bg-white/95 p-4"><flux:button :href="route('operations.movements.index')" variant="ghost">Cancel</flux:button><flux:button type="submit" variant="primary">Save movement</flux:button></div>
    </form>
</section>
