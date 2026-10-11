import { Head, Link, usePage } from '@inertiajs/react';
import { useState } from 'react';
import PendingInvitationsModal from '@/components/pending-invitations-modal';
import { PageHeader } from '@/components/crm/page-header';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { CrmPermissions, DashboardInvitation } from '@/types';

type Metrics = {
    new_requests: number;
    unassigned_jobs: number;
    todays_jobs: number;
    in_progress: number;
    overdue_jobs: number;
    estimates_awaiting: number;
    awaiting_invoice: number;
    outstanding_invoices?: number;
    outstanding_balance?: string | number;
    revenue?: string | number;
    completed_jobs?: number;
    technician_workload?: { user_id: number; name: string | null; jobs: number }[];
};

type Props = {
    pendingInvitations?: DashboardInvitation[];
    metrics?: Metrics | null;
    period?: { from: string; to: string };
};

export default function Dashboard({
    pendingInvitations = [],
    metrics,
    period,
}: Props) {
    const [showInvitations, setShowInvitations] = useState(
        pendingInvitations.length > 0,
    );
    const page = usePage();
    const slug = page.props.currentTeam?.slug;
    const permissions = page.props.crmPermissions as CrmPermissions | null;

    const cards = [
        { label: 'New requests', value: metrics?.new_requests, href: slug ? `/${slug}/requests?status=new` : '#' },
        { label: 'Unassigned jobs', value: metrics?.unassigned_jobs, href: slug ? `/${slug}/work-orders` : '#' },
        { label: "Today's schedule", value: metrics?.todays_jobs, href: slug ? `/${slug}/schedule` : '#' },
        { label: 'In progress', value: metrics?.in_progress, href: slug ? `/${slug}/work-orders?status=in_progress` : '#' },
        { label: 'Overdue / at risk', value: metrics?.overdue_jobs, href: slug ? `/${slug}/work-orders` : '#' },
        { label: 'Estimates awaiting approval', value: metrics?.estimates_awaiting, href: slug ? `/${slug}/estimates?status=sent` : '#' },
        { label: 'Awaiting invoice', value: metrics?.awaiting_invoice, href: slug ? `/${slug}/work-orders?status=completed` : '#' },
    ];

    return (
        <>
            <Head title="Dashboard" />
            <PendingInvitationsModal
                invitations={pendingInvitations}
                open={pendingInvitations.length > 0 && showInvitations}
                onOpenChange={setShowInvitations}
            />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Operations dashboard"
                    description={period ? `Period ${period.from} to ${period.to}` : 'Live organization metrics'}
                />
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {cards.map((card) => (
                        <Link key={card.label} href={card.href}>
                            <Card className="h-full transition hover:border-primary/40">
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        {card.label}
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-semibold">
                                        {card.value ?? '—'}
                                    </div>
                                </CardContent>
                            </Card>
                        </Link>
                    ))}
                    {permissions?.canViewFinancials && (
                        <>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        Outstanding invoices
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-semibold">
                                        {metrics?.outstanding_invoices ?? 0}
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Balance {metrics?.outstanding_balance ?? '0.00'}
                                    </p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardHeader>
                                    <CardTitle className="text-sm font-medium text-muted-foreground">
                                        Recorded revenue
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-semibold">
                                        {metrics?.revenue ?? '0.00'}
                                    </div>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        {metrics?.completed_jobs ?? 0} jobs completed
                                    </p>
                                </CardContent>
                            </Card>
                        </>
                    )}
                </div>
                {metrics?.technician_workload && metrics.technician_workload.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Technician workload</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {metrics.technician_workload.map((row) => (
                                <div key={row.user_id} className="flex items-center justify-between text-sm">
                                    <span>{row.name}</span>
                                    <span className="font-medium">{row.jobs} open jobs</span>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                )}
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? `/${props.currentTeam.slug}/dashboard` : '/',
        },
    ],
});
