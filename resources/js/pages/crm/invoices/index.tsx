import { Head, Link, router, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginated } from '@/types';

type Invoice = {
    id: number;
    reference: string;
    status: string;
    currency: string;
    total: string;
    balance_due: string;
    issued_on?: string | null;
    customer?: { id: number; name: string } | null;
};

export default function InvoicesIndex({
    invoices,
    filters,
}: {
    invoices: Paginated<Invoice>;
    filters: { status?: string };
}) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title="Invoices" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Invoices" description="Issued work, balances, and payment capture" />
                <form
                    className="flex flex-col gap-3 sm:flex-row"
                    onSubmit={(event) => {
                        event.preventDefault();
                        const form = new FormData(event.currentTarget);
                        router.get(`/${slug}/invoices`, {
                            status: String(form.get('status') ?? ''),
                        });
                    }}
                >
                    <Input name="status" defaultValue={filters.status} placeholder="Status" />
                    <Button type="submit" variant="secondary">Filter</Button>
                </form>
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 font-medium">Reference</th>
                                <th className="px-4 py-3 font-medium">Customer</th>
                                <th className="px-4 py-3 font-medium">Total</th>
                                <th className="px-4 py-3 font-medium">Balance</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {invoices.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-10 text-center text-muted-foreground">
                                        No invoices yet.
                                    </td>
                                </tr>
                            )}
                            {invoices.data.map((invoice) => (
                                <tr key={invoice.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <Link className="font-medium hover:underline" href={`/${slug}/invoices/${invoice.reference}`}>
                                            {invoice.reference}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">{invoice.customer?.name ?? '—'}</td>
                                    <td className="px-4 py-3">{invoice.currency} {invoice.total}</td>
                                    <td className="px-4 py-3">{invoice.balance_due}</td>
                                    <td className="px-4 py-3"><StatusBadge value={invoice.status} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
