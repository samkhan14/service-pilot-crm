export type CrmPermissions = {
    canViewDashboard: boolean;
    canViewReports: boolean;
    canManageSettings: boolean;
    canViewCustomers: boolean;
    canManageCustomers: boolean;
    canViewRequests: boolean;
    canManageRequests: boolean;
    canViewEstimates: boolean;
    canManageEstimates: boolean;
    canApproveEstimates: boolean;
    canViewWorkOrders: boolean;
    canManageWorkOrders: boolean;
    canAssignWorkOrders: boolean;
    canExecuteWorkOrders: boolean;
    canViewSchedule: boolean;
    canManageSchedule: boolean;
    canViewInvoices: boolean;
    canManageInvoices: boolean;
    canRecordPayments: boolean;
    canViewAllJobs: boolean;
    canViewFinancials: boolean;
};

export type Paginated<T> = {
    data: T[];
    links: { url: string | null; label: string; active: boolean }[];
    current_page: number;
    last_page: number;
    total: number;
};
