import { Head, Link, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

type Location = { id: number; label: string; address_line1: string; city: string };
type Customer = {
    id: number;
    name: string;
    company_name?: string | null;
    email?: string | null;
    phone?: string | null;
    status: string;
    internal_notes?: string | null;
    locations: Location[];
    work_orders?: { id: number; reference?: string; title: string; status: string }[];
    estimates?: { id: number; reference: string; status: string; total: string }[];
};

export default function CustomersShow({ customer }: { customer: Customer }) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title={customer.name} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={customer.name}
                    description={customer.company_name ?? 'Customer profile'}
                    actions={
                        <Button asChild variant="secondary">
                            <Link href={`/${slug}/customers/${customer.id}/edit`}>Edit</Link>
                        </Button>
                    }
                />
                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle>Contact</CardTitle></CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <div className="flex items-center gap-2">Status <StatusBadge value={customer.status} /></div>
                            <p>{customer.email ?? 'No email'}</p>
                            <p>{customer.phone ?? 'No phone'}</p>
                            {customer.internal_notes && <p className="text-muted-foreground">{customer.internal_notes}</p>}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Service locations</CardTitle></CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {customer.locations.length === 0 && <p className="text-muted-foreground">No locations yet.</p>}
                            {customer.locations.map((location) => (
                                <div key={location.id}>
                                    <p className="font-medium">{location.label}</p>
                                    <p>{location.address_line1}, {location.city}</p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Recent work orders</CardTitle></CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {(customer.work_orders ?? []).length === 0 && <p className="text-muted-foreground">No work orders yet.</p>}
                            {(customer.work_orders ?? []).map((job) => (
                                <Link key={job.id} className="flex items-center justify-between hover:underline" href={`/${slug}/work-orders/${job.reference ?? job.id}`}>
                                    <span>{job.reference ?? job.title}</span>
                                    <StatusBadge value={job.status} />
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Recent estimates</CardTitle></CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            {(customer.estimates ?? []).length === 0 && <p className="text-muted-foreground">No estimates yet.</p>}
                            {(customer.estimates ?? []).map((estimate) => (
                                <Link key={estimate.id} className="flex items-center justify-between hover:underline" href={`/${slug}/estimates/${estimate.reference ?? estimate.id}`}>
                                    <span>{estimate.reference}</span>
                                    <StatusBadge value={estimate.status} />
                                </Link>
                            ))}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
