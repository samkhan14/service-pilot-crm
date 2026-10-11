import { Form, Head, Link, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CrmPermissions } from '@/types';

type Invoice = {
    id: number;
    reference: string;
    status: string;
    currency: string;
    subtotal: string;
    tax_amount: string;
    total: string;
    amount_paid: string;
    balance_due: string;
    issued_on?: string | null;
    due_on?: string | null;
    payment_instructions?: string | null;
    customer?: { id: number; name: string } | null;
    work_order?: { reference: string } | null;
    line_items: { id: number; description: string; quantity: string; unit_price: string; line_total: string }[];
    payments: { id: number; amount: string; method: string; paid_on: string; reference?: string | null; recorder?: { name: string } | null }[];
};

export default function InvoicesShow({ invoice }: { invoice: Invoice }) {
    const page = usePage();
    const slug = page.props.currentTeam?.slug;
    const permissions = page.props.crmPermissions as CrmPermissions | null;
    const errors = page.props.errors as Record<string, string>;

    return (
        <>
            <Head title={invoice.reference} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={invoice.reference}
                    description={invoice.customer?.name ?? 'Invoice'}
                    actions={
                        <Button asChild variant="secondary">
                            <Link href={`/${slug}/invoices/${invoice.reference}/print`}>Print</Link>
                        </Button>
                    }
                />
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader><CardTitle>Charges</CardTitle></CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <table className="w-full text-left">
                                <thead>
                                    <tr className="text-muted-foreground">
                                        <th className="pb-2">Description</th>
                                        <th className="pb-2">Qty</th>
                                        <th className="pb-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {invoice.line_items.map((line) => (
                                        <tr key={line.id} className="border-t">
                                            <td className="py-2">{line.description}</td>
                                            <td>{line.quantity}</td>
                                            <td className="text-right">{line.line_total}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                            <div className="space-y-1 text-right">
                                <p>Subtotal {invoice.subtotal}</p>
                                <p>Tax {invoice.tax_amount}</p>
                                <p className="text-lg font-semibold">{invoice.currency} {invoice.total}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Balance</CardTitle></CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <StatusBadge value={invoice.status} />
                            <p>Paid {invoice.amount_paid}</p>
                            <p className="font-semibold">Due {invoice.balance_due}</p>
                            <p>Issued {invoice.issued_on ?? '—'} · Due {invoice.due_on ?? '—'}</p>
                            {invoice.work_order && <p>Work order {invoice.work_order.reference}</p>}
                            {invoice.customer && (
                                <Link className="block hover:underline" href={`/${slug}/customers/${invoice.customer.id}`}>
                                    {invoice.customer.name}
                                </Link>
                            )}
                            {invoice.payment_instructions && <p className="text-muted-foreground">{invoice.payment_instructions}</p>}
                        </CardContent>
                    </Card>
                </div>
                {permissions?.canRecordPayments && !['paid', 'void', 'draft'].includes(invoice.status) && (
                    <Card>
                        <CardHeader><CardTitle>Record payment</CardTitle></CardHeader>
                        <CardContent>
                            <Form action={`/${slug}/invoices/${invoice.reference}/payments`} method="post" className="grid gap-3 sm:grid-cols-2">
                                {({ processing }) => (
                                    <>
                                        <div className="grid gap-2">
                                            <Label htmlFor="amount">Amount</Label>
                                            <Input id="amount" name="amount" required />
                                            <InputError message={errors.amount} />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="method">Method</Label>
                                            <select id="method" name="method" className="h-9 rounded-md border border-input bg-transparent px-3 text-sm" defaultValue="check">
                                                <option value="check">Check</option>
                                                <option value="card">Card</option>
                                                <option value="cash">Cash</option>
                                                <option value="ach">ACH</option>
                                                <option value="manual">Manual</option>
                                            </select>
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="reference">Reference</Label>
                                            <Input id="reference" name="reference" />
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="idempotency_key">Idempotency key</Label>
                                            <Input id="idempotency_key" name="idempotency_key" />
                                        </div>
                                        <div className="sm:col-span-2">
                                            <Button disabled={processing} type="submit">Save payment</Button>
                                        </div>
                                    </>
                                )}
                            </Form>
                        </CardContent>
                    </Card>
                )}
                <Card>
                    <CardHeader><CardTitle>Payments</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        {invoice.payments.length === 0 && <p className="text-muted-foreground">No payments recorded.</p>}
                        {invoice.payments.map((payment) => (
                            <div key={payment.id} className="flex justify-between gap-4">
                                <span>{payment.amount} via {payment.method} {payment.reference ? `· ${payment.reference}` : ''}</span>
                                <span className="text-muted-foreground">{payment.paid_on} {payment.recorder?.name ? `· ${payment.recorder.name}` : ''}</span>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
