<?php

namespace App\Models;

use App\Concerns\HasActivityLog;
use App\Enums\MovementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $company_id
 * @property int|null $driver_id
 * @property int $lock_version
 * @property MovementStatus $status
 */
class Movement extends Model
{
    use HasActivityLog;
    use SoftDeletes;

    protected $fillable = [
        'status',
        'status_reason',
        'movement_type',
        'notes',
        'completed_at',
        'planned_date',
        'schedule_start',
        'schedule_end',
        'estimated_minutes',
        'schedule_window',
        'lock_version',
        'company_id',
        'reference',
        'advice_note',
        'job_number',
        'contact_name',
        'contact_number',
        'driver_id',
        'vehicle_id',
        'customer_id',
        'delivery_site_id',
        'collection_site_id',
        'created_by',
        'updated_by',
        'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'planned_date' => 'date',
            'schedule_start' => 'datetime',
            'schedule_end' => 'datetime',
            'status' => MovementStatus::class,
        ];
    }

    /** @return BelongsTo<Company, $this> */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** @return HasMany<MovementAction, $this> */
    public function actions(): HasMany
    {
        return $this->hasMany(MovementAction::class)->orderBy('sequence');
    }

    /** @return HasMany<MovementStatusHistory, $this> */
    public function statusHistory(): HasMany
    {
        return $this->hasMany(MovementStatusHistory::class)->orderByDesc('created_at');
    }

    /** @return BelongsTo<User, $this> */
    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    /** @return BelongsTo<Vehicle, $this> */
    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    /** @return BelongsTo<Customer, $this> */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /** @return BelongsTo<Site, $this> */
    public function deliverySite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'delivery_site_id');
    }

    /** @return BelongsTo<Site, $this> */
    public function collectionSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'collection_site_id');
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** @return BelongsTo<User, $this> */
    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /** @return HasMany<MovementItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(MovementItem::class);
    }

    /** @return HasMany<MovementDocument, $this> */
    public function documents(): HasMany
    {
        return $this->hasMany(MovementDocument::class);
    }

    /** @return HasMany<MovementPhoto, $this> */
    public function photos(): HasMany
    {
        return $this->hasMany(MovementPhoto::class);
    }
}
