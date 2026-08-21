<?php

namespace App\Models;

use App\Concerns\HasActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovementPhoto extends Model
{
    use HasActivityLog;

    protected $fillable = [
        'movement_id',
        'movement_item_id',
        'photo_type',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'file_size',
        'notes',
        'taken_at',
        'uploaded_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'taken_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Movement, $this> */
    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    /** @return BelongsTo<MovementItem, $this> */
    public function movementItem(): BelongsTo
    {
        return $this->belongsTo(MovementItem::class);
    }

    /** @return BelongsTo<User, $this> */
    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
