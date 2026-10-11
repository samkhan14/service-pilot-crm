<?php

namespace App\Http\Controllers\Crm;

use App\Enums\Priority;
use App\Enums\ServiceRequestStatus;
use App\Enums\TeamPermission;
use App\Http\Controllers\Concerns\AuthorizesCrm;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\ServiceRequest;
use App\Services\ActivityRecorder;
use App\Services\NumberingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceRequestController extends Controller
{
    use AuthorizesCrm;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewRequests);
        $team = $this->currentTeam($request);

        $requests = ServiceRequest::query()
            ->forTeam($team)
            ->with(['customer:id,name', 'assignee:id,name'])
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('reference', 'like', "%{$search}%")
                        ->orWhere('subject', 'like', "%{$search}%");
                });
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('crm/requests/index', [
            'requests' => $requests,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ManageRequests);
        $team = $this->currentTeam($request);

        return Inertia::render('crm/requests/create', [
            'customers' => Customer::query()->forTeam($team)->where('status', 'active')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, NumberingService $numbering, ActivityRecorder $activity): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ManageRequests);
        $team = $this->currentTeam($request);

        $data = $request->validate([
            'customer_id' => ['nullable', 'exists:customers,id'],
            'service_location_id' => ['nullable', 'exists:service_locations,id'],
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'source' => ['nullable', 'string', 'max:40'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'internal_notes' => ['nullable', 'string'],
        ]);

        $serviceRequest = ServiceRequest::query()->create([
            ...$data,
            'team_id' => $team->id,
            'reference' => $numbering->next($team, 'request'),
            'status' => ServiceRequestStatus::New,
            'priority' => Priority::from($data['priority']),
            'source' => $data['source'] ?? 'manual',
        ]);

        $activity->record($team, $serviceRequest, 'request.created', $request->user());

        return redirect()->route('requests.show', [$team, $serviceRequest])->with('success', 'Service request created.');
    }

    public function show(Request $request, $current_team, ServiceRequest $serviceRequest): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewRequests);
        $serviceRequest->load(['customer', 'location', 'assignee']);

        return Inertia::render('crm/requests/show', [
            'serviceRequest' => $serviceRequest,
        ]);
    }
}
