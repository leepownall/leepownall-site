import Layout from "@/Layout";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import Column from '@/components/Activity/Column';
import { User } from '@/types';

export interface Activity {
    name: string;
    distance: string;
    elapsed_time: string;
    moving_time: string;
    total_elevation_gain: string;
}

export default function Home({ activity }: { activity: Activity }) {
    return (
        <Layout>
            <div className="flex items-center space-x-4">
                {/*<Avatar className="h-14 w-14 rounded-full">*/}
                {/*    <AvatarImage src="lee-small.jpg" alt="@leepownall" />*/}
                {/*    <AvatarFallback>LP</AvatarFallback>*/}
                {/*</Avatar>*/}
                <h1 className="tracking-tigher font-semibold text-2xl xl:text-3xl">Lee Pownall</h1>
            </div>
            <p className="mt-4 text-xl tracking-tight">A Senior Developer from the West Midlands, currently building things in Laravel.</p>
            <p className="t mt-6 leading-7 tracking-tight">I also enjoy running, currently focusing on 5k, 10k and half marathon distances.</p>
            <p className="t mt-2 leading-7 tracking-tight">
                Some code can be seen on{' '}
                <a href="https://github.com/leepownall" className="font-medium text-primary underline underline-offset-4">
                    GitHub
                </a>
                .
            </p>
            <p className="t mt-2 leading-7 tracking-tight">
                We can connect on{' '}
                <a href="https://www.linkedin.com/in/lee-pownall" className="font-medium text-primary underline underline-offset-4">
                    LinkedIn
                </a>{' '}
                or email me at{' '}
                <a href="mailto:lee@pownall.uk" className="font-medium text-primary underline underline-offset-4">
                    lee@pownall.uk
                </a>
                .
            </p>
            <div className="py-4">-</div>
            <div>
                <h2 className="text-xl tracking-tight font-semibold">Latest Activity</h2>
                <div className="grid grid-cols-2 gap-2 tracking-tight sm:grid-cols-6 mt-4">
                    <Column heading="Name" value={activity.name} />
                    <Column heading="Distance" value={`${activity.distance} km`} />
                    <Column heading="Elapsed Time" value={activity.elapsed_time} />
                    <Column heading="Moving Time" value={activity.moving_time} />
                    <Column heading="Elevation Gain" value={`${activity.total_elevation_gain} km`} />
                </div>
            </div>
        </Layout>
    );
}
