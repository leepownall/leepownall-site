import { Head } from '@inertiajs/react';

import Layout from '@/Layout';

export default function TrainingEmpty() {
    return (
        <Layout>
            <Head title="Training" />
            <div className="flex flex-col gap-3 pt-8 sm:pt-16">
                <h1 className="font-['Instrument_Serif'] text-5xl leading-none font-normal">No plan yet</h1>
                <p className="text-muted-foreground">The training plan will appear here once it has been imported.</p>
            </div>
        </Layout>
    );
}
