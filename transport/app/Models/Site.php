<?php

namespace App\Models;

use App\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
        'google_place_id',
        'access_instructions',
        'company_id',
        'customer_id',
    ];

    public function formattedAddress(): string
    {
        return collect([$this->address_line_1, $this->address_line_2, $this->town, $this->county, $this->postcode])->filter()->join(', ');
    }

    /** @return BelongsToMany<Company, $this> */
    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(Company::class);
    }

    /** @return BelongsToMany<Customer, $this> */
    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class);
    }

    /** @return HasMany<Movement, $this> */
    public function deliveryMovements(): HasMany
    {
        return $this->hasMany(Movement::class, 'delivery_site_id');
    }

    /** @return HasMany<Movement, $this> */
    public function collectionMovements(): HasMany
    {
        return $this->hasMany(Movement::class, 'collection_site_id');
    }
}
