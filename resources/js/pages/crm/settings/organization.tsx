import { Form, Head } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Settings = {
    legal_name?: string | null;
    timezone: string;
    currency: string;
    tax_label: string;
    default_tax_rate: string | number;
    working_hours_start: number;
    working_hours_end: number;
    default_payment_terms_days: number;
    payment_instructions?: string | null;
};

export default function OrganizationSettings({ settings }: { settings: Settings }) {
    return (
        <>
            <Head title="Organization settings" />
            <div className="mx-auto flex max-w-3xl flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Organization settings" description="Tax, currency, working hours, and payment terms" />
                <Form method="put" className="grid gap-4">
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-4 sm:grid-cols-2">
                                <Field label="Legal name" name="legal_name" defaultValue={settings.legal_name ?? ''} error={errors.legal_name} />
                                <Field label="Timezone" name="timezone" defaultValue={settings.timezone} error={errors.timezone} />
                                <Field label="Currency" name="currency" defaultValue={settings.currency} error={errors.currency} />
                                <Field label="Tax label" name="tax_label" defaultValue={settings.tax_label} error={errors.tax_label} />
                                <Field label="Default tax rate" name="default_tax_rate" defaultValue={String(settings.default_tax_rate)} error={errors.default_tax_rate} />
                                <Field label="Payment terms (days)" name="default_payment_terms_days" type="number" defaultValue={String(settings.default_payment_terms_days)} error={errors.default_payment_terms_days} />
                                <Field label="Working hours start" name="working_hours_start" type="number" defaultValue={String(settings.working_hours_start)} error={errors.working_hours_start} />
                                <Field label="Working hours end" name="working_hours_end" type="number" defaultValue={String(settings.working_hours_end)} error={errors.working_hours_end} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="payment_instructions">Payment instructions</Label>
                                <textarea id="payment_instructions" name="payment_instructions" defaultValue={settings.payment_instructions ?? ''} className="min-h-24 rounded-md border border-input bg-transparent px-3 py-2 text-sm" />
                            </div>
                            <Button disabled={processing} type="submit">Save settings</Button>
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
    defaultValue,
    error,
}: {
    label: string;
    name: string;
    type?: string;
    defaultValue?: string;
    error?: string;
}) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={name}>{label}</Label>
            <Input id={name} name={name} type={type} defaultValue={defaultValue} />
            <InputError message={error} />
        </div>
    );
}
