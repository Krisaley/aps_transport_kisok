<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Concerns\HasActivityLog;

class Site extends Model
{
    use HasActivityLog;
    
    protected $fillable = [
        'name',
        'address_line_1',
        'address_line_2',
        'town',
        'county',
        'postcode',
        'what_3_words',
        'address_code',
        'company_id',
    ];

    public function deliveryMovements(): HasMany
    {
        return $this->hasMany(Movement::class, 'delivery_site_id');
    }

    public function collectionMovements(): HasMany
    {
        return $this->hasMany(Movement::class, 'collection_site_id');
    }
}
