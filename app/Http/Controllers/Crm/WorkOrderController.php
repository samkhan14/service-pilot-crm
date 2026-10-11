<?php

namespace App\Http\Controllers\Crm;

use App\Enums\TeamPermission;
use App\Http\Controllers\Concerns\AuthorizesCrm;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\InvoiceService;
use App\Services\WorkOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class WorkOrderController extends Controller
{
    use AuthorizesCrm;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewWorkOrders);
        $team = $this->currentTeam($request);
        $user = $request->user();

        $jobs = WorkOrder::query()
            ->forTeam($team)
            ->with(['customer:id,name', 'assignments.user:id,name'])
            ->when(! $user->hasTeamPermission($team, TeamPermission::ViewAllJobs), function ($query) use ($user) {
                $query->whereHas('assignments', fn ($inner) => $inner->where('user_id', $user->id));
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('crm/work-orders/index', [
            'workOrders' => $jobs,
            'filters' => $request->only(['status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ManageWorkOrders);
        $team = $this->currentTeam($request);

        return Inertia::render('crm/work-orders/create', [
            'customers' => Customer::query()->forTeam($team)->with('locations')->orderBy('name')->get(['id', 'name']),
            'technicians' => $team->members()->orderBy('name')->get(['users.id', 'users.name']),
        ]);
    }

    public function store(Request $request, WorkOrderService $workOrders): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ManageWorkOrders);
        $team = $this->currentTeam($request);

        $data = $request->validate([
            'customer_id' => ['required', 'exists:customers,id'],
            'service_location_id' => ['nullable', 'exists:service_locations,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'priority' => ['required', 'in:low,normal,high,urgent'],
            'scheduled_start_at' => ['nullable', 'date'],
            'scheduled_end_at' => ['nullable', 'date', 'after:scheduled_start_at'],
            'internal_notes' => ['nullable', 'string'],
            'customer_notes' => ['nullable', 'string'],
            'technician_ids' => ['nullable', 'array'],
            'technician_ids.*' => ['integer'],
            'checklist' => ['nullable', 'array'],
            'checklist.*' => ['string', 'max:255'],
            'line_items' => ['nullable', 'array'],
            'line_items.*.description' => ['required_with:line_items', 'string'],
            'line_items.*.quantity' => ['required_with:line_items', 'numeric', 'min:0.01'],
            'line_items.*.unit_price' => ['required_with:line_items', 'numeric', 'min:0'],
            'line_items.*.tax_rate' => ['nullable', 'numeric', 'min:0'],
            'line_items.*.is_material' => ['nullable', 'boolean'],
        ]);

        $workOrder = $workOrders->create(
            $team,
            $request->user(),
            $data,
            $data['line_items'] ?? [],
            $data['technician_ids'] ?? [],
            $data['checklist'] ?? [],
        );

        return redirect()->route('work-orders.show', [$team, $workOrder])->with('success', 'Work order created.');
    }

    public function show(Request $request, $current_team, WorkOrder $workOrder): Response
    {
        $team = $this->currentTeam($request);
        abort_unless($this->canViewWorkOrder($request->user(), $team, $workOrder), 403);

        $workOrder->load(['customer', 'location', 'lineItems', 'assignments.user', 'checklistItems', 'events.user', 'estimate']);

        return Inertia::render('crm/work-orders/show', [
            'workOrder' => $workOrder,
            'technicians' => $team->members()->orderBy('name')->get(['users.id', 'users.name']),
        ]);
    }

    public function schedule(Request $request, $current_team, WorkOrder $workOrder, WorkOrderService $workOrders): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ManageSchedule);
        $data = $request->validate([
            'scheduled_start_at' => ['required', 'date'],
            'scheduled_end_at' => ['required', 'date', 'after:scheduled_start_at'],
            'technician_ids' => ['nullable', 'array'],
            'technician_ids.*' => ['integer'],
        ]);

        try {
            $workOrders->schedule(
                $workOrder,
                $request->user(),
                Carbon::parse($data['scheduled_start_at']),
                Carbon::parse($data['scheduled_end_at']),
                $data['technician_ids'] ?? [],
            );
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['scheduled_start_at' => $exception->getMessage()]);
        }

        return back()->with('success', 'Schedule updated.');
    }

    public function assign(Request $request, $current_team, WorkOrder $workOrder, WorkOrderService $workOrders): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::AssignWorkOrders);
        $data = $request->validate([
            'technician_ids' => ['required', 'array', 'min:1'],
            'technician_ids.*' => ['integer'],
        ]);

        try {
            $workOrders->assign($workOrder, $request->user(), $data['technician_ids']);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['technician_ids' => $exception->getMessage()]);
        }

        return back()->with('success', 'Technicians assigned.');
    }

    public function start(Request $request, $current_team, WorkOrder $workOrder, WorkOrderService $workOrders): RedirectResponse
    {
        $this->authorizeJobAction($request, $workOrder);
        $workOrders->start($workOrder, $request->user());

        return back()->with('success', 'Job started.');
    }

    public function pause(Request $request, $current_team, WorkOrder $workOrder, WorkOrderService $workOrders): RedirectResponse
    {
        $this->authorizeJobAction($request, $workOrder);
        $workOrders->pause($workOrder, $request->user(), $request->string('reason')->toString() ?: null);

        return back()->with('success', 'Job paused.');
    }

    public function complete(Request $request, $current_team, WorkOrder $workOrder, WorkOrderService $workOrders): RedirectResponse
    {
        $this->authorizeJobAction($request, $workOrder);
        $workOrders->complete($workOrder, $request->user(), $request->string('signoff_name')->toString() ?: null);

        return back()->with('success', 'Job completed.');
    }

    public function cancel(Request $request, $current_team, WorkOrder $workOrder, WorkOrderService $workOrders): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ManageWorkOrders);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $workOrders->cancel($workOrder, $request->user(), $data['reason']);

        return back()->with('success', 'Work order cancelled.');
    }

    public function invoice(Request $request, $current_team, WorkOrder $workOrder, InvoiceService $invoices): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ManageInvoices);

        try {
            $invoice = $invoices->createFromWorkOrder($workOrder, $request->user());
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['invoice' => $exception->getMessage()]);
        }

        return redirect()->route('invoices.show', [$this->currentTeam($request), $invoice])
            ->with('success', 'Invoice issued.');
    }

    private function authorizeJobAction(Request $request, WorkOrder $workOrder): void
    {
        $team = $this->currentTeam($request);
        $user = $request->user();

        abort_unless(
            $user->hasTeamPermission($team, TeamPermission::ExecuteWorkOrders)
            && $this->canViewWorkOrder($user, $team, $workOrder),
            403,
        );
    }
}
