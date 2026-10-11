import { Head, Link, router, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginated } from '@/types';

type ServiceRequest = {
    id: number;
    reference: string;
    subject: string;
    status: string;
    priority: string;
    source: string;
    customer?: { id: number; name: string } | null;
    assignee?: { id: number; name: string } | null;
};

export default function RequestsIndex({
    requests,
    filters,
}: {
    requests: Paginated<ServiceRequest>;
    filters: { search?: string; status?: string };
}) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title="Service requests" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Service requests"
                    description="Intake, dispatch, and conversion pipeline"
                    actions={
                        <Button asChild>
                            <Link href={`/${slug}/requests/create`}>New request</Link>
                        </Button>
                    }
                />
                <form
                    className="flex flex-col gap-3 sm:flex-row"
                    onSubmit={(event) => {
                        event.preventDefault();
                        const form = new FormData(event.currentTarget);
                        router.get(`/${slug}/requests`, {
                            search: String(form.get('search') ?? ''),
                            status: String(form.get('status') ?? ''),
                        });
                    }}
                >
                    <Input name="search" defaultValue={filters.search} placeholder="Search reference or subject" />
                    <Input name="status" defaultValue={filters.status} placeholder="Status" />
                    <Button type="submit" variant="secondary">Filter</Button>
                </form>
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[720px] text-left text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 font-medium">Reference</th>
                                <th className="px-4 py-3 font-medium">Subject</th>
                                <th className="px-4 py-3 font-medium">Customer</th>
                                <th className="px-4 py-3 font-medium">Priority</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {requests.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-10 text-center text-muted-foreground">
                                        No service requests yet.
                                    </td>
                                </tr>
                            )}
                            {requests.data.map((item) => (
                                <tr key={item.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <Link className="font-medium hover:underline" href={`/${slug}/requests/${item.reference}`}>
                                            {item.reference}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">{item.subject}</td>
                                    <td className="px-4 py-3">{item.customer?.name ?? 'Unlinked'}</td>
                                    <td className="px-4 py-3"><StatusBadge value={item.priority} /></td>
                                    <td className="px-4 py-3"><StatusBadge value={item.status} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
