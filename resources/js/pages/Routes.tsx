import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import { sync } from '@/actions/App/Http/Controllers/StravaRouteController';
import Layout from '@/Layout';
import { training } from '@/routes';
import type { SharedData } from '@/types';

type StravaRoute = {
    id: number;
    name: string;
    km: number;
    climb: number;
    url: string;
};

type RoutesProps = {
    nighttime: StravaRoute[];
    daytime: StravaRoute[];
};

function RouteList({ title, caption, routes }: { title: string; caption: string; routes: StravaRoute[] }) {
    return (
        <section className="flex flex-col gap-3">
            <header className="flex items-baseline justify-between gap-3 border-b border-border pb-2">
                <h2 className="text-lg font-medium">{title}</h2>
                <span className="text-sm text-muted-foreground">{caption}</span>
            </header>
            {routes.length === 0 ? (
                <p className="text-sm text-muted-foreground">None yet.</p>
            ) : (
                <ul className="flex flex-col">
                    {routes.map((route) => (
                        <li key={route.id}>
                            <a
                                href={route.url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="flex items-baseline justify-between gap-4 rounded-md px-2 py-2 hover:bg-[oklch(0.97_0_0)]"
                            >
                                <span>{route.name}</span>
                                <span className="shrink-0 text-sm text-muted-foreground tabular-nums">
                                    {route.km} km · {route.climb} m climb
                                </span>
                            </a>
                        </li>
                    ))}
                </ul>
            )}
        </section>
    );
}

export default function Routes({ nighttime, daytime }: RoutesProps) {
    const { routesSynced, errors } = usePage<SharedData>().props;
    const [syncing, setSyncing] = useState(false);

    function syncRoutes(): void {
        router.post(
            sync.url(),
            {},
            {
                preserveScroll: true,
                onStart: () => setSyncing(true),
                onFinish: () => setSyncing(false),
            },
        );
    }

    return (
        <Layout>
            <Head title="Routes" />
            <div className="flex flex-col gap-8">
                <div className="flex flex-col gap-4">
                    <Link href={training()} className="text-sm text-muted-foreground hover:text-foreground">
                        ← Training
                    </Link>
                    <div className="flex flex-wrap items-end justify-between gap-4">
                        <h1 className="font-['Instrument_Serif'] text-5xl leading-none font-normal">Routes</h1>
                        <button
                            type="button"
                            disabled={syncing}
                            onClick={syncRoutes}
                            className="rounded-md border border-border px-3 py-1 text-sm transition-colors hover:bg-[oklch(0.95_0_0)] disabled:opacity-60"
                        >
                            {syncing ? 'Syncing routes…' : 'Sync Strava routes'}
                        </button>
                    </div>
                    {errors.routes ? (
                        <p className="text-sm text-red-600">{errors.routes}</p>
                    ) : (
                        routesSynced !== null && <p className="text-sm text-muted-foreground">{routesSynced} routes synced.</p>
                    )}
                    <p className="text-sm text-muted-foreground">
                        A route is for running in the dark when its Strava name or description includes “lit” or “night”, such as “Canal loop (lit)”.
                        Every other route counts as daytime.
                    </p>
                </div>
                <RouteList title="Nighttime" caption={`${nighttime.length} lit`} routes={nighttime} />
                <RouteList title="Daytime" caption={`${daytime.length} routes`} routes={daytime} />
            </div>
        </Layout>
    );
}
