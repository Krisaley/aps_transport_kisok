<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Concerns\HasActivityLog;

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

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    public function movementItem(): BelongsTo
    {
        return $this->belongsTo(MovementItem::class);
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
