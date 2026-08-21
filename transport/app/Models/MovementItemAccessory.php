<?php

namespace App\Models;

use App\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementItemAccessory extends Model
{
    use HasActivityLog;

    protected $fillable = [
        'movement_item_id',
        'type',
        'description',
        'serial_number',
        'quantity',
        'completed',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'completed' => 'boolean',
        ];
    }

    public function movementItem(): BelongsTo
    {
        return $this->belongsTo(MovementItem::class);
    }
}
