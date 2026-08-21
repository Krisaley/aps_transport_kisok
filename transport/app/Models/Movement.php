<?php

namespace App\Models;

use App\Concerns\HasActivityLog;
use App\Enums\MovementStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

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

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function actions(): HasMany
    {
        return $this->hasMany(MovementAction::class)->orderBy('sequence');
    }

    public function statusHistory(): HasMany
    {
        return $this->hasMany(MovementStatusHistory::class)->orderByDesc('created_at');
    }

    public function driver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_id');
    }

    public function vehicle(): BelongsTo
    {
        return $this->belongsTo(Vehicle::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deliverySite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'delivery_site_id');
    }

    public function collectionSite(): BelongsTo
    {
        return $this->belongsTo(Site::class, 'collection_site_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function completer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MovementItem::class);
    }

    public function documents(): HasMany
    {
        return $this->hasMany(MovementDocument::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(MovementPhoto::class);
    }
}
