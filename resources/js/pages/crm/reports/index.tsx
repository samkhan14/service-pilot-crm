import { Head, router, usePage } from '@inertiajs/react';
import { PageHeader } from '@/components/crm/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';

type Report = {
    job_volume: number;
    completion_rate: number;
    estimate_acceptance_rate: number;
    invoices_issued: number;
    outstanding_balance: string | number;
    revenue: string | number;
    technician_workload: { name?: string | null; jobs: number }[];
};

export default function ReportsIndex({
    report,
    filters,
}: {
    report: Report;
    filters: { from: string; to: string };
}) {
    const slug = usePage().props.currentTeam?.slug;
    const cards = [
        { label: 'Job volume', value: report.job_volume },
        { label: 'Completion rate', value: `${report.completion_rate}%` },
        { label: 'Estimate acceptance', value: `${report.estimate_acceptance_rate}%` },
        { label: 'Invoices issued', value: report.invoices_issued },
        { label: 'Outstanding balance', value: report.outstanding_balance },
        { label: 'Recorded revenue', value: report.revenue },
    ];

    return (
        <>
            <Head title="Reports" />
            <div className="flex flex-col gap-6 p-4 md:p-6">
                <PageHeader title="Reports" description="Operational and billing performance for the selected period" />
                <form
                    className="flex flex-col gap-3 sm:flex-row"
                    onSubmit={(event) => {
                        event.preventDefault();
                        const form = new FormData(event.currentTarget);
                        router.get(`/${slug}/reports`, {
                            from: String(form.get('from') ?? ''),
                            to: String(form.get('to') ?? ''),
                        });
                    }}
                >
                    <Input name="from" type="date" defaultValue={filters.from} />
                    <Input name="to" type="date" defaultValue={filters.to} />
                    <Button type="submit" variant="secondary">Apply</Button>
                </form>
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
                    {cards.map((card) => (
                        <Card key={card.label}>
                            <CardHeader>
                                <CardTitle className="text-sm font-medium text-muted-foreground">{card.label}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="text-3xl font-semibold">{card.value}</div>
                            </CardContent>
                        </Card>
                    ))}
                </div>
                <Card>
                    <CardHeader><CardTitle>Technician workload</CardTitle></CardHeader>
                    <CardContent className="space-y-2 text-sm">
                        {report.technician_workload.length === 0 && <p className="text-muted-foreground">No assignments in this period.</p>}
                        {report.technician_workload.map((row, index) => (
                            <div key={`${row.name}-${index}`} className="flex justify-between">
                                <span>{row.name ?? 'Unknown'}</span>
                                <span className="font-medium">{row.jobs} jobs</span>
                            </div>
                        ))}
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
