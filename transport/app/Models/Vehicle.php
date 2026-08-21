<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Concerns\HasActivityLog;

class Vehicle extends Model
{
    use HasActivityLog;
    
    protected $fillable = [
        'is_active',
        'name',
        'registration',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }
}
