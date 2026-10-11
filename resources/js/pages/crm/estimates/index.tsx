import { Head, Link, router, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginated } from '@/types';

type Estimate = {
    id: number;
    reference: string;
    status: string;
    total: string;
    currency: string;
    customer?: { id: number; name: string } | null;
};

export default function EstimatesIndex({
    estimates,
    filters,
}: {
    estimates: Paginated<Estimate>;
    filters: { status?: string };
}) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title="Estimates" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Estimates"
                    description="Draft, send, approve, and convert to work"
                    actions={
                        <Button asChild>
                            <Link href={`/${slug}/estimates/create`}>New estimate</Link>
                        </Button>
                    }
                />
                <form
                    className="flex flex-col gap-3 sm:flex-row"
                    onSubmit={(event) => {
                        event.preventDefault();
                        const form = new FormData(event.currentTarget);
                        router.get(`/${slug}/estimates`, {
                            status: String(form.get('status') ?? ''),
                        });
                    }}
                >
                    <Input name="status" defaultValue={filters.status} placeholder="Status" />
                    <Button type="submit" variant="secondary">Filter</Button>
                </form>
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[640px] text-left text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 font-medium">Reference</th>
                                <th className="px-4 py-3 font-medium">Customer</th>
                                <th className="px-4 py-3 font-medium">Total</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {estimates.data.length === 0 && (
                                <tr>
                                    <td colSpan={4} className="px-4 py-10 text-center text-muted-foreground">
                                        No estimates yet.
                                    </td>
                                </tr>
                            )}
                            {estimates.data.map((estimate) => (
                                <tr key={estimate.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <Link className="font-medium hover:underline" href={`/${slug}/estimates/${estimate.reference}`}>
                                            {estimate.reference}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">{estimate.customer?.name ?? '—'}</td>
                                    <td className="px-4 py-3">{estimate.currency} {estimate.total}</td>
                                    <td className="px-4 py-3"><StatusBadge value={estimate.status} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
