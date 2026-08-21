<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use App\Concerns\HasActivityLog;

#[Fillable(['name', 'guard_name', 'is_active'])]
class Role extends SpatieRole
{
    use HasActivityLog;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function isActive(): bool
    {
        return $this->is_active;
    }

    public function hasUsers(): bool
    {
        return $this->users()->exists();
    }
}
