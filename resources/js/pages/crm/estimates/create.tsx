import { Head, useForm, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Location = { id: number; label: string };
type CustomerOption = { id: number; name: string; locations?: Location[] };
type Settings = { currency?: string; default_tax_rate?: string };

export default function EstimatesCreate({
    customers,
    settings,
}: {
    customers: CustomerOption[];
    settings?: Settings;
}) {
    const slug = usePage().props.currentTeam?.slug;
    const form = useForm({
        customer_id: customers[0]?.id ? String(customers[0].id) : '',
        service_location_id: '',
        currency: settings?.currency ?? 'USD',
        tax_rate: settings?.default_tax_rate ?? '0',
        discount_amount: '0',
        expires_on: '',
        notes: '',
        customer_notes: '',
        line_items: [{ description: '', quantity: '1', unit_price: '0', tax_rate: '' }],
    });

    const selected = customers.find((customer) => String(customer.id) === String(form.data.customer_id));

    return (
        <>
            <Head title="New estimate" />
            <div className="mx-auto flex max-w-4xl flex-col gap-6 p-4 md:p-6">
                <PageHeader title="New estimate" description="Line items are totaled with organization tax defaults" />
                <form
                    className="grid gap-4"
                    onSubmit={(event) => {
                        event.preventDefault();
                        form.post(`/${slug}/estimates`);
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
                        <div className="grid gap-2">
                            <Label htmlFor="tax_rate">Tax rate %</Label>
                            <Input id="tax_rate" value={form.data.tax_rate} onChange={(event) => form.setData('tax_rate', event.target.value)} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="discount_amount">Discount</Label>
                            <Input id="discount_amount" value={form.data.discount_amount} onChange={(event) => form.setData('discount_amount', event.target.value)} />
                            <InputError message={form.errors.discount_amount} />
                        </div>
                    </div>
                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-sm font-semibold">Line items</h2>
                            <Button
                                type="button"
                                variant="secondary"
                                onClick={() => form.setData('line_items', [
                                    ...form.data.line_items,
                                    { description: '', quantity: '1', unit_price: '0', tax_rate: '' },
                                ])}
                            >
                                Add line
                            </Button>
                        </div>
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
                                    placeholder="Line tax %"
                                    value={line.tax_rate}
                                    onChange={(event) => {
                                        const lines = [...form.data.line_items];
                                        lines[index] = { ...line, tax_rate: event.target.value };
                                        form.setData('line_items', lines);
                                    }}
                                />
                            </div>
                        ))}
                        <InputError message={form.errors['line_items.0.description']} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="notes">Internal notes</Label>
                        <textarea id="notes" className="min-h-20 rounded-md border border-input bg-transparent px-3 py-2 text-sm" value={form.data.notes} onChange={(event) => form.setData('notes', event.target.value)} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="customer_notes">Customer notes</Label>
                        <textarea id="customer_notes" className="min-h-20 rounded-md border border-input bg-transparent px-3 py-2 text-sm" value={form.data.customer_notes} onChange={(event) => form.setData('customer_notes', event.target.value)} />
                    </div>
                    <Button disabled={form.processing} type="submit">Save draft</Button>
                </form>
            </div>
        </>
    );
}
