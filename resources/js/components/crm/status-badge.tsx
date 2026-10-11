import { Badge } from '@/components/ui/badge';

const tones: Record<string, string> = {
    new: 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-200',
    draft: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    sent: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
    approved: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    scheduled: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200',
    assigned: 'bg-cyan-100 text-cyan-800 dark:bg-cyan-900/40 dark:text-cyan-200',
    in_progress: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    paused: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200',
    completed: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    issued: 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
    partially_paid: 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
    paid: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    overdue: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    rejected: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    expired: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    cancelled: 'bg-slate-200 text-slate-700 dark:bg-slate-700 dark:text-slate-200',
    invoiced: 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-200',
    converted: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    active: 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-200',
    archived: 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300',
    urgent: 'bg-rose-100 text-rose-800 dark:bg-rose-900/40 dark:text-rose-200',
    high: 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200',
    normal: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
    low: 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
};

export function StatusBadge({ value }: { value?: string | null }) {
    if (!value) {
        return null;
    }

    return (
        <Badge
            variant="secondary"
            className={`capitalize ${tones[value] ?? ''}`}
        >
            {value.replaceAll('_', ' ')}
        </Badge>
    );
}
