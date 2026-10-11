<?php

namespace App\Http\Controllers\Crm;

use App\Enums\CustomerStatus;
use App\Enums\TeamPermission;
use App\Http\Controllers\Concerns\AuthorizesCrm;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerContact;
use App\Models\ServiceLocation;
use App\Services\ActivityRecorder;
use App\Services\CustomerDuplicateFinder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    use AuthorizesCrm;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewCustomers);
        $team = $this->currentTeam($request);

        $customers = Customer::query()
            ->forTeam($team)
            ->withCount(['locations', 'workOrders'])
            ->when($request->string('search')->toString(), function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('name', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('crm/customers/index', [
            'customers' => $customers,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ManageCustomers);

        return Inertia::render('crm/customers/create');
    }

    public function store(Request $request, CustomerDuplicateFinder $duplicates, ActivityRecorder $activity): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ManageCustomers);
        $team = $this->currentTeam($request);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'communication_preference' => ['nullable', 'string', 'max:30'],
            'internal_notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:40'],
            'billing_address_line1' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_region' => ['nullable', 'string', 'max:120'],
            'billing_postal_code' => ['nullable', 'string', 'max:30'],
            'billing_country' => ['nullable', 'string', 'size:2'],
            'location_label' => ['nullable', 'string', 'max:120'],
            'location_address_line1' => ['nullable', 'string', 'max:255'],
            'location_city' => ['nullable', 'string', 'max:120'],
            'location_region' => ['nullable', 'string', 'max:120'],
            'location_postal_code' => ['nullable', 'string', 'max:30'],
        ]);

        $matches = $duplicates->find($team, $data['email'] ?? null, $data['phone'] ?? null, $data['name']);
        if ($matches->isNotEmpty() && ! $request->boolean('confirm_duplicate')) {
            return back()->withErrors([
                'name' => 'A possible duplicate customer exists: '.$matches->pluck('name')->join(', '),
            ])->withInput();
        }

        $customer = Customer::query()->create([
            ...collect($data)->except([
                'location_label', 'location_address_line1', 'location_city',
                'location_region', 'location_postal_code',
            ])->all(),
            'team_id' => $team->id,
            'status' => CustomerStatus::Active,
            'billing_country' => $data['billing_country'] ?? 'US',
        ]);

        if (! empty($data['location_address_line1'])) {
            ServiceLocation::query()->create([
                'team_id' => $team->id,
                'customer_id' => $customer->id,
                'label' => $data['location_label'] ?: 'Primary',
                'address_line1' => $data['location_address_line1'],
                'city' => $data['location_city'] ?? '',
                'region' => $data['location_region'] ?? null,
                'postal_code' => $data['location_postal_code'] ?? null,
                'country' => $data['billing_country'] ?? 'US',
                'is_primary' => true,
            ]);
        }

        if (! empty($data['email']) || ! empty($data['phone'])) {
            CustomerContact::query()->create([
                'team_id' => $team->id,
                'customer_id' => $customer->id,
                'name' => $data['name'],
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_primary' => true,
            ]);
        }

        $activity->record($team, $customer, 'customer.created', $request->user());

        return redirect()->route('customers.show', [$team, $customer])->with('success', 'Customer created.');
    }

    public function show(Request $request, $current_team, Customer $customer): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewCustomers);

        $customer->load([
            'locations',
            'contacts',
            'serviceRequests' => fn ($query) => $query->latest()->limit(10),
            'estimates' => fn ($query) => $query->latest()->limit(10),
            'workOrders' => fn ($query) => $query->latest()->limit(10),
            'invoices' => fn ($query) => $query->latest()->limit(10),
        ]);

        return Inertia::render('crm/customers/show', [
            'customer' => $customer,
        ]);
    }

    public function edit(Request $request, $current_team, Customer $customer): Response
    {
        $this->authorizePermission($request, TeamPermission::ManageCustomers);

        return Inertia::render('crm/customers/edit', [
            'customer' => $customer,
        ]);
    }

    public function update(Request $request, $current_team, Customer $customer, ActivityRecorder $activity): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::ManageCustomers);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'status' => ['required', 'in:active,inactive,archived'],
            'communication_preference' => ['nullable', 'string', 'max:30'],
            'internal_notes' => ['nullable', 'string'],
            'tags' => ['nullable', 'array'],
            'billing_address_line1' => ['nullable', 'string', 'max:255'],
            'billing_city' => ['nullable', 'string', 'max:120'],
            'billing_region' => ['nullable', 'string', 'max:120'],
            'billing_postal_code' => ['nullable', 'string', 'max:30'],
            'billing_country' => ['nullable', 'string', 'size:2'],
        ]);

        $customer->update([
            ...$data,
            'archived_at' => $data['status'] === 'archived' ? now() : null,
        ]);
        $activity->record($this->currentTeam($request), $customer, 'customer.updated', $request->user());

        return redirect()->route('customers.show', [$this->currentTeam($request), $customer])->with('success', 'Customer updated.');
    }
}
