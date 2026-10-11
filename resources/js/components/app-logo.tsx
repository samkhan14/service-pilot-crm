import { useState } from 'react';
import { usePage } from '@inertiajs/react';

import AppLogoIcon from '@/components/app-logo-icon';

export default function AppLogo() {
    const { name } = usePage().props;
    const [failed, setFailed] = useState(false);

    return (
        <>
            <div className="flex aspect-square size-8 items-center justify-center overflow-hidden rounded-md bg-primary text-primary-foreground shadow-sm">
                {!failed ? (
                    <img
                        src="/images/servicepilot-logo.jpg"
                        alt=""
                        className="size-8 object-cover"
                        onError={() => setFailed(true)}
                    />
                ) : (
                    <AppLogoIcon className="size-5 fill-current" />
                )}
            </div>
            <div className="ml-1 grid flex-1 text-left text-sm">
                <span className="truncate leading-tight font-semibold">
                    {name}
                </span>
                <span className="truncate text-[11px] text-muted-foreground">
                    Field service CRM
                </span>
            </div>
        </>
    );
}
