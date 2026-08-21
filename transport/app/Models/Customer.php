<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Concerns\HasActivityLog;

class Customer extends Model
{
    use HasActivityLog;
    
    protected $fillable = [
        'account_number',
        'name',
        'company_id',
    ];

    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }
}
