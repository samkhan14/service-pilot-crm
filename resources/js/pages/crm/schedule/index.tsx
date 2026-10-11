import { Head, Link, router, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Technician = { id: number; name: string };
type Job = {
    id: number;
    reference: string;
    title: string;
    status: string;
    scheduled_start_at?: string | null;
    scheduled_end_at?: string | null;
    customer?: { id: number; name: string } | null;
    assignments?: { user?: { name: string } | null }[];
};

export default function ScheduleIndex({
    jobs,
    technicians,
    filters,
}: {
    jobs: Job[];
    technicians: Technician[];
    filters: { from: string; to: string; technician_id?: number | null; status?: string | null };
}) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title="Schedule" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Schedule" description="Board of scheduled work for the selected window" />
                <form
                    className="grid gap-3 md:grid-cols-5"
                    onSubmit={(event) => {
                        event.preventDefault();
                        const form = new FormData(event.currentTarget);
                        router.get(`/${slug}/schedule`, {
                            from: String(form.get('from') ?? ''),
                            to: String(form.get('to') ?? ''),
                            technician_id: String(form.get('technician_id') ?? ''),
                            status: String(form.get('status') ?? ''),
                        });
                    }}
                >
                    <Input name="from" type="date" defaultValue={filters.from} />
                    <Input name="to" type="date" defaultValue={filters.to} />
                    <select name="technician_id" defaultValue={filters.technician_id ?? ''} className="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                        <option value="">All technicians</option>
                        {technicians.map((technician) => (
                            <option key={technician.id} value={technician.id}>{technician.name}</option>
                        ))}
                    </select>
                    <Input name="status" defaultValue={filters.status ?? ''} placeholder="Status" />
                    <Button type="submit" variant="secondary">Apply</Button>
                </form>
                <div className="grid gap-3">
                    {jobs.length === 0 && (
                        <Card>
                            <CardContent className="py-10 text-center text-sm text-muted-foreground">
                                No scheduled jobs in this window.
                            </CardContent>
                        </Card>
                    )}
                    {jobs.map((job) => (
                        <Card key={job.id}>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <CardTitle className="text-base">
                                    <Link className="hover:underline" href={`/${slug}/work-orders/${job.reference}`}>
                                        {job.reference} · {job.title}
                                    </Link>
                                </CardTitle>
                                <StatusBadge value={job.status} />
                            </CardHeader>
                            <CardContent className="text-sm text-muted-foreground">
                                <p>{job.customer?.name ?? 'No customer'}</p>
                                <p>{job.scheduled_start_at} — {job.scheduled_end_at}</p>
                                <p>{(job.assignments ?? []).map((assignment) => assignment.user?.name).filter(Boolean).join(', ') || 'Unassigned'}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
            </div>
        </>
    );
}
