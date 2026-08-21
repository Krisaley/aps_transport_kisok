<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Concerns\HasActivityLog;

class EquipmentModel extends Model
{
    use HasActivityLog;
    
    protected $table = 'models';

    protected $fillable = [
        'make_id',
        'name',
    ];

    public function make(): BelongsTo
    {
        return $this->belongsTo(Make::class);
    }

    public function equipment(): HasMany
    {
        return $this->hasMany(Equipment::class, 'model_id');
    }
}
