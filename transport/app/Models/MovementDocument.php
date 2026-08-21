<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Concerns\HasActivityLog;

class MovementDocument extends Model
{
    use HasActivityLog;
    
    protected $fillable = [
        'movement_id',
        'document_type',
        'disk',
        'path',
        'original_filename',
        'mime_type',
        'file_size',
        'checksum',
        'template_version',
        'issued_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'issued_at' => 'datetime',
        ];
    }

    public function movement(): BelongsTo
    {
        return $this->belongsTo(Movement::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
