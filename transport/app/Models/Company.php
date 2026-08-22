<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $fillable = ['code', 'name', 'address', 'home_site_id', 'email', 'phone', 'document_prefix', 'next_document_number', 'logo_path', 'is_active'];

    /** @return BelongsTo<Site, $this> */
    public function homeSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'home_site_id');
    }

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    /** @return HasMany<Movement, $this> */
    public function movements(): HasMany
    {
        return $this->hasMany(Movement::class);
    }
}
