<?php

namespace App\Support;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Collection;

class CurrentCompany
{
    public const SESSION_KEY = 'active_company_id';

    /** @return Collection<int, Company> */
    public function availableFor(User $user): Collection
    {
        $query = Company::query()->where('is_active', true)->orderBy('name');

        if (! $user->hasRole('Super-Admin')) {
            $ids = $user->companies()->pluck('companies.id')->push($user->company_id)->filter()->unique();
            $query->whereKey($ids);
        }

        return $query->get();
    }

    public function id(User $user): ?int
    {
        $available = $this->availableFor($user);
        if ($available->isEmpty()) {
            return null;
        }

        $requested = (int) session(self::SESSION_KEY, $user->company_id);
        $active = $available->firstWhere('id', $requested) ?? $available->first();

        session([self::SESSION_KEY => $active->id]);

        return $active->id;
    }

    public function company(User $user): ?Company
    {
        return $this->availableFor($user)->firstWhere('id', $this->id($user));
    }
}
