import { Form, Head, Link, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { CrmPermissions } from '@/types';

type Technician = { id: number; name: string };
type WorkOrder = {
    id: number;
    reference: string;
    title: string;
    description?: string | null;
    status: string;
    priority: string;
    scheduled_start_at?: string | null;
    scheduled_end_at?: string | null;
    currency: string;
    total: string;
    customer?: { id: number; name: string } | null;
    location?: { label: string; address_line1: string; city: string } | null;
    estimate?: { reference: string } | null;
    assignments: { id: number; user_id: number; user?: { name: string } | null }[];
    line_items: { id: number; description: string; quantity: string; unit_price: string; line_total: string }[];
    checklist_items: { id: number; label: string; is_completed?: boolean; completed_at?: string | null }[];
    events: { id: number; type: string; from_status?: string | null; to_status?: string | null; occurred_at: string }[];
};

export default function WorkOrdersShow({
    workOrder,
    technicians,
}: {
    workOrder: WorkOrder;
    technicians: Technician[];
}) {
    const page = usePage();
    const slug = page.props.currentTeam?.slug;
    const permissions = page.props.crmPermissions as CrmPermissions | null;
    const errors = page.props.errors as Record<string, string>;

    return (
        <>
            <Head title={workOrder.reference} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={workOrder.title}
                    description={workOrder.reference}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            {permissions?.canExecuteWorkOrders && ['draft', 'scheduled', 'paused'].includes(workOrder.status) && (
                                <Form action={`/${slug}/work-orders/${workOrder.reference}/start`} method="post">
                                    {({ processing }) => <Button disabled={processing}>Start</Button>}
                                </Form>
                            )}
                            {permissions?.canExecuteWorkOrders && workOrder.status === 'in_progress' && (
                                <Form action={`/${slug}/work-orders/${workOrder.reference}/pause`} method="post">
                                    {({ processing }) => <Button disabled={processing} variant="secondary">Pause</Button>}
                                </Form>
                            )}
                            {permissions?.canExecuteWorkOrders && ['in_progress', 'paused'].includes(workOrder.status) && (
                                <Form action={`/${slug}/work-orders/${workOrder.reference}/complete`} method="post" className="flex gap-2">
                                    {({ processing }) => (
                                        <>
                                            <Input name="signoff_name" placeholder="Customer sign-off" />
                                            <Button disabled={processing}>Complete</Button>
                                        </>
                                    )}
                                </Form>
                            )}
                            {permissions?.canManageInvoices && workOrder.status === 'completed' && (
                                <Form action={`/${slug}/work-orders/${workOrder.reference}/invoice`} method="post">
                                    {({ processing }) => <Button disabled={processing}>Issue invoice</Button>}
                                </Form>
                            )}
                        </div>
                    }
                />
                <InputError message={errors.invoice ?? errors.scheduled_start_at ?? errors.technician_ids} />
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader><CardTitle>Job details</CardTitle></CardHeader>
                        <CardContent className="space-y-3 text-sm">
                            <div className="flex flex-wrap gap-2">
                                <StatusBadge value={workOrder.status} />
                                <StatusBadge value={workOrder.priority} />
                            </div>
                            <p>{workOrder.description || 'No description'}</p>
                            {workOrder.customer && (
                                <Link className="block hover:underline" href={`/${slug}/customers/${workOrder.customer.id}`}>
                                    {workOrder.customer.name}
                                </Link>
                            )}
                            {workOrder.location && <p>{workOrder.location.label}: {workOrder.location.address_line1}, {workOrder.location.city}</p>}
                            <p>Window {workOrder.scheduled_start_at ?? 'unscheduled'} — {workOrder.scheduled_end_at ?? '—'}</p>
                            <p>Assigned: {workOrder.assignments.map((assignment) => assignment.user?.name).filter(Boolean).join(', ') || 'None'}</p>
                            {workOrder.estimate && <p>From estimate {workOrder.estimate.reference}</p>}
                            <p className="font-semibold">{workOrder.currency} {workOrder.total}</p>
                            <ul className="space-y-1">
                                {workOrder.line_items.map((line) => (
                                    <li key={line.id}>{line.description} × {line.quantity} = {line.line_total}</li>
                                ))}
                            </ul>
                            <ul className="space-y-1">
                                {workOrder.checklist_items.map((item) => (
                                    <li key={item.id}>{item.is_completed || item.completed_at ? '☑' : '☐'} {item.label}</li>
                                ))}
                            </ul>
                        </CardContent>
                    </Card>
                    <div className="space-y-4">
                        {permissions?.canManageSchedule && (
                            <Card>
                                <CardHeader><CardTitle>Schedule</CardTitle></CardHeader>
                                <CardContent>
                                    <Form action={`/${slug}/work-orders/${workOrder.reference}/schedule`} method="post" className="grid gap-3">
                                        {({ processing }) => (
                                            <>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="scheduled_start_at">Start</Label>
                                                    <Input id="scheduled_start_at" name="scheduled_start_at" type="datetime-local" required />
                                                </div>
                                                <div className="grid gap-2">
                                                    <Label htmlFor="scheduled_end_at">End</Label>
                                                    <Input id="scheduled_end_at" name="scheduled_end_at" type="datetime-local" required />
                                                </div>
                                                {technicians.map((technician) => (
                                                    <label key={technician.id} className="flex items-center gap-2 text-sm">
                                                        <input type="checkbox" name="technician_ids[]" value={technician.id} defaultChecked={workOrder.assignments.some((assignment) => assignment.user_id === technician.id)} />
                                                        {technician.name}
                                                    </label>
                                                ))}
                                                <Button disabled={processing} type="submit">Save schedule</Button>
                                            </>
                                        )}
                                    </Form>
                                </CardContent>
                            </Card>
                        )}
                        {permissions?.canAssignWorkOrders && (
                            <Card>
                                <CardHeader><CardTitle>Assign</CardTitle></CardHeader>
                                <CardContent>
                                    <Form action={`/${slug}/work-orders/${workOrder.reference}/assign`} method="post" className="grid gap-3">
                                        {({ processing }) => (
                                            <>
                                                {technicians.map((technician) => (
                                                    <label key={technician.id} className="flex items-center gap-2 text-sm">
                                                        <input type="checkbox" name="technician_ids[]" value={technician.id} defaultChecked={workOrder.assignments.some((assignment) => assignment.user_id === technician.id)} />
                                                        {technician.name}
                                                    </label>
                                                ))}
                                                <Button disabled={processing} type="submit">Update assignment</Button>
                                            </>
                                        )}
                                    </Form>
                                </CardContent>
                            </Card>
                        )}
                        {permissions?.canManageWorkOrders && !['cancelled', 'invoiced'].includes(workOrder.status) && (
                            <Card>
                                <CardHeader><CardTitle>Cancel</CardTitle></CardHeader>
                                <CardContent>
                                    <Form action={`/${slug}/work-orders/${workOrder.reference}/cancel`} method="post" className="grid gap-3">
                                        {({ processing }) => (
                                            <>
                                                <Input name="reason" placeholder="Cancellation reason" required />
                                                <Button disabled={processing} variant="destructive" type="submit">Cancel job</Button>
                                            </>
                                        )}
                                    </Form>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
                <Card>
                    <CardHeader><CardTitle>Activity</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        {workOrder.events.length === 0 && <p className="text-muted-foreground">No events yet.</p>}
                        {workOrder.events.map((event) => (
                            <div key={event.id} className="flex justify-between gap-4">
                                <span>{event.type} {event.from_status ? `${event.from_status} → ${event.to_status}` : ''}</span>
                                <span className="text-muted-foreground">{event.occurred_at}</span>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
