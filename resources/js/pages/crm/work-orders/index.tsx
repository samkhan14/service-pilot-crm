import { Head, Link, router, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginated } from '@/types';

type WorkOrder = {
    id: number;
    reference: string;
    title: string;
    status: string;
    priority: string;
    scheduled_start_at?: string | null;
    customer?: { id: number; name: string } | null;
    assignments?: { id: number; user?: { name: string } | null }[];
};

export default function WorkOrdersIndex({
    workOrders,
    filters,
}: {
    workOrders: Paginated<WorkOrder>;
    filters: { status?: string };
}) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title="Work orders" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Work orders"
                    description="Schedule, assign, and complete field jobs"
                    actions={
                        <Button asChild>
                            <Link href={`/${slug}/work-orders/create`}>New work order</Link>
                        </Button>
                    }
                />
                <form
                    className="flex flex-col gap-3 sm:flex-row"
                    onSubmit={(event) => {
                        event.preventDefault();
                        const form = new FormData(event.currentTarget);
                        router.get(`/${slug}/work-orders`, {
                            status: String(form.get('status') ?? ''),
                        });
                    }}
                >
                    <Input name="status" defaultValue={filters.status} placeholder="Status" />
                    <Button type="submit" variant="secondary">Filter</Button>
                </form>
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[760px] text-left text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 font-medium">Reference</th>
                                <th className="px-4 py-3 font-medium">Job</th>
                                <th className="px-4 py-3 font-medium">Customer</th>
                                <th className="px-4 py-3 font-medium">Technicians</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {workOrders.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-10 text-center text-muted-foreground">
                                        No work orders yet.
                                    </td>
                                </tr>
                            )}
                            {workOrders.data.map((job) => (
                                <tr key={job.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <Link className="font-medium hover:underline" href={`/${slug}/work-orders/${job.reference}`}>
                                            {job.reference}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">
                                        <div>{job.title}</div>
                                        <div className="text-xs text-muted-foreground">{job.scheduled_start_at ?? 'Unscheduled'}</div>
                                    </td>
                                    <td className="px-4 py-3">{job.customer?.name ?? '—'}</td>
                                    <td className="px-4 py-3">
                                        {(job.assignments ?? []).map((assignment) => assignment.user?.name).filter(Boolean).join(', ') || 'Unassigned'}
                                    </td>
                                    <td className="px-4 py-3"><StatusBadge value={job.status} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
