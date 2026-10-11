import { Head, Link, usePage } from '@inertiajs/react';
import { login, register } from '@/routes';

export default function Welcome() {
    const { auth, currentTeam } = usePage().props;
    const dashboardUrl = currentTeam ? `/${currentTeam.slug}/dashboard` : '/dashboard';

    return (
        <>
            <Head title="ServicePilot" />
            <div className="min-h-screen bg-[radial-gradient(circle_at_top_left,_rgba(13,148,136,0.16),_transparent_42%),linear-gradient(180deg,#f8fafc_0%,#ffffff_55%)] text-slate-900 dark:bg-[radial-gradient(circle_at_top_left,_rgba(13,148,136,0.2),_transparent_40%),#0b1220] dark:text-slate-100">
                <header className="mx-auto flex w-full max-w-6xl items-center justify-between px-6 py-6">
                    <div className="flex items-center gap-3">
                        <img src="/images/servicepilot-logo.jpg" alt="ServicePilot" className="size-10 rounded-lg object-cover" />
                        <div>
                            <p className="font-semibold tracking-tight">ServicePilot</p>
                            <p className="text-xs text-slate-500 dark:text-slate-400">Field service CRM</p>
                        </div>
                    </div>
                    <nav className="flex items-center gap-3 text-sm">
                        <Link href="/request-service" className="rounded-md px-3 py-2 hover:bg-white/60 dark:hover:bg-white/10">
                            Request service
                        </Link>
                        {auth.user ? (
                            <Link href={dashboardUrl} className="rounded-md bg-teal-700 px-4 py-2 text-white hover:bg-teal-800">
                                Open workspace
                            </Link>
                        ) : (
                            <>
                                <Link href={login()} className="rounded-md px-3 py-2 hover:bg-white/60 dark:hover:bg-white/10">
                                    Log in
                                </Link>
                                <Link href={register()} className="rounded-md bg-teal-700 px-4 py-2 text-white hover:bg-teal-800">
                                    Create organization
                                </Link>
                            </>
                        )}
                    </nav>
                </header>
                <main className="mx-auto grid w-full max-w-6xl gap-10 px-6 pb-20 pt-8 lg:grid-cols-2 lg:items-center">
                    <div className="space-y-6">
                        <p className="inline-flex rounded-full bg-teal-100 px-3 py-1 text-xs font-medium text-teal-800 dark:bg-teal-900/50 dark:text-teal-100">
                            Operations for service businesses
                        </p>
                        <h1 className="text-4xl font-semibold tracking-tight sm:text-5xl">
                            Dispatch, quote, complete, and bill from one workspace.
                        </h1>
                        <p className="max-w-xl text-lg text-slate-600 dark:text-slate-300">
                            ServicePilot keeps customers, estimates, work orders, technician schedules, and invoices in one organization-scoped CRM. Built for HVAC, plumbing, electrical, and similar field teams.
                        </p>
                        <div className="flex flex-wrap gap-3">
                            <Link href={auth.user ? dashboardUrl : register()} className="rounded-md bg-teal-700 px-5 py-2.5 text-white hover:bg-teal-800">
                                {auth.user ? 'Go to dashboard' : 'Start a workspace'}
                            </Link>
                            <Link href="/request-service" className="rounded-md border border-slate-300 px-5 py-2.5 hover:bg-white dark:border-slate-700 dark:hover:bg-slate-900">
                                Public intake form
                            </Link>
                        </div>
                    </div>
                    <div className="grid gap-4 sm:grid-cols-2">
                        {[
                            ['Customers', 'Households, sites, and service history'],
                            ['Estimates', 'Draft, send, approve, convert'],
                            ['Work orders', 'Assign, schedule, and complete'],
                            ['Invoices', 'Issue from completed jobs and record payments'],
                        ].map(([title, copy]) => (
                            <div key={title} className="rounded-xl border border-slate-200 bg-white/80 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-950/60">
                                <p className="font-medium">{title}</p>
                                <p className="mt-1 text-sm text-slate-500 dark:text-slate-400">{copy}</p>
                            </div>
                        ))}
                    </div>
                </main>
                <footer className="mx-auto max-w-6xl px-6 pb-10 text-xs text-slate-500">
                    Demo data is fictional. Do not store real customer records in this development copy.
                </footer>
            </div>
        </>
    );
}
