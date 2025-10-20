import Layout from '@/Layout';
import Stat from '@/components/Activity/Stat';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

type Activity = {
    name: string;
    distance: string;
    elapsed_time: string;
    moving_time: string;
    total_elevation_gain: string;
    type: ActivityType;
};

type ActivityType = 'Run' | 'WeightTraining';

export default function Home({ activity }: { activity: Activity }) {
    return (
        <Layout>
            <div className="flex items-center space-x-4">
                <Avatar className="h-14 w-14 rounded-lg">
                    <AvatarImage src="lee-small.jpg" alt="@leepownall" />
                    <AvatarFallback>LP</AvatarFallback>
                </Avatar>
                <h1 className="tracking-tigher text-2xl font-semibold xl:text-3xl">Lee Pownall</h1>
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
                <h2 className="text-xl font-semibold tracking-tight">Latest Activity</h2>
                <div className="mt-4 grid grid-cols-1 gap-2 tracking-tight">
                    <Stat heading="Name" value={activity.name} />
                </div>
                <div className="mt-4 grid grid-cols-3 gap-2 tracking-tight sm:grid-cols-6">
                    <Stat heading="Distance" value={`${activity.distance} km`} visible={activity.type === 'Run'} />
                    <Stat heading="Elapsed Time" value={activity.elapsed_time} />
                    <Stat heading="Moving Time" value={activity.moving_time} />
                    <Stat heading="Elevation Gain" value={`${activity.total_elevation_gain} km`} visible={activity.type === 'Run'} />
                </div>
            </div>
        </Layout>
    );
}
