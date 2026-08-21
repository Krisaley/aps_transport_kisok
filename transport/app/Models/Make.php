<?php

namespace App\Models;

use App\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Make extends Model
{
    use HasActivityLog;

    protected $fillable = [
        'name',
    ];

    /** @return HasMany<EquipmentModel, $this> */
    public function equipmentModels(): HasMany
    {
        return $this->hasMany(EquipmentModel::class);
    }

    /**
     * A separately eager-loadable copy of the models relationship.
     *
     * The makes index constrains this relationship to the current search term
     * while retaining equipmentModels for make-name matches.
     *
     * @return HasMany<EquipmentModel, $this>
     */
    public function matchingEquipmentModels(): HasMany
    {
        return $this->hasMany(EquipmentModel::class);
    }
}
