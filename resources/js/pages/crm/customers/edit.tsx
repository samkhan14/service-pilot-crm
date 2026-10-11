import { Form, Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Customer = {
    id: number;
    name: string;
    company_name?: string | null;
    email?: string | null;
    phone?: string | null;
    status: string;
    internal_notes?: string | null;
};

export default function CustomersEdit({ customer }: { customer: Customer }) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title={`Edit ${customer.name}`} />
            <div className="mx-auto flex max-w-3xl flex-col gap-6 p-4 md:p-6">
                <PageHeader title={`Edit ${customer.name}`} />
                <Form action={`/${slug}/customers/${customer.id}`} method="put" className="grid gap-4">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input id="name" name="name" defaultValue={customer.name} required />
                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="company_name">Company</Label>
                                <Input id="company_name" name="company_name" defaultValue={customer.company_name ?? ''} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" name="email" type="email" defaultValue={customer.email ?? ''} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" name="phone" defaultValue={customer.phone ?? ''} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Input id="status" name="status" defaultValue={customer.status} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="internal_notes">Internal notes</Label>
                                <Input id="internal_notes" name="internal_notes" defaultValue={customer.internal_notes ?? ''} />
                            </div>
                            <Button disabled={processing} type="submit">Save changes</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}
