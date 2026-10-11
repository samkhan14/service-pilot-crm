<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class ActivityRecorder
{
    /**
     * @param  array<string, mixed>  $properties
     */
    public function record(Team $team, Model $subject, string $action, ?User $actor = null, array $properties = []): ActivityLog
    {
        $safe = $properties;
        unset($safe['password'], $safe['token'], $safe['secret'], $safe['two_factor_secret']);

        return ActivityLog::query()->create([
            'team_id' => $team->id,
            'user_id' => $actor?->id,
            'subject_type' => $subject->getMorphClass(),
            'subject_id' => $subject->getKey(),
            'action' => $action,
            'properties' => $safe,
        ]);
    }
}
