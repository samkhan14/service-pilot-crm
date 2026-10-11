import { Form, Head, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

type Organization = { name: string; slug: string } | null;

export default function PublicIntake({ organization }: { organization: Organization }) {
    const success = usePage().props.success as string | undefined;

    return (
        <>
            <Head title="Request service" />
            <div className="min-h-screen bg-[radial-gradient(circle_at_top,_rgba(15,118,110,0.12),_transparent_40%)] px-4 py-10">
                <div className="mx-auto flex max-w-xl flex-col gap-6 rounded-2xl border bg-card p-6 shadow-sm md:p-8">
                    <div className="flex items-center gap-3">
                        <img src="/images/servicepilot-logo.jpg" alt="" className="size-10 rounded-md object-cover" />
                        <div>
                            <p className="font-semibold">ServicePilot</p>
                            <p className="text-sm text-muted-foreground">
                                {organization ? `Request service from ${organization.name}` : 'Request field service'}
                            </p>
                        </div>
                    </div>
                    {success && (
                        <div className="rounded-md border border-emerald-200 bg-emerald-50 px-3 py-2 text-sm text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-200">
                            {success}
                        </div>
                    )}
                    <Form action="/request-service" method="post" className="grid gap-4">
                        {({ errors, processing }) => (
                            <>
                                {organization ? (
                                    <input type="hidden" name="organization_slug" value={organization.slug} />
                                ) : (
                                    <div className="grid gap-2">
                                        <Label htmlFor="organization_slug">Organization slug</Label>
                                        <Input id="organization_slug" name="organization_slug" placeholder="your-organization" />
                                        <InputError message={errors.organization_slug} />
                                    </div>
                                )}
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_name">Your name</Label>
                                    <Input id="contact_name" name="contact_name" required />
                                    <InputError message={errors.contact_name} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_email">Email</Label>
                                    <Input id="contact_email" name="contact_email" type="email" required />
                                    <InputError message={errors.contact_email} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="contact_phone">Phone</Label>
                                    <Input id="contact_phone" name="contact_phone" />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="city">City</Label>
                                    <Input id="city" name="city" />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="subject">What do you need?</Label>
                                    <Input id="subject" name="subject" required />
                                    <InputError message={errors.subject} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="description">Details</Label>
                                    <textarea id="description" name="description" required className="min-h-28 rounded-md border border-input bg-transparent px-3 py-2 text-sm" />
                                    <InputError message={errors.description} />
                                </div>
                                <Button disabled={processing} type="submit">Submit request</Button>
                            </>
                        )}
                    </Form>
                </div>
            </div>
        </>
    );
}
