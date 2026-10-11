<?php

namespace App\Http\Controllers\Crm;

use App\Enums\TeamPermission;
use App\Http\Controllers\Concerns\AuthorizesCrm;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class InvoiceController extends Controller
{
    use AuthorizesCrm;

    public function index(Request $request): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewInvoices);
        $team = $this->currentTeam($request);

        $invoices = Invoice::query()
            ->forTeam($team)
            ->with('customer:id,name')
            ->when($request->string('status')->toString(), fn ($query, $status) => $query->where('status', $status))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('crm/invoices/index', [
            'invoices' => $invoices,
            'filters' => $request->only(['status']),
        ]);
    }

    public function show(Request $request, $current_team, Invoice $invoice): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewInvoices);
        $invoice->load(['customer', 'lineItems', 'payments.recorder', 'workOrder']);

        return Inertia::render('crm/invoices/show', [
            'invoice' => $invoice,
        ]);
    }

    public function print(Request $request, $current_team, Invoice $invoice): Response
    {
        $this->authorizePermission($request, TeamPermission::ViewInvoices);
        $invoice->load(['customer', 'lineItems', 'workOrder', 'team.settings']);

        return Inertia::render('crm/invoices/print', [
            'invoice' => $invoice,
        ]);
    }

    public function pay(Request $request, $current_team, Invoice $invoice, InvoiceService $invoices): RedirectResponse
    {
        $this->authorizePermission($request, TeamPermission::RecordPayments);

        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:40'],
            'reference' => ['nullable', 'string', 'max:120'],
            'paid_on' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'idempotency_key' => ['nullable', 'string', 'max:80'],
        ]);

        try {
            $invoices->recordPayment($invoice, $request->user(), $data);
        } catch (\InvalidArgumentException $exception) {
            return back()->withErrors(['amount' => $exception->getMessage()]);
        }

        return back()->with('success', 'Payment recorded.');
    }
}
