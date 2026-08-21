<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Concerns\HasActivityLog;

class Equipment extends Model
{
    use HasActivityLog;
    
    protected $fillable = [
        'model_id',
        'stock_number',
        'serial_number',
    ];

    public function equipmentModel(): BelongsTo
    {
        return $this->belongsTo(EquipmentModel::class, 'model_id');
    }

    public function movementItems(): HasMany
    {
        return $this->hasMany(MovementItem::class);
    }
}
