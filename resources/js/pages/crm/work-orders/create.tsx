import { Head, useForm, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Location = { id: number; label: string };
type CustomerOption = { id: number; name: string; locations?: Location[] };
type Technician = { id: number; name: string };

export default function WorkOrdersCreate({
    customers,
    technicians,
}: {
    customers: CustomerOption[];
    technicians: Technician[];
}) {
    const slug = usePage().props.currentTeam?.slug;
    const form = useForm({
        customer_id: customers[0]?.id ? String(customers[0].id) : '',
        service_location_id: '',
        title: '',
        description: '',
        priority: 'normal',
        scheduled_start_at: '',
        scheduled_end_at: '',
        internal_notes: '',
        customer_notes: '',
        technician_ids: [] as string[],
        checklist: ['Arrive on site', 'Complete safety check'],
        line_items: [{ description: '', quantity: '1', unit_price: '0', tax_rate: '', is_material: false }],
    });

    const selected = customers.find((customer) => String(customer.id) === String(form.data.customer_id));

    return (
        <>
            <Head title="New work order" />
            <div className="mx-auto flex max-w-4xl flex-col gap-6 p-4 md:p-6">
                <PageHeader title="New work order" description="Create a job, optional schedule, and technician assignment" />
                <form
                    className="grid gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(`/${slug}/work-orders`);
                    }}
                >
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="customer_id">Customer</Label>
                            <select
                                id="customer_id"
                                className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                                value={form.data.customer_id}
                                onChange={(event) => form.setData('customer_id', event.target.value)}
                            >
                                {customers.map((customer) => (
                                    <option key={customer.id} value={customer.id}>{customer.name}</option>
                                ))}
                            </select>
                            <InputError message={form.errors.customer_id} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="service_location_id">Location</Label>
                            <select
                                id="service_location_id"
                                className="h-9 rounded-md border border-input bg-transparent px-3 text-sm"
                                value={form.data.service_location_id}
                                onChange={(event) => form.setData('service_location_id', event.target.value)}
                            >
                                <option value="">None</option>
                                {(selected?.locations ?? []).map((location) => (
                                    <option key={location.id} value={location.id}>{location.label}</option>
                                ))}
                            </select>
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="title">Title</Label>
                        <Input id="title" value={form.data.title} onChange={(event) => form.setData('title', event.target.value)} required />
                        <InputError message={form.errors.title} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="description">Description</Label>
                        <textarea id="description" className="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm" value={form.data.description} onChange={(event) => form.setData('description', event.target.value)} />
                    </div>
                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="priority">Priority</Label>
                            <select id="priority" className="h-9 rounded-md border border-input bg-transparent px-3 text-sm" value={form.data.priority} onChange={(event) => form.setData('priority', event.target.value)}>
                                <option value="low">Low</option>
                                <option value="normal">Normal</option>
                                <option value="high">High</option>
                                <option value="urgent">Urgent</option>
                            </select>
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="scheduled_start_at">Start</Label>
                            <Input id="scheduled_start_at" type="datetime-local" value={form.data.scheduled_start_at} onChange={(event) => form.setData('scheduled_start_at', event.target.value)} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="scheduled_end_at">End</Label>
                            <Input id="scheduled_end_at" type="datetime-local" value={form.data.scheduled_end_at} onChange={(event) => form.setData('scheduled_end_at', event.target.value)} />
                            <InputError message={form.errors.scheduled_end_at} />
                        </div>
                    </div>
                    <div className="grid gap-2">
                        <Label>Technicians</Label>
                        <div className="grid gap-2 sm:grid-cols-2">
                            {technicians.map((technician) => (
                                <label key={technician.id} className="flex items-center gap-2 text-sm">
                                    <input
                                        type="checkbox"
                                        checked={form.data.technician_ids.includes(String(technician.id))}
                                        onChange={(event) => {
                                            const value = String(technician.id);
                                            form.setData(
                                                'technician_ids',
                                                event.target.checked
                                                    ? [...form.data.technician_ids, value]
                                                    : form.data.technician_ids.filter((id) => id !== value),
                                            );
                                        }}
                                    />
                                    {technician.name}
                                </label>
                            ))}
                        </div>
                    </div>
                    <div className="space-y-3">
                        <h2 className="text-sm font-semibold">Line items</h2>
                        {form.data.line_items.map((line, index) => (
                            <div key={index} className="grid gap-3 sm:grid-cols-4">
                                <Input
                                    placeholder="Description"
                                    value={line.description}
                                    onChange={(event) => {
                                        const lines = [...form.data.line_items];
                                        lines[index] = { ...line, description: event.target.value };
                                        form.setData('line_items', lines);
                                    }}
                                />
                                <Input
                                    placeholder="Qty"
                                    value={line.quantity}
                                    onChange={(event) => {
                                        const lines = [...form.data.line_items];
                                        lines[index] = { ...line, quantity: event.target.value };
                                        form.setData('line_items', lines);
                                    }}
                                />
                                <Input
                                    placeholder="Unit price"
                                    value={line.unit_price}
                                    onChange={(event) => {
                                        const lines = [...form.data.line_items];
                                        lines[index] = { ...line, unit_price: event.target.value };
                                        form.setData('line_items', lines);
                                    }}
                                />
                                <Input
                                    placeholder="Tax %"
                                    value={line.tax_rate}
                                    onChange={(event) => {
                                        const lines = [...form.data.line_items];
                                        lines[index] = { ...line, tax_rate: event.target.value };
                                        form.setData('line_items', lines);
                                    }}
                                />
                            </div>
                        ))}
                    </div>
                    <Button disabled={form.processing} type="submit">Create work order</Button>
                </form>
            </div>
        </>
    );
}
