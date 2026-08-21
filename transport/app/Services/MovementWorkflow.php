<?php

namespace App\Services;

use App\Enums\MovementStatus;
use App\Models\Movement;
use App\Models\MovementAction;
use App\Models\MovementStatusHistory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MovementWorkflow
{
    public function transition(Movement $movement, MovementStatus $to, User $actor, ?string $reason = null): Movement
    {
        $from = $movement->status;

        if (! in_array($to, $from->next(), true)) {
            throw ValidationException::withMessages(['status' => "A movement cannot move from {$from->label()} to {$to->label()}."]);
        }

        if (in_array($to, [MovementStatus::Scheduled, MovementStatus::Assigned], true)) {
            $this->validateSchedule($movement);
        }
        if ($to === MovementStatus::Assigned && $movement->actions()->where(fn ($query) => $query->whereNull('driver_id')->orWhereNull('vehicle_id'))->exists()) {
            throw ValidationException::withMessages(['assignment' => 'Every action requires a driver and vehicle before assignment.']);
        }

        if (in_array($to, [MovementStatus::Cancelled, MovementStatus::OnHold, MovementStatus::AwaitingSchedule], true) && blank($reason)) {
            throw ValidationException::withMessages(['reason' => 'A reason is required for this status change.']);
        }

        return DB::transaction(function () use ($movement, $from, $to, $actor, $reason) {
            $movement->update([
                'status' => $to,
                'status_reason' => $reason,
                'completed_at' => $to === MovementStatus::Completed ? now() : $movement->completed_at,
                'completed_by' => $to === MovementStatus::Completed ? $actor->id : $movement->completed_by,
                'updated_by' => $actor->id,
                'lock_version' => $movement->lock_version + 1,
            ]);

            MovementStatusHistory::create([
                'movement_id' => $movement->id,
                'from_status' => $from->value,
                'to_status' => $to->value,
                'reason' => $reason,
                'changed_by' => $actor->id,
                'created_at' => now(),
            ]);

            return $movement->refresh();
        });
    }

    public function validateSchedule(Movement $movement): void
    {
        $movement->loadMissing('actions');

        if ($movement->actions->isEmpty()) {
            throw ValidationException::withMessages(['actions' => 'At least one movement action is required before scheduling.']);
        }

        foreach ($movement->actions as $action) {
            if (! $action->schedule_start || ! $action->schedule_end || ! $action->site_id) {
                throw ValidationException::withMessages(['schedule' => 'Every action requires a site, start time, and end time.']);
            }
            if ($action->schedule_end->lessThanOrEqualTo($action->schedule_start)) {
                throw ValidationException::withMessages(['schedule' => 'An action must end after it starts.']);
            }
            $this->assertNoConflict($action);
        }
    }

    public function assertNoConflict(MovementAction $action): void
    {
        foreach (['driver_id' => 'driver', 'vehicle_id' => 'vehicle'] as $column => $label) {
            if (! $action->{$column}) {
                continue;
            }

            $conflict = MovementAction::query()
                ->where($column, $action->{$column})
                ->whereKeyNot($action->id)
                ->whereHas('movement', fn ($query) => $query->whereNotIn('status', ['cancelled', 'draft', 'awaiting_schedule']))
                ->where('schedule_start', '<', $action->schedule_end)
                ->where('schedule_end', '>', $action->schedule_start)
                ->exists();

            if ($conflict) {
                throw ValidationException::withMessages([$column => "This {$label} already has an overlapping action."]);
            }
        }
    }

    /** Fields which cannot be silently changed after scheduling. */
    /** @return list<string> */
    public static function routingFields(): array
    {
        return ['company_id', 'customer_id', 'delivery_site_id', 'collection_site_id', 'movement_type'];
    }
}
