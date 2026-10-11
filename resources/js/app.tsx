import { createInertiaApp } from '@inertiajs/react';
import type { ReactNode } from 'react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

const appName = import.meta.env.VITE_APP_NAME || 'ServicePilot';

function RootShell({ children }: { children: ReactNode }) {
    return (
        <>
            {children}
            <Toaster />
        </>
    );
}

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'welcome':
            case name.startsWith('crm/public/'):
            case name.endsWith('/print'):
                return RootShell;
            case name.startsWith('auth/'):
                return [RootShell, AuthLayout];
            case name.startsWith('settings/'):
            case name.startsWith('teams/'):
                return [RootShell, AppLayout, SettingsLayout];
            default:
                return [RootShell, AppLayout];
        }
    },
    strictMode: true,
    withApp(app) {
        return (
            <TooltipProvider delayDuration={0}>
                {app}
            </TooltipProvider>
        );
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on load...
initializeTheme();
