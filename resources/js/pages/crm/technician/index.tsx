import { Head, Link, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Job = {
    id: number;
    reference: string;
    title: string;
    status: string;
    scheduled_start_at?: string | null;
    customer?: { id: number; name: string; phone?: string | null } | null;
};

export default function TechnicianIndex({ jobs }: { jobs: Job[] }) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title="My jobs" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader title="My jobs" description="Assigned work that is scheduled or in progress" />
                <div className="grid gap-3">
                    {jobs.length === 0 && (
                        <Card>
                            <CardContent className="py-10 text-center text-sm text-muted-foreground">
                                No assigned open jobs.
                            </CardContent>
                        </Card>
                    )}
                    {jobs.map((job) => (
                        <Link key={job.id} href={`/${slug}/my-jobs/${job.reference}`}>
                            <Card className="transition hover:border-primary/40">
                                <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                    <CardTitle className="text-base">{job.title}</CardTitle>
                                    <StatusBadge value={job.status} />
                                </CardHeader>
                                <CardContent className="text-sm text-muted-foreground">
                                    <p>{job.reference}</p>
                                    <p>{job.customer?.name} {job.customer?.phone ? `· ${job.customer.phone}` : ''}</p>
                                    <p>{job.scheduled_start_at ?? 'No start time'}</p>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                </div>
            </div>
        </>
    );
}
