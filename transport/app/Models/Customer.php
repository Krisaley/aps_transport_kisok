<?php

namespace App\Models;

use App\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
