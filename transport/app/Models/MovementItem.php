<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Concerns\HasActivityLog;

class MovementItem extends Model
{
    use HasActivityLog;
    
    protected $fillable = [
        'movement_id',
        'movement_action_id',
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

    public function action(): BelongsTo { return $this->belongsTo(MovementAction::class, 'movement_action_id'); }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    public function equipment(): BelongsTo
    {
        return $this->belongsTo(Equipment::class);
    }

    public function accessories(): HasMany
    {
        return $this->hasMany(MovementItemAccessory::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MovementPhoto::class);
    }
}
