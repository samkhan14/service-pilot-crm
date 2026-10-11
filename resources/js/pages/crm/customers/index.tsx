import { Head, Link, router, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { StatusBadge } from '@/components/crm/status-badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { Paginated } from '@/types';

type Customer = {
    id: number;
    name: string;
    company_name?: string | null;
    email?: string | null;
    phone?: string | null;
    status: string;
    locations_count?: number;
};

export default function CustomersIndex({
    customers,
    filters,
}: {
    customers: Paginated<Customer>;
    filters: { search?: string; status?: string };
}) {
    const slug = usePage().props.currentTeam?.slug;

    return (
        <>
            <Head title="Customers" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader
                    title="Customers"
                    description="Companies, households, and service history"
                    actions={
                        <Button asChild>
                            <Link href={`/${slug}/customers/create`}>New customer</Link>
                        </Button>
                    }
                />
                <form
                    className="flex flex-col gap-3 sm:flex-row"
                    onSubmit={(event) => {
                        event.preventDefault();
                        const form = new FormData(event.currentTarget);
                        router.get(`/${slug}/customers`, {
                            search: String(form.get('search') ?? ''),
                            status: String(form.get('status') ?? ''),
                        });
                    }}
                >
                    <Input name="search" defaultValue={filters.search} placeholder="Search name, email, phone" />
                    <Input name="status" defaultValue={filters.status} placeholder="Status" />
                    <Button type="submit" variant="secondary">Filter</Button>
                </form>
                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full min-w-[640px] text-left text-sm">
                        <thead className="bg-muted/50">
                            <tr>
                                <th className="px-4 py-3 font-medium">Name</th>
                                <th className="px-4 py-3 font-medium">Company</th>
                                <th className="px-4 py-3 font-medium">Contact</th>
                                <th className="px-4 py-3 font-medium">Locations</th>
                                <th className="px-4 py-3 font-medium">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            {customers.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="px-4 py-10 text-center text-muted-foreground">
                                        No customers yet. Create the first record.
                                    </td>
                                </tr>
                            )}
                            {customers.data.map((customer) => (
                                <tr key={customer.id} className="border-t">
                                    <td className="px-4 py-3">
                                        <Link className="font-medium hover:underline" href={`/${slug}/customers/${customer.id}`}>
                                            {customer.name}
                                        </Link>
                                    </td>
                                    <td className="px-4 py-3">{customer.company_name ?? '—'}</td>
                                    <td className="px-4 py-3">{customer.email ?? customer.phone ?? '—'}</td>
                                    <td className="px-4 py-3">{customer.locations_count ?? 0}</td>
                                    <td className="px-4 py-3"><StatusBadge value={customer.status} /></td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
