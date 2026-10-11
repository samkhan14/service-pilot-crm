import { Form, Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type WorkOrder = {
    id: number;
    reference: string;
    title: string;
    description?: string | null;
    status: string;
    customer?: { name: string; phone?: string | null; email?: string | null } | null;
    checklist_items: { id: number; label: string; is_completed?: boolean; completed_at?: string | null }[];
    line_items: { id: number; description: string; quantity: string }[];
    events: { id: number; type: string; occurred_at: string }[];
};

export default function TechnicianShow({ workOrder }: { workOrder: WorkOrder }) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title={workOrder.reference} />
            <div className="mx-auto flex max-w-2xl flex-col gap-6 p-4 md:p-6">
                <PageHeader title={workOrder.title} description={workOrder.reference} />
                <Card>
                    <CardHeader className="flex flex-row items-center justify-between">
                        <CardTitle>Job</CardTitle>
                        <StatusBadge value={workOrder.status} />
                    </CardHeader>
                    <CardContent className="space-y-3 text-sm">
                        <p>{workOrder.customer?.name}</p>
                        <p>{workOrder.customer?.phone ?? 'No phone'}</p>
                        <p>{workOrder.description || 'No notes'}</p>
                        <ul className="space-y-1">
                            {workOrder.checklist_items.map((item) => (
                                <li key={item.id}>{item.is_completed || item.completed_at ? '☑' : '☐'} {item.label}</li>
                            ))}
                        </ul>
                        <ul className="space-y-1">
                            {workOrder.line_items.map((line) => (
                                <li key={line.id}>{line.description} × {line.quantity}</li>
                            ))}
                        </ul>
                        <div className="flex flex-wrap gap-2 pt-2">
                            {['draft', 'scheduled', 'paused'].includes(workOrder.status) && (
                                <Form action={`/${slug}/work-orders/${workOrder.reference}/start`} method="post">
                                    {({ processing }) => <Button disabled={processing}>Start job</Button>}
                                </Form>
                            )}
                            {workOrder.status === 'in_progress' && (
                                <Form action={`/${slug}/work-orders/${workOrder.reference}/pause`} method="post">
                                    {({ processing }) => <Button disabled={processing} variant="secondary">Pause</Button>}
                                </Form>
                            )}
                            {['in_progress', 'paused'].includes(workOrder.status) && (
                                <Form action={`/${slug}/work-orders/${workOrder.reference}/complete`} method="post" className="flex gap-2">
                                    {({ processing }) => (
                                        <>
                                            <Input name="signoff_name" placeholder="Sign-off name" />
                                            <Button disabled={processing}>Complete</Button>
                                        </>
                                    )}
                                </Form>
                            )}
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
