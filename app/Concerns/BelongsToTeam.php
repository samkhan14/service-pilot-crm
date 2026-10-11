<?php

namespace App\Concerns;

use App\Models\Team;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToTeam
{
    /**
     * @return BelongsTo<Team, $this>
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTeam(Builder $query, Team $team): Builder
    {
        return $query->where($this->getTable().'.team_id', $team->id);
    }

    public function resolveRouteBinding($value, $field = null)
    {
        $team = request()->route('current_team');

        $query = $this->newQuery()->where($field ?? $this->getRouteKeyName(), $value);

        if ($team instanceof Team) {
            $query->where($this->getTable().'.team_id', $team->id);
        }

        return $query->firstOrFail();
    }
}
