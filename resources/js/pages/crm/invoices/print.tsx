import { Head } from '@inertiajs/react';

type Invoice = {
    reference: string;
    currency: string;
    subtotal: string;
    tax_amount: string;
    total: string;
    amount_paid: string;
    balance_due: string;
    issued_on?: string | null;
    due_on?: string | null;
    payment_instructions?: string | null;
    customer?: { name: string; email?: string | null; phone?: string | null } | null;
    work_order?: { reference: string } | null;
    team?: { name: string; settings?: { legal_name?: string | null; payment_instructions?: string | null } | null } | null;
    line_items: { id: number; description: string; quantity: string; unit_price: string; line_total: string }[];
};

export default function InvoicePrint({ invoice }: { invoice: Invoice }) {
    const legalName = invoice.team?.settings?.legal_name || invoice.team?.name || 'ServicePilot';

    return (
        <>
            <Head title={`Print ${invoice.reference}`} />
            <div className="mx-auto min-h-screen max-w-3xl bg-white p-10 text-slate-900">
                <div className="mb-8 flex items-start justify-between">
                    <div className="flex items-center gap-3">
                        <img src="/images/servicepilot-logo.jpg" alt="" className="size-12 rounded object-cover" />
                        <div>
                            <p className="text-lg font-semibold">{legalName}</p>
                            <p className="text-sm text-slate-500">Prepared with ServicePilot</p>
                        </div>
                    </div>
                    <div className="text-right">
                        <p className="text-2xl font-semibold">{invoice.reference}</p>
                        <p className="text-sm">Issued {invoice.issued_on ?? '—'}</p>
                        <p className="text-sm">Due {invoice.due_on ?? '—'}</p>
                    </div>
                </div>
                <div className="mb-8 grid gap-4 sm:grid-cols-2">
                    <div>
                        <p className="text-xs uppercase tracking-wide text-slate-500">Bill to</p>
                        <p className="font-medium">{invoice.customer?.name}</p>
                        <p className="text-sm">{invoice.customer?.email}</p>
                        <p className="text-sm">{invoice.customer?.phone}</p>
                    </div>
                    <div>
                        {invoice.work_order && <p className="text-sm">Work order {invoice.work_order.reference}</p>}
                    </div>
                </div>
                <table className="mb-8 w-full text-left text-sm">
                    <thead>
                        <tr className="border-b">
                            <th className="py-2">Description</th>
                            <th>Qty</th>
                            <th>Price</th>
                            <th className="text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        {invoice.line_items.map((line) => (
                            <tr key={line.id} className="border-b">
                                <td className="py-2">{line.description}</td>
                                <td>{line.quantity}</td>
                                <td>{line.unit_price}</td>
                                <td className="text-right">{line.line_total}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
                <div className="ml-auto max-w-xs space-y-1 text-sm">
                    <div className="flex justify-between"><span>Subtotal</span><span>{invoice.subtotal}</span></div>
                    <div className="flex justify-between"><span>Tax</span><span>{invoice.tax_amount}</span></div>
                    <div className="flex justify-between font-semibold"><span>Total</span><span>{invoice.currency} {invoice.total}</span></div>
                    <div className="flex justify-between"><span>Paid</span><span>{invoice.amount_paid}</span></div>
                    <div className="flex justify-between"><span>Balance</span><span>{invoice.balance_due}</span></div>
                </div>
                {(invoice.payment_instructions || invoice.team?.settings?.payment_instructions) && (
                    <p className="mt-8 text-sm text-slate-600">
                        {invoice.payment_instructions || invoice.team?.settings?.payment_instructions}
                    </p>
                )}
            </div>
        </>
    );
}
