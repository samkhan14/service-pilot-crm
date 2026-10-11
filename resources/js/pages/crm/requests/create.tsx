import { Form, Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type CustomerOption = { id: number; name: string };

export default function RequestsCreate({ customers }: { customers: CustomerOption[] }) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title="New service request" />
            <div className="mx-auto flex max-w-3xl flex-col gap-6 p-4 md:p-6">
                <PageHeader title="New service request" description="Capture a job, callback, or inbound inquiry" />
                <Form action={`/${slug}/requests`} method="post" className="grid gap-4">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="customer_id">Customer</Label>
                                <select id="customer_id" name="customer_id" className="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                                    <option value="">Unlinked / new caller</option>
                                    {customers.map((customer) => (
                                        <option key={customer.id} value={customer.id}>{customer.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="subject">Subject</Label>
                                <Input id="subject" name="subject" required />
                                <InputError message={errors.subject} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea id="description" name="description" required className="min-h-28 rounded-md border border-input bg-transparent px-3 py-2 text-sm" />
                                <InputError message={errors.description} />
                            </div>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="priority">Priority</Label>
                                    <select id="priority" name="priority" defaultValue="normal" className="h-9 rounded-md border border-input bg-transparent px-3 text-sm">
                                        <option value="low">Low</option>
                                        <option value="normal">Normal</option>
                                        <option value="high">High</option>
                                        <option value="urgent">Urgent</option>
                                    </select>
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="source">Source</Label>
                                    <Input id="source" name="source" defaultValue="manual" />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_name">Contact name</Label>
                                    <Input id="contact_name" name="contact_name" />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_email">Contact email</Label>
                                    <Input id="contact_email" name="contact_email" type="email" />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_phone">Contact phone</Label>
                                    <Input id="contact_phone" name="contact_phone" />
                                </div>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="internal_notes">Internal notes</Label>
                                <textarea id="internal_notes" name="internal_notes" className="min-h-20 rounded-md border border-input bg-transparent px-3 py-2 text-sm" />
                            </div>
                            <Button disabled={processing} type="submit">Create request</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
