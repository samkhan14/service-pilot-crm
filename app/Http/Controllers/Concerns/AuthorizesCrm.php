<?php

namespace App\Http\Controllers\Concerns;

use App\Enums\TeamPermission;
use App\Models\Team;
use App\Models\User;
use App\Models\WorkOrder;
use Illuminate\Http\Request;

trait AuthorizesCrm
{
    protected function currentTeam(Request $request): Team
    {
        $team = $request->route('current_team');

        abort_unless($team instanceof Team, 404);

        return $team;
    }

    protected function authorizePermission(Request $request, TeamPermission $permission): void
    {
        $user = $request->user();
        $team = $this->currentTeam($request);

        abort_unless($user instanceof User && $user->hasTeamPermission($team, $permission), 403);
    }

    protected function canViewWorkOrder(User $user, Team $team, WorkOrder $workOrder): bool
    {
        if (! $user->belongsToTeam($team) || $workOrder->team_id !== $team->id) {
            return false;
        }

        if ($user->hasTeamPermission($team, TeamPermission::ViewAllJobs)) {
            return $user->hasTeamPermission($team, TeamPermission::ViewWorkOrders);
        }

        return $user->hasTeamPermission($team, TeamPermission::ViewWorkOrders)
            && $workOrder->isAssignedTo($user);
    }
}
