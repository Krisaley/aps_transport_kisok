<?php

use App\Enums\MovementStatus;
use App\Models\{Company, Customer, Equipment, Movement, Site, User, Vehicle};
use App\Services\MovementWorkflow;
use App\Support\CurrentCompany;
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
    public ?int $driver_id = null;
    public ?int $vehicle_id = null;
    public ?string $driver_notes = null;
    public string $newCustomerName = '';
    public string $newCustomerAccount = '';

    public function createCustomer(): void
    {
        Gate::authorize('crm.customer.create');
        $data = $this->validate(['newCustomerName'=>['required','max:255'],'newCustomerAccount'=>['required','max:255','unique:customers,account_number']]);
        $customer = Customer::create(['company_id'=>$this->company_id,'name'=>$data['newCustomerName'],'account_number'=>$data['newCustomerAccount']]);
        $this->customer_id=$customer->id; $this->reset(['newCustomerName','newCustomerAccount']); $this->applyStandardRoute(); Flux::modal('create-customer')->close(); Flux::toast(text:'Customer created and selected',variant:'success');
    }

    public function mount(CurrentCompany $currentCompany, ?Movement $movement = null): void
    {
        $this->movement = $movement;
        $activeCompanyId = $currentCompany->id(Auth::user());

        if ($movement) {
            abort_unless((int) $movement->company_id === $activeCompanyId, 404);
            $movement->load(['actions', 'items.accessories']);

            foreach (['company_id', 'reference', 'movement_type', 'advice_note', 'job_number', 'contact_name', 'contact_number', 'notes', 'driver_notes', 'customer_id'] as $field) {
                $this->{$field} = $movement->{$field};
            }

            $this->status = $movement->status->value;
            $this->actions = $movement->actions->values()->map(fn ($action) => [
                'id' => $action->id,
                'action_type' => in_array($action->action_type->value, ['collection', 'delivery'], true) ? $action->action_type->value : 'collection',
                'site_id' => $action->site_id,
                'contact_name'=>$action->contact_name,'contact_number'=>$action->contact_number,'access_instructions'=>$action->access_instructions,
                'driver_id' => $action->driver_id,
                'vehicle_id' => $action->vehicle_id,
                'schedule_start' => $action->schedule_start?->format('Y-m-d\TH:i'),
                'schedule_end' => $action->schedule_end?->format('Y-m-d\TH:i'),
                'notes' => $action->notes,
            ])->all();
            $this->driver_id=$movement->actions->first()?->driver_id; $this->vehicle_id=$movement->actions->first()?->vehicle_id;

            $actionIndexes = $movement->actions->values()->mapWithKeys(fn ($action, $index) => [$action->id => $index]);
            $this->items = $movement->items->map(function ($item) use ($actionIndexes) {
                $legacyIndex = $item->movement_action_id ? $actionIndexes->get($item->movement_action_id) : null;

                return [
                    'id' => $item->id,
                    'collection_action_index' => $item->collection_action_id ? $actionIndexes->get($item->collection_action_id) : ($item->movement_action === 'collection' ? $legacyIndex : null),
                    'delivery_action_index' => $item->delivery_action_id ? $actionIndexes->get($item->delivery_action_id) : ($item->movement_action === 'delivery' ? $legacyIndex : null),
                    'leg' => $this->movement_type === 'exchange' && $item->collectionAction?->sequence >= 3 ? 'collection' : 'delivery',
                    'equipment_id' => $item->equipment_id,
                    'stock_number' => $item->stock_number,
                    'serial_number' => $item->serial_number,
                    'description' => $item->description,
                    'quantity' => $item->quantity,
                    'accessories' => $item->accessories->map(fn($accessory)=>$accessory->only(['type','description','serial_number','quantity']))->all(),
                ];
            })->all();

            return;
        }

        $this->company_id = $activeCompanyId;
        $this->reference = 'MOV-'.now()->format('Ymd-His');
        $this->applyStandardRoute();
        $this->addItem();
        $equipmentId = request()->integer('equipment');
        if ($equipmentId) {
            $equipment = Equipment::where('company_id', $this->company_id)->find($equipmentId);
            if ($equipment) {
                $this->customer_id = $equipment->customers()->where('company_id', $this->company_id)->value('customers.id');
                $this->applyStandardRoute();
                $this->items[0]['equipment_id'] = $equipment->id;
                $this->fillEquipment(0, $equipment->id);
            }
        }
    }

    public function updatedMovementType(): void
    {
        $this->applyStandardRoute();
    }

    public function updatedCustomerId(): void
    {
        $this->applyStandardRoute();
    }

    public function updatedItems(mixed $value, ?string $key = null): void
    {
        if ($key === null) {
            foreach ($this->items as &$item) { $item['accessories'] = is_array($item['accessories'] ?? null) ? $item['accessories'] : []; $item['quantity'] ??= 1; }
        }
        if ($key && str_ends_with($key, '.leg') && $this->movement_type === 'exchange') {
            $index = (int) (string) str($key)->before('.');
            $this->assignItemRoute($index);
        }
        if ($key && str_ends_with($key,'.equipment_id')) {
            $index=(int) (string) str($key)->before('.');
            $this->fillEquipment($index,$value);
        }
    }

    private function applyStandardRoute(): void
    {
        if ($this->movement?->status->locksRouting()) {
            return;
        }

        $homeSiteId = Company::find($this->company_id)?->home_site_id;
        $customerSiteId = Customer::where('company_id',$this->company_id)->whereKey($this->customer_id)->value('home_site_id') ?: Site::where('company_id', $this->company_id)->where('customer_id', $this->customer_id)->orderBy('name')->value('id');
        $route = match ($this->movement_type) {
            'collection' => [['collection', $customerSiteId], ['delivery', $homeSiteId]],
            'exchange' => [['collection', $homeSiteId], ['delivery', $customerSiteId], ['collection', $customerSiteId], ['delivery', $homeSiteId]],
            'site_to_site' => [['collection', null], ['delivery', null]],
            default => [['collection', $homeSiteId], ['delivery', $customerSiteId]],
        };

        $this->actions = collect($route)->map(fn ($stop) => [
            'id' => null, 'action_type' => $stop[0], 'site_id' => $stop[1], 'contact_name'=>null,'contact_number'=>null,'access_instructions'=>null,'driver_id' => $this->driver_id,
            'vehicle_id' => null, 'schedule_start' => null, 'schedule_end' => null, 'notes' => null,
        ])->all();

        foreach (array_keys($this->items) as $index) {
            $this->assignItemRoute($index);
        }
    }

    private function assignItemRoute(int $index): void
    {
        $leg = $this->items[$index]['leg'] ?? 'delivery';
        [$collection, $delivery] = $this->movement_type === 'exchange' && $leg === 'collection' ? [2, 3] : [0, 1];
        $this->items[$index]['collection_action_index'] = $collection;
        $this->items[$index]['delivery_action_index'] = $delivery;
    }

    public function addAction(string $type = 'collection'): void
    {
        $this->actions[] = [
            'id' => null,
            'action_type' => in_array($type, ['collection', 'delivery'], true) ? $type : 'collection',
            'site_id' => null,
            'contact_name'=>null,'contact_number'=>null,'access_instructions'=>null,
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
            'leg' => 'delivery',
            'equipment_id' => null,
            'collection_action_index' => $collectionIndex === false ? null : $collectionIndex,
            'delivery_action_index' => $deliveryIndex === false ? null : $deliveryIndex,
            'stock_number' => null,
            'serial_number' => null,
            'description' => '',
            'quantity' => 1,
            'accessories' => [],
        ];
    }

    public function addAccessory(int $itemIndex): void { $this->items[$itemIndex]['accessories'][]=['type'=>'custom','description'=>'','serial_number'=>null,'quantity'=>1]; }
    public function removeAccessory(int $itemIndex,int $accessoryIndex): void { unset($this->items[$itemIndex]['accessories'][$accessoryIndex]); $this->items[$itemIndex]['accessories']=array_values($this->items[$itemIndex]['accessories']); }
    private function fillEquipment(int $itemIndex, mixed $value): void
    {
        $equipment=Equipment::with('equipmentModel.make')->where('company_id',$this->company_id)->find($value);
        if(!$equipment)return;
        $this->items[$itemIndex]['stock_number']=$equipment->stock_number;
        $this->items[$itemIndex]['serial_number']=$equipment->serial_number;
        $this->items[$itemIndex]['description']=$equipment->equipmentModel->make->name.' '.$equipment->equipmentModel->name;
    }

    public function removeItem(int $index): void
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
    }

    public function save(CurrentCompany $currentCompany): mixed
    {
        Gate::authorize($this->movement ? 'operations.movement.update' : 'operations.movement.create');
        abort_unless((int) $this->company_id === $currentCompany->id(Auth::user()), 403);
        foreach($this->actions as &$action){$action['driver_id']=$this->driver_id;$action['vehicle_id']=$this->vehicle_id;}
        foreach ($this->items as &$item) {
            $item['equipment_id'] = filled($item['equipment_id'] ?? null) ? (int) $item['equipment_id'] : null;
            $item['stock_number'] = filled($item['stock_number'] ?? null) ? $item['stock_number'] : null;
            $item['serial_number'] = filled($item['serial_number'] ?? null) ? $item['serial_number'] : null;
        }

        $data = $this->validate([
            'company_id' => ['required', 'exists:companies,id'],
            'reference' => ['required', 'max:255', Rule::unique('movements')->ignore($this->movement?->id)],
            'movement_type' => ['required', Rule::in(['delivery', 'collection', 'exchange', 'site_to_site'])],
            'customer_id' => ['required', Rule::exists('customers', 'id')->where('company_id', $this->company_id)],
            'advice_note' => ['nullable', 'max:255'],
            'job_number' => ['nullable', 'max:255'],
            'contact_name' => ['nullable', 'max:255'],
            'contact_number' => ['nullable', 'max:255'],
            'notes' => ['nullable', 'string'],
            'driver_notes'=>['nullable','string','max:2000'],
            'actions' => ['required', 'array', 'min:2'],
            'actions.*.id' => ['nullable'],
            'actions.*.action_type' => ['required', Rule::in(['collection', 'delivery'])],
            'actions.*.site_id' => ['required', Rule::exists('sites', 'id')],
            'actions.*.contact_name'=>['nullable','string','max:255'],'actions.*.contact_number'=>['nullable','string','max:100'],'actions.*.access_instructions'=>['nullable','string','max:2000'],
            'actions.*.driver_id' => ['nullable', 'exists:users,id'],
            'actions.*.vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'actions.*.schedule_start' => ['nullable', 'date'],
            'actions.*.schedule_end' => ['nullable', 'date'],
            'actions.*.notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable'],
            'items.*.equipment_id' => ['nullable',Rule::exists('equipment','id')->where('company_id',$this->company_id)],
            'items.*.leg' => ['nullable', Rule::in(['delivery', 'collection'])],
            'items.*.collection_action_index' => ['required', 'integer', 'min:0'],
            'items.*.delivery_action_index' => ['required', 'integer', 'min:0'],
            'items.*.stock_number' => ['nullable', 'max:255'],
            'items.*.serial_number' => ['nullable', 'max:255'],
            'items.*.description' => ['required', 'max:255'],
            'items.*.quantity' => ['nullable','numeric','min:0.01'],
            'items.*.accessories' => ['nullable','array'],
            'items.*.accessories.*.type' => ['required',Rule::in(['trailer','remote','straps','remote_batteries','keys','outrigger_pads','custom'])],
            'items.*.accessories.*.description' => ['required','max:255'],
            'items.*.accessories.*.serial_number' => ['nullable','max:255'],
            'items.*.accessories.*.quantity' => ['required','numeric','min:0.01'],
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
                    collect($row)->except(['id', 'leg', 'accessories', 'collection_action_index', 'delivery_action_index'])->merge([
                        'movement_action_id' => $deliveryActionId,
                        'collection_action_id' => $collectionActionId,
                        'delivery_action_id' => $deliveryActionId,
                        'movement_action' => 'delivery',
                    ])->all(),
                );
                $itemIds[] = $item->id;
                $item->accessories()->delete();
                foreach($row['accessories'] ?? [] as $accessory) $item->accessories()->create($accessory+['completed'=>false]);
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
            $action->contact_name,$action->contact_number,$action->access_instructions,
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
            $action['contact_name']??null,$action['contact_number']??null,$action['access_instructions']??null,
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
            Gate::authorize('operations.movement.schedule');
        } elseif ($target === MovementStatus::Assigned) {
            Gate::authorize('operations.movement.assign');
        } else {
            Gate::authorize('operations.movement.complete');
        }
        $this->movement = $workflow->transition($this->movement, $target, Auth::user(), $this->transitionReason ?: null);
        $this->status = $this->movement->status->value;
        $this->transitionReason = '';
        Flux::toast(text: 'Status updated', variant: 'success');
    }

    public function with(CurrentCompany $currentCompany): array
    {
        $companyId = $currentCompany->id(Auth::user());
        $company = $companyId
            ? Company::with('homeSite')->findOrFail($companyId)
            : new Company(['name' => 'No active company']);

        return [
            'activeCompany' => $company,
            'customers' => Customer::where('company_id', $companyId)->orderBy('name')->get(),
            'sites' => Site::orderBy('name')->get(),
            'customerSites' => Site::where('company_id', $companyId)->where('customer_id', $this->customer_id)->orderBy('name')->get(),
            'drivers' => User::where(fn ($query) => $query->where('company_id', $companyId)->orWhereHas('companies', fn ($query) => $query->whereKey($companyId)))->whereHas('roles', fn ($query) => $query->where('name', 'Driver'))->where('is_active', true)->orderBy('name')->get(),
            'vehicles' => Vehicle::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get(),
            'equipmentOptions' => Equipment::where('company_id',$companyId)->with('equipmentModel.make')->orderBy('stock_number')->get(),
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
            <div><flux:text>Active company / depot</flux:text><flux:heading size="sm">{{ $activeCompany->name }}</flux:heading></div>
            <flux:input wire:model="reference" label="Reference" required />
            <flux:select wire:model.live="movement_type" label="Type"><flux:select.option value="delivery">Delivery</flux:select.option><flux:select.option value="collection">Collection</flux:select.option><flux:select.option value="exchange">Exchange</flux:select.option><flux:select.option value="site_to_site">Site to site</flux:select.option></flux:select>
            <div class="flex items-end gap-2"><div class="flex-1"><flux:select wire:model.live="customer_id" variant="combobox" clearable label="Customer" placeholder="Search customers..."><flux:select.option value="">Select customer</flux:select.option>@foreach ($customers as $customer)<flux:select.option :value="$customer->id" :wire:key="'movement-customer-'.$customer->id">{{ $customer->name }} · {{ $customer->account_number }}</flux:select.option>@endforeach</flux:select></div><flux:modal.trigger name="create-customer"><flux:button type="button" icon="plus">New</flux:button></flux:modal.trigger></div>
            <flux:input wire:model="advice_note" label="Advice note" /><flux:input wire:model="job_number" label="Job number" /><flux:input wire:model="contact_name" label="Contact" /><flux:input wire:model="contact_number" label="Phone" /><flux:textarea class="md:col-span-3" wire:model="notes" label="Instructions" />
        </flux:card>

        <div><flux:heading size="lg">Addresses</flux:heading><flux:text>The active depot and selected customer are applied automatically for standard movements.</flux:text></div>
        @if (!$activeCompany->home_site_id && $movement_type !== 'site_to_site')<flux:callout variant="warning">Set a home depot site on {{ $activeCompany->name }} before saving standard movements.</flux:callout>@endif
        @error('actions')<flux:callout variant="danger">{{ $message }}</flux:callout>@enderror
        @foreach ($actions as $index => $action)
            <flux:card wire:key="action-{{ $index }}" class="grid gap-4 md:grid-cols-3">
                <div class="md:col-span-3"><flux:heading size="sm">{{ $index + 1 }}. {{ str($action['action_type'])->headline() }}</flux:heading></div>
                <flux:select wire:model.live="actions.{{ $index }}.site_id" variant="combobox" clearable label="Location" placeholder="Search sites or postcodes..."><flux:select.option value="">Select address</flux:select.option>@foreach ($sites as $site)<flux:select.option :value="$site->id" :wire:key="'movement-site-'.$index.'-'.$site->id">{{ $site->name }} — {{ $site->postcode }}</flux:select.option>@endforeach</flux:select>
                <flux:input wire:model="actions.{{ $index }}.schedule_start" type="datetime-local" label="Start / earliest" /><flux:input wire:model="actions.{{ $index }}.schedule_end" type="datetime-local" label="End / latest" /><flux:textarea class="md:col-span-3" wire:model="actions.{{ $index }}.access_instructions" label="Instructions / easy to find"/><flux:textarea class="md:col-span-3" wire:model="actions.{{ $index }}.notes" label="Address notes" /><div class="md:col-span-3"><flux:error name="actions.{{ $index }}.action_type" /></div>
            </flux:card>
        @endforeach

        <div><flux:heading size="lg">Driver and vehicle</flux:heading><flux:text>Assignment applies to the complete movement.</flux:text></div><flux:card class="grid gap-4 md:grid-cols-3"><flux:select wire:model="driver_id" label="Driver"><flux:select.option value="">Unassigned</flux:select.option>@foreach($drivers as $driver)<flux:select.option :value="$driver->id">{{ $driver->name }}</flux:select.option>@endforeach</flux:select><flux:select wire:model="vehicle_id" label="Vehicle"><flux:select.option value="">Unassigned</flux:select.option>@foreach($vehicles as $vehicle)<flux:select.option :value="$vehicle->id">{{ $vehicle->name }} — {{ $vehicle->registration }}{{ $vehicle->capacity_tonnes ? ' · '.$vehicle->capacity_tonnes.'t' : '' }}</flux:select.option>@endforeach</flux:select><flux:textarea wire:model="driver_notes" label="Driver notes"/></flux:card>

        <div class="flex justify-between"><div><flux:heading size="lg">Machines and accessories</flux:heading><flux:text>List the machines included in this {{ str($movement_type)->replace('_', ' ') }}.</flux:text></div><flux:button type="button" wire:click="addItem">Add machine</flux:button></div>
        @error('items')<flux:callout variant="danger">{{ $message }}</flux:callout>@enderror
        @foreach ($items as $index => $item)
            <flux:card wire:key="item-{{ $index }}" class="grid gap-4 md:grid-cols-4">
                <flux:select class="md:col-span-4" wire:model.live="items.{{ $index }}.equipment_id" variant="combobox" clearable label="Equipment (optional for manual lines)" placeholder="Search stock, serial, make or model"><flux:select.option value="">Manual line</flux:select.option>@foreach($equipmentOptions as $equipmentOption)<flux:select.option :value="$equipmentOption->id" :wire:key="'movement-equipment-'.$index.'-'.$equipmentOption->id">{{ $equipmentOption->stock_number }} · {{ $equipmentOption->serial_number }} · {{ $equipmentOption->equipmentModel->make->name }} {{ $equipmentOption->equipmentModel->name }}</flux:select.option>@endforeach</flux:select><flux:input wire:model="items.{{ $index }}.stock_number" label="Stock number" /><flux:input wire:model="items.{{ $index }}.serial_number" label="Serial number" /><flux:input wire:model="items.{{ $index }}.description" label="Description" required /><flux:input wire:model="items.{{ $index }}.quantity" type="number" step="0.01" min="0.01" label="Qty"/>
                @if ($movement_type === 'exchange')<flux:select wire:model.live="items.{{ $index }}.leg" label="Exchange list"><flux:select.option value="delivery">Being delivered</flux:select.option><flux:select.option value="collection">Being collected</flux:select.option></flux:select>@endif
                <div class="md:col-span-4 space-y-2"><div class="flex justify-between"><flux:heading size="sm">Accessories</flux:heading><flux:button type="button" size="xs" wire:click="addAccessory({{ $index }})">Add accessory</flux:button></div>@foreach($item['accessories'] as $accessoryIndex=>$accessory)<div class="grid gap-2 md:grid-cols-5" wire:key="accessory-{{ $index }}-{{ $accessoryIndex }}"><flux:select wire:model="items.{{ $index }}.accessories.{{ $accessoryIndex }}.type"><flux:select.option value="trailer">Trailer</flux:select.option><flux:select.option value="remote">Remote</flux:select.option><flux:select.option value="straps">Straps</flux:select.option><flux:select.option value="remote_batteries">Remote batteries</flux:select.option><flux:select.option value="keys">Keys</flux:select.option><flux:select.option value="outrigger_pads">Outrigger pads</flux:select.option><flux:select.option value="custom">Custom</flux:select.option></flux:select><flux:input class="md:col-span-2" wire:model="items.{{ $index }}.accessories.{{ $accessoryIndex }}.description" placeholder="Description"/><flux:input wire:model="items.{{ $index }}.accessories.{{ $accessoryIndex }}.serial_number" placeholder="Serial"/><div class="flex gap-1"><flux:input wire:model="items.{{ $index }}.accessories.{{ $accessoryIndex }}.quantity" type="number" min="0.01" step="0.01"/><flux:button type="button" variant="danger" size="xs" wire:click="removeAccessory({{ $index }},{{ $accessoryIndex }})">×</flux:button></div></div>@endforeach</div><flux:error name="items.{{ $index }}.collection_action_index" /><flux:error name="items.{{ $index }}.delivery_action_index" /><div class="md:col-span-2 flex justify-end"><flux:button type="button" variant="danger" wire:click="removeItem({{ $index }})">Remove</flux:button></div>
            </flux:card>
        @endforeach

        <div class="sticky bottom-0 flex justify-end gap-3 border-t bg-white/95 p-4"><flux:button :href="route('operations.movements.index')" variant="ghost">Cancel</flux:button><flux:button type="submit" variant="primary">Save movement</flux:button></div>
    </form><flux:modal name="create-customer" class="min-w-[28rem]"><div class="space-y-4"><flux:heading size="lg">Create customer</flux:heading><flux:input wire:model="newCustomerAccount" label="Account number"/><flux:input wire:model="newCustomerName" label="Customer name"/><div class="flex justify-end gap-2"><flux:modal.close><flux:button variant="ghost">Cancel</flux:button></flux:modal.close><flux:button type="button" variant="primary" wire:click="createCustomer">Create and select</flux:button></div></div></flux:modal>
</section>
