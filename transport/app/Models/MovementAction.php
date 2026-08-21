<?php

namespace App\Models;

use App\Concerns\HasActivityLog;
use App\Enums\MovementActionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MovementAction extends Model
{
    use HasActivityLog;

    protected $fillable = ['movement_id', 'sequence', 'action_type', 'site_id', 'driver_id', 'vehicle_id', 'schedule_start', 'schedule_end', 'arrived_at', 'departed_at', 'status', 'notes'];

    protected function casts(): array
    {
        return [
            'action_type' => MovementActionType::class,
            'schedule_start' => 'datetime', 'schedule_end' => 'datetime',
            'arrived_at' => 'datetime', 'departed_at' => 'datetime',
        ];
    }

    public function movement(): BelongsTo { return $this->belongsTo(Movement::class); }
    public function site(): BelongsTo { return $this->belongsTo(Site::class); }
    public function driver(): BelongsTo { return $this->belongsTo(User::class, 'driver_id'); }
    public function vehicle(): BelongsTo { return $this->belongsTo(Vehicle::class); }
    public function items(): HasMany { return $this->hasMany(MovementItem::class); }
}
