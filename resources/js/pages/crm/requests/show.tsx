import { Head, Link, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type ServiceRequest = {
    id: number;
    reference: string;
    subject: string;
    description: string;
    status: string;
    priority: string;
    source: string;
    contact_name?: string | null;
    contact_email?: string | null;
    contact_phone?: string | null;
    internal_notes?: string | null;
    customer?: { id: number; name: string } | null;
    location?: { id: number; label: string; address_line1: string; city: string } | null;
    assignee?: { id: number; name: string } | null;
};

export default function RequestsShow({ serviceRequest }: { serviceRequest: ServiceRequest }) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title={serviceRequest.reference} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={serviceRequest.subject}
                    description={serviceRequest.reference}
                    actions={
                        <Button asChild>
                            <Link href={`/${slug}/estimates/create`}>Create estimate</Link>
                        </Button>
                    }
                />
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>Request</CardTitle></CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div className="flex flex-wrap gap-2">
                                <StatusBadge value={serviceRequest.status} />
                                <StatusBadge value={serviceRequest.priority} />
                            </div>
                            <p>{serviceRequest.description}</p>
                            {serviceRequest.internal_notes && (
                                <p className="text-muted-foreground">{serviceRequest.internal_notes}</p>
                            )}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Contact</CardTitle></CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {serviceRequest.customer ? (
                                <p>
                                    Customer{' '}
                                    <Link className="font-medium hover:underline" href={`/${slug}/customers/${serviceRequest.customer.id}`}>
                                        {serviceRequest.customer.name}
                                    </Link>
                                </p>
                            ) : (
                                <p>Unlinked caller</p>
                            )}
                            <p>{serviceRequest.contact_name ?? '—'}</p>
                            <p>{serviceRequest.contact_email ?? 'No email'}</p>
                            <p>{serviceRequest.contact_phone ?? 'No phone'}</p>
                            <p className="text-muted-foreground">Source {serviceRequest.source}</p>
                            {serviceRequest.location && (
                                <p>{serviceRequest.location.label}: {serviceRequest.location.address_line1}, {serviceRequest.location.city}</p>
                            )}
                            {serviceRequest.assignee && <p>Assigned to {serviceRequest.assignee.name}</p>}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
