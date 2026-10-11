import { Form, Head, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function CustomersCreate() {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title="New customer" />
            <div className="mx-auto flex max-w-3xl flex-col gap-6 p-4 md:p-6">
                <PageHeader title="New customer" description="Customer profile and an optional first service location" />
                <Form action={`/${slug}/customers`} method="post" className="grid gap-4">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Name" name="name" required error={errors.name} />
                                <Field label="Company" name="company_name" error={errors.company_name} />
                                <Field label="Email" name="email" type="email" error={errors.email} />
                                <Field label="Phone" name="phone" error={errors.phone} />
                            </div>
                            <Field label="Internal notes" name="internal_notes" error={errors.internal_notes} />
                            <h2 className="pt-2 text-sm font-semibold">Service location</h2>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Location label" name="location_label" error={errors.location_label} />
                                <Field label="Address" name="location_address_line1" error={errors.location_address_line1} />
                                <Field label="City" name="location_city" error={errors.location_city} />
                                <Field label="Region" name="location_region" error={errors.location_region} />
                                <Field label="Postal code" name="location_postal_code" error={errors.location_postal_code} />
                            </div>
                            <Button disabled={processing} type="submit">Save customer</Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

function Field({
    label,
    name,
    type = 'text',
    required,
    error,
}: {
    label: string;
    name: string;
    type?: string;
    required?: boolean;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={name}>{label}</Label>
            <Input id={name} name={name} type={type} required={required} />
            <InputError message={error} />
        </div>
    );
}
