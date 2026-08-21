<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Concerns\HasActivityLog;

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
