import type { SVGAttributes } from 'react';

export default function AppLogoIcon(props: SVGAttributes<SVGElement>) {
    return (
        <svg
            {...props}
            viewBox="0 0 40 40"
            xmlns="http://www.w3.org/2000/svg"
            aria-hidden="true"
        >
            <path
                fillRule="evenodd"
                clipRule="evenodd"
                d="M20 3.5c.6 0 1.1.15 1.6.44l11 6.35A3.2 3.2 0 0 1 34.3 12.9v14.2a3.2 3.2 0 0 1-1.7 2.61l-11 6.35a3.2 3.2 0 0 1-3.2 0l-11-6.35A3.2 3.2 0 0 1 5.7 27.1V12.9a3.2 3.2 0 0 1 1.7-2.61l11-6.35c.5-.29 1-.44 1.6-.44Zm0 3.2L9.5 12.7v14.6L20 33.3l10.5-6V12.7L20 6.7Zm0 5.1 6.8 3.92v7.85L20 27.49l-6.8-3.92v-7.85L20 11.8Zm0 3.55-3.2 1.85v3.7L20 22.75l3.2-1.85v-3.7L20 15.35Z"
            />
        </svg>
    );
}
