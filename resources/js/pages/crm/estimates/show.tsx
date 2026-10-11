import { Form, Head, Link, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import type { CrmPermissions } from '@/types';

type Line = { id: number; description: string; quantity: string; unit_price: string; tax_rate: string; line_total: string };
type Estimate = {
    id: number;
    reference: string;
    status: string;
    currency: string;
    subtotal: string;
    discount_amount: string;
    tax_amount: string;
    total: string;
    notes?: string | null;
    customer_notes?: string | null;
    approved_by_name?: string | null;
    customer?: { id: number; name: string } | null;
    location?: { label: string; address_line1: string; city: string } | null;
    line_items: Line[];
};

export default function EstimatesShow({ estimate }: { estimate: Estimate }) {
    const page = usePage();
    const slug = page.props.currentTeam?.slug;
    const permissions = page.props.crmPermissions as CrmPermissions | null;

    return (
        <>
            <Head title={estimate.reference} />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title={estimate.reference}
                    description={estimate.customer?.name ?? 'Estimate'}
                    actions={
                        <div className="flex flex-wrap gap-2">
                            {permissions?.canManageEstimates && estimate.status === 'draft' && (
                                <Form action={`/${slug}/estimates/${estimate.reference}/send`} method="post">
                                    {({ processing }) => <Button disabled={processing}>Mark sent</Button>}
                                </Form>
                            )}
                            {permissions?.canApproveEstimates && ['draft', 'sent'].includes(estimate.status) && (
                                <>
                                    <Form action={`/${slug}/estimates/${estimate.reference}/approve`} method="post" className="flex gap-2">
                                        {({ processing }) => (
                                            <>
                                                <Input name="approved_by_name" placeholder="Approver name" />
                                                <Button disabled={processing} type="submit">Approve</Button>
                                            </>
                                        )}
                                    </Form>
                                    <Form action={`/${slug}/estimates/${estimate.reference}/reject`} method="post">
                                        {({ processing }) => <Button disabled={processing} variant="destructive">Reject</Button>}
                                    </Form>
                                </>
                            )}
                            {permissions?.canManageWorkOrders && estimate.status === 'approved' && (
                                <Form action={`/${slug}/estimates/${estimate.reference}/convert`} method="post">
                                    {({ processing }) => <Button disabled={processing}>Convert to work order</Button>}
                                </Form>
                            )}
                        </div>
                    }
                />
                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader><CardTitle>Line items</CardTitle></CardHeader>
                        <CardContent className="overflow-x-auto">
                            <table className="w-full text-left text-sm">
                                <thead>
                                    <tr className="text-muted-foreground">
                                        <th className="pb-2">Description</th>
                                        <th className="pb-2">Qty</th>
                                        <th className="pb-2">Price</th>
                                        <th className="pb-2 text-right">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {estimate.line_items.map((line) => (
                                        <tr key={line.id} className="border-t">
                                            <td className="py-2">{line.description}</td>
                                            <td>{line.quantity}</td>
                                            <td>{line.unit_price}</td>
                                            <td className="text-right">{line.line_total}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader><CardTitle>Summary</CardTitle></CardHeader>
                        <CardContent className="space-y-2 text-sm">
                            <StatusBadge value={estimate.status} />
                            <p>Subtotal {estimate.subtotal}</p>
                            <p>Discount {estimate.discount_amount}</p>
                            <p>Tax {estimate.tax_amount}</p>
                            <p className="text-lg font-semibold">{estimate.currency} {estimate.total}</p>
                            {estimate.customer && (
                                <Link className="block hover:underline" href={`/${slug}/customers/${estimate.customer.id}`}>
                                    {estimate.customer.name}
                                </Link>
                            )}
                            {estimate.location && <p>{estimate.location.label}: {estimate.location.address_line1}, {estimate.location.city}</p>}
                            {estimate.approved_by_name && <p>Approved by {estimate.approved_by_name}</p>}
                            {estimate.notes && <p className="text-muted-foreground">{estimate.notes}</p>}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
