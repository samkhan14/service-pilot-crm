<?php

namespace App\Http\Controllers;

use App\Enums\TeamPermission;
use App\Http\Controllers\Concerns\AuthorizesCrm;
use App\Models\TeamInvitation;
use App\Services\DashboardMetrics;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use AuthorizesCrm;

    public function __invoke(Request $request, DashboardMetrics $metrics): Response
    {
        $email = strtolower($request->user()->email);
        $team = $request->user()->currentTeam;

        $pendingInvitations = TeamInvitation::query()
            ->with(['inviter', 'team'])
            ->whereRaw('LOWER(email) = ?', [$email])
            ->whereNull('accepted_at')
            ->where(fn ($query) => $query
                ->whereNull('expires_at')
                ->orWhere('expires_at', '>=', now()))
            ->latest()
            ->get()
            ->map(fn (TeamInvitation $invitation) => [
                'code' => $invitation->code,
                'inviterName' => $invitation->inviter->name,
                'team' => [
                    'name' => $invitation->team->name,
                    'slug' => $invitation->team->slug,
                ],
            ]);

        $from = now()->startOfMonth();
        $to = now()->endOfDay();

        return Inertia::render('dashboard', [
            'pendingInvitations' => $pendingInvitations,
            'metrics' => $team && $request->user()->hasTeamPermission($team, TeamPermission::ViewDashboard)
                ? $metrics->for($team, $request->user(), $from, $to)
                : null,
            'period' => [
                'from' => $from->toDateString(),
                'to' => $to->toDateString(),
            ],
        ]);
    }
}
