<?php

namespace App\Services;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\Team;
use Illuminate\Support\Collection;

class CustomerDuplicateFinder
{
    /**
     * @return Collection<int, Customer>
     */
    public function find(Team $team, ?string $email = null, ?string $phone = null, ?string $name = null, ?int $ignoreId = null): Collection
    {
        $query = Customer::query()
            ->forTeam($team)
            ->where('status', '!=', CustomerStatus::Archived->value)
            ->when($ignoreId, fn ($builder) => $builder->where('id', '!=', $ignoreId));

        $email = $email ? strtolower(trim($email)) : null;
        $phone = $phone ? preg_replace('/\D+/', '', $phone) : null;
        $name = $name ? strtolower(trim($name)) : null;

        $query->where(function ($builder) use ($email, $phone, $name) {
            $matched = false;

            if ($email) {
                $builder->orWhereRaw('LOWER(email) = ?', [$email]);
                $matched = true;
            }

            if ($phone) {
                $builder->orWhereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '(', ''), ')', '') = ?", [$phone]);
                $matched = true;
            }

            if ($name && $phone) {
                $builder->orWhere(function ($inner) use ($name, $phone) {
                    $inner->whereRaw('LOWER(name) = ?', [$name])
                        ->whereRaw("REPLACE(REPLACE(REPLACE(REPLACE(phone, '-', ''), ' ', ''), '(', ''), ')', '') = ?", [$phone]);
                });
                $matched = true;
            }

            if (! $matched) {
                $builder->whereRaw('1 = 0');
            }
        });

        return $query->limit(5)->get();
    }
}
