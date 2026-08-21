<?php

namespace App\Http\Controllers;

use App\Enums\MovementActionType;
use App\Enums\MovementStatus;
use App\Models\Company;
use App\Models\Customer;
use App\Models\Movement;
use App\Models\MovementPhoto;
use App\Models\Site;
use App\Services\MovementWorkflow;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PwaController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(Gate::allows('pwa.driver') || Gate::allows('pwa.yard_receipt'), 403);

        return view('pwa.index', [
            'bootstrap' => $this->bootstrapData($request),
        ]);
    }

    public function bootstrap(Request $request): JsonResponse
    {
        abort_unless(Gate::allows('pwa.driver') || Gate::allows('pwa.yard_receipt'), 403);
        return response()->json($this->bootstrapData($request));
    }

    public function sync(Request $request, MovementWorkflow $workflow): JsonResponse
    {
        abort_unless(Gate::allows('pwa.driver') || Gate::allows('pwa.yard_receipt'), 403);
        $validated = $request->validate([
            'operations' => ['required', 'array', 'max:50'],
            'operations.*.id' => ['required', 'uuid'],
            'operations.*.type' => ['required', Rule::in(['transition', 'yard_receipt'])],
            'operations.*.payload' => ['required', 'array'],
        ]);

        $results = [];
        foreach ($validated['operations'] as $operation) {
            $prior = DB::table('mobile_sync_operations')->where('operation_id', $operation['id'])->first();
            if ($prior) {
                $results[] = ['id' => $operation['id'], 'ok' => true, 'replayed' => true, 'result' => json_decode($prior->result ?? '{}', true)];
                continue;
            }

            try {
                $result = DB::transaction(function () use ($operation, $request, $workflow) {
                    $result = $operation['type'] === 'yard_receipt'
                        ? $this->createYardReceipt($operation['payload'], $request)
                        : $this->transition($operation['payload'], $request, $workflow);

                    DB::table('mobile_sync_operations')->insert([
                        'operation_id' => $operation['id'], 'user_id' => $request->user()->id,
                        'operation_type' => $operation['type'], 'result' => json_encode($result), 'processed_at' => now(),
                    ]);
                    return $result;
                });
                $results[] = ['id' => $operation['id'], 'ok' => true, 'result' => $result];
            } catch (\Throwable $exception) {
                report($exception);
                $results[] = ['id' => $operation['id'], 'ok' => false, 'message' => $exception instanceof \Illuminate\Validation\ValidationException
                    ? collect($exception->errors())->flatten()->first() : 'This change needs manager review.'];
            }
        }

        return response()->json(['results' => $results, 'server_time' => now()->toIso8601String()]);
    }

    private function transition(array $payload, Request $request, MovementWorkflow $workflow): array
    {
        abort_unless(Gate::allows('pwa.driver'), 403);
        $data = validator($payload, [
            'movement_id' => ['required', 'integer', 'exists:movements,id'],
            'to_status' => ['required', Rule::enum(MovementStatus::class)],
            'reason' => ['nullable', 'string', 'max:2000'],
            'recipient_name' => ['nullable', 'string', 'max:255'],
            'signature' => ['nullable', 'string'],
            'photos' => ['nullable', 'array', 'max:12'],
            'photos.*' => ['string'],
            'expected_lock_version' => ['required', 'integer'],
        ])->validate();

        $movement = Movement::query()->lockForUpdate()->findOrFail($data['movement_id']);
        abort_unless($movement->driver_id === $request->user()->id || $movement->actions()->where('driver_id', $request->user()->id)->exists() || Gate::allows('user.movement.complete'), 403);
        if ($movement->lock_version !== $data['expected_lock_version']) {
            throw \Illuminate\Validation\ValidationException::withMessages(['sync' => 'The job changed while this device was offline. Manager review is required.']);
        }

        if (! empty($data['recipient_name'])) {
            $movement->update(['contact_name' => $data['recipient_name'], 'updated_by' => $request->user()->id]);
        }
        $this->storeEvidence($movement, $data, $request);
        $movement = $workflow->transition($movement, MovementStatus::from($data['to_status']), $request->user(), $data['reason'] ?? null);
        return ['movement_id' => $movement->id, 'status' => $movement->status->value, 'lock_version' => $movement->lock_version];
    }

    private function createYardReceipt(array $payload, Request $request): array
    {
        abort_unless(Gate::allows('pwa.yard_receipt'), 403);
        $data = validator($payload, [
            'company_id' => ['required', 'integer', 'exists:companies,id'],
            'customer_id' => ['nullable', 'integer', 'exists:customers,id'],
            'site_id' => ['required', 'integer', 'exists:sites,id'],
            'delivered_by' => ['required', 'string', 'max:255'],
            'reason' => ['required', 'string', 'max:2000'],
            'stock_number' => ['nullable', 'string', 'max:255'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:255'],
            'condition_notes' => ['nullable', 'string', 'max:2000'],
            'accessories' => ['nullable', 'string', 'max:2000'],
            'photos' => ['nullable', 'array', 'max:12'],
            'photos.*' => ['string'],
        ])->validate();
        abort_unless($request->user()->canAccessCompany($data['company_id']), 403);

        $customer = ! empty($data['customer_id']) ? Customer::findOrFail($data['customer_id']) : Customer::firstOrCreate(
            ['account_number' => 'TEMP-UNKNOWN'], ['name' => 'Unknown - awaiting identification', 'company_id' => $data['company_id']]
        );
        $site = Site::findOrFail($data['site_id']);
        $reference = 'YR-'.now()->format('Ymd').'-'.str_pad((string) (Movement::withTrashed()->count() + 1), 5, '0', STR_PAD_LEFT);
        $movement = Movement::create([
            'company_id' => $data['company_id'], 'status' => MovementStatus::AwaitingSchedule,
            'movement_type' => 'yard_receipt', 'reference' => $reference,
            'notes' => "Delivered by: {$data['delivered_by']}\nReason: {$data['reason']}",
            'customer_id' => $customer->id, 'delivery_site_id' => $site->id, 'collection_site_id' => $site->id,
            'created_by' => $request->user()->id, 'updated_by' => $request->user()->id,
        ]);
        $action = $movement->actions()->create(['sequence' => 1, 'action_type' => MovementActionType::YardReceipt, 'site_id' => $site->id, 'status' => 'completed', 'arrived_at' => now(), 'departed_at' => now()]);
        $item = $movement->items()->create([
            'movement_action_id' => $action->id, 'stock_number' => $data['stock_number'] ?? null,
            'serial_number' => $data['serial_number'] ?? null, 'description' => $data['description'],
            'movement_action' => 'yard_receipt', 'completed' => true, 'is_temporary' => true,
            'condition_notes' => $data['condition_notes'] ?? null,
        ]);
        if (filled($data['accessories'] ?? null)) {
            $item->accessories()->create(['type' => 'received', 'description' => $data['accessories'], 'completed' => true]);
        }
        $this->storeEvidence($movement, $data, $request);
        return ['movement_id' => $movement->id, 'reference' => $movement->reference, 'status' => $movement->status->value];
    }

    private function storeEvidence(Movement $movement, array $data, Request $request): void
    {
        $evidence = array_map(fn ($photo) => ['type' => 'condition', 'data' => $photo], $data['photos'] ?? []);
        if (! empty($data['signature'])) { $evidence[] = ['type' => 'signature', 'data' => $data['signature']]; }
        foreach ($evidence as $index => $entry) {
            if (! preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,(.+)$/', $entry['data'], $matches)) { continue; }
            $bytes = base64_decode($matches[2], true);
            if ($bytes === false || strlen($bytes) > 10 * 1024 * 1024) { continue; }
            $extension = str_contains($matches[1], 'png') ? 'png' : 'jpg';
            $path = "movements/{$movement->id}/{$entry['type']}-".Str::uuid().".{$extension}";
            Storage::disk('local')->put($path, $bytes);
            MovementPhoto::create(['movement_id' => $movement->id, 'photo_type' => $entry['type'], 'disk' => 'local', 'path' => $path, 'mime_type' => $matches[1], 'file_size' => strlen($bytes), 'taken_at' => now(), 'uploaded_by' => $request->user()->id]);
        }
    }

    private function bootstrapData(Request $request): array
    {
        $user = $request->user();
        $companyIds = $user->hasRole('Super-Admin') ? Company::pluck('id') : $user->companies()->pluck('companies.id')->push($user->company_id)->filter()->unique();
        $jobs = Movement::query()->with(['customer', 'actions.site', 'items.accessories'])
            ->where(function ($query) use ($user) { $query->where('driver_id', $user->id)->orWhereHas('actions', fn ($query) => $query->where('driver_id', $user->id)); })
            ->whereNotIn('status', ['completed', 'cancelled'])->orderBy('schedule_start')->get();
        return [
            'user' => ['id' => $user->id, 'name' => $user->name],
            'permissions' => ['driver' => Gate::allows('pwa.driver'), 'yard' => Gate::allows('pwa.yard_receipt')],
            'companies' => Company::whereIn('id', $companyIds)->where('is_active', true)->get(['id', 'code', 'name']),
            'customers' => Customer::orderBy('name')->get(['id', 'name', 'account_number']),
            'sites' => Site::orderBy('name')->get(['id', 'name', 'address_line_1', 'postcode']),
            'jobs' => $jobs,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
