<?php

namespace App\Models;

use App\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovementItem extends Model
{
    use HasActivityLog;

    protected $fillable = [
        'movement_id',
        'movement_action_id',
        'collection_action_id',
        'delivery_action_id',
        'equipment_id',
        'stock_number',
        'make',
        'model',
        'description',
        'serial_number',
        'quantity',
        'movement_action',
        'completed',
        'is_temporary',
        'condition_notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'completed' => 'boolean',
            'is_temporary' => 'boolean',
        ];
    }

    /** @return BelongsTo<MovementAction, $this> */
    public function action(): BelongsTo
    {
        return $this->belongsTo(MovementAction::class, 'movement_action_id');
    }

    public function collectionAction(): BelongsTo
    {
        return $this->belongsTo(MovementAction::class, 'collection_action_id');
    }

    public function deliveryAction(): BelongsTo
    {
        return $this->belongsTo(MovementAction::class, 'delivery_action_id');
    }

    /** @return BelongsTo<Movement, $this> */
    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    /** @return BelongsTo<Equipment, $this> */
    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    /** @return HasMany<MovementItemAccessory, $this> */
    public function accessories(): HasMany
    {
        return $this->hasMany(MovementItemAccessory::class);
    }

    /** @return HasMany<MovementPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(MovementPhoto::class);
    }
}
