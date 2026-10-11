<?php

namespace App\Http\Controllers\Crm;

use App\Enums\Priority;
use App\Enums\ServiceRequestStatus;
use App\Http\Controllers\Controller;
use App\Models\ServiceRequest;
use App\Models\Team;
use App\Services\NumberingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response;

class PublicIntakeController extends Controller
{
    public function create(Request $request): Response
    {
        $team = Team::query()->where('slug', $request->string('org')->toString())->first();

        return Inertia::render('crm/public/intake', [
            'organization' => $team ? ['name' => $team->name, 'slug' => $team->slug] : null,
        ]);
    }

    public function store(Request $request, NumberingService $numbering): RedirectResponse
    {
        $data = $request->validate([
            'organization_slug' => ['required', 'string', 'exists:teams,slug'],
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:5000'],
            'city' => ['nullable', 'string', 'max:120'],
        ]);

        abort_if($request->filled('assigned_user_id') || $request->filled('status') || $request->filled('total'), 422);

        $key = 'intake:'.$request->ip();
        abort_if(RateLimiter::tooManyAttempts($key, 5), 429, 'Too many submissions. Try again later.');
        RateLimiter::hit($key, 300);

        $team = Team::query()->where('slug', $data['organization_slug'])->firstOrFail();
        $fingerprint = hash('sha256', implode('|', [
            $team->id,
            strtolower($data['contact_email']),
            strtolower($data['subject']),
            now()->format('Y-m-d-H'),
        ]));

        if (ServiceRequest::query()->where('team_id', $team->id)->where('intake_fingerprint', $fingerprint)->exists()) {
            return back()->with('success', 'We already received this request. A dispatcher will follow up.');
        }

        ServiceRequest::query()->create([
            'team_id' => $team->id,
            'reference' => $numbering->next($team, 'request'),
            'status' => ServiceRequestStatus::New,
            'priority' => Priority::Normal,
            'source' => 'public_form',
            'contact_name' => $data['contact_name'],
            'contact_email' => $data['contact_email'],
            'contact_phone' => $data['contact_phone'] ?? null,
            'subject' => $data['subject'],
            'description' => $data['description'],
            'intake_fingerprint' => $fingerprint,
        ]);

        return back()->with('success', 'Request received. We will contact you shortly.');
    }
}
