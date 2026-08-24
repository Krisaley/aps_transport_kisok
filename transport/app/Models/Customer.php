<?php

namespace App\Models;

use App\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasActivityLog;

    protected $fillable = [
        'account_number',
        'name',
        'trading_name',
        'company_id',
        'home_site_id',
    ];

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return BelongsTo<Site, $this> */
    public function homeSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'home_site_id');
    }

    /** @return HasMany<Movement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }

    /** @return BelongsToMany<Site, $this> */
    public function sites(): BelongsToMany
    {
        return $this->belongsToMany(Site::class);
    }

    /** @return BelongsToMany<Equipment, $this> */
    public function equipment(): BelongsToMany
    {
        return $this->belongsToMany(Equipment::class, 'customer_equipment');
    }
}
