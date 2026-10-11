<?php

namespace App\Http\Controllers\Crm;

use App\Enums\TeamPermission;
use App\Http\Controllers\Concerns\AuthorizesCrm;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Estimate;
use App\Models\TeamSetting;
use App\Services\EstimateService;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EstimateController extends Controller
{
    use AuthorizesCrm;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewEstimates);
        $team = $this->currentTeam($request);

        $estimates = Estimate::query()
            ->forTeam($team)
            ->with('customer:id,name')
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('crm/estimates/index', [
            'estimates' => $estimates,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ManageEstimates);
        $team = $this->currentTeam($request);

        return Inertia::render('crm/estimates/create', [
            'customers' => Customer::query()->forTeam($team)->with('locations')->orderBy('name')->get(['id', 'name']),
            'settings' => TeamSetting::forTeam($team),
        ]);
    }

    public function store(Request $request, EstimateService $estimates): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ManageEstimates);
        $team = $this->currentTeam($request);

        $data = $this->validated($request);

        if (! $request->user()->hasTeamPermission($team, TeamPermission::ApplyDiscounts) && ($data['discount_amount'] ?? 0) > 0) {
            abort(403);
        }

        $estimate = $estimates->create($team, $request->user(), $data, $data['line_items']);

        return redirect()->route('estimates.show', [$team, $estimate])->with('success', 'Estimate drafted.');
    }

    public function show(Request $request, $current_team, Estimate $estimate): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewEstimates);
        $estimate->load(['customer', 'location', 'lineItems', 'serviceRequest']);

        return Inertia::render('crm/estimates/show', [
            'estimate' => $estimate,
        ]);
    }

    public function send(Request $request, $current_team, Estimate $estimate, EstimateService $estimates): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ManageEstimates);
        $estimates->send($estimate, $request->user());

        return back()->with('success', 'Estimate marked as sent.');
    }

    public function approve(Request $request, $current_team, Estimate $estimate, EstimateService $estimates): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ApproveEstimates);
        $estimates->approve($estimate, $request->user(), $request->string('approved_by_name')->toString() ?: null);

        return back()->with('success', 'Estimate approved.');
    }

    public function reject(Request $request, $current_team, Estimate $estimate, EstimateService $estimates): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ApproveEstimates);
        $estimates->reject($estimate, $request->user());

        return back()->with('success', 'Estimate rejected.');
    }

    public function convert(Request $request, $current_team, Estimate $estimate, WorkOrderService $workOrders): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ManageWorkOrders);

        try {
            $workOrder = $workOrders->createFromEstimate($estimate, $request->user(), $request->only([
                'title', 'description', 'priority', 'scheduled_start_at', 'scheduled_end_at', 'technician_ids', 'checklist',
            ]));
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['convert' => $exception->getMessage()]);
        }

        return redirect()->route('work-orders.show', [$this->currentTeam($request), $workOrder])
            ->with('success', 'Work order created from estimate.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'service_location_id' => ['nullable', 'exists:service_locations,id'],
            'service_request_id' => ['nullable', 'exists:service_requests,id'],
            'currency' => ['nullable', 'size:3'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'expires_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'customer_notes' => ['nullable', 'string'],
            'line_items' => ['required', 'array', 'min:1'],
            'line_items.*.description' => ['required', 'string', 'max:255'],
            'line_items.*.quantity' => ['required', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required', 'numeric', 'min:0'],
            'line_items.*.tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);
    }
}
