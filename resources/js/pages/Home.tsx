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
    started_at: string;
};

type ActivityType = 'Run' | 'WeightTraining';

export default function Home({ activity, steps }: { activity: Activity, steps: number }) {
    return (
        <Layout>
            <div className="flex items-center space-x-4">
                <Avatar className="h-16 w-16 rounded-lg">
                    <AvatarImage src="lee-small.jpg" alt="@leepownall" />
                    <AvatarFallback>LP</AvatarFallback>
                </Avatar>
                <div className='flex flex-col'>
                    <h1 className="tracking-tigher text-2xl font-semibold">Lee Pownall</h1>
                    <h2 className="tracking-tigher text-lg">Developer & Runner</h2>
                </div>
            </div>
            <p className="mt-6 leading-7 tracking-tight">A Senior Developer from the West Midlands, currently building things in Laravel. I also enjoy running, currently focusing on 5k, 10k and half marathon distances.</p>

            <div className="mt-8">
                <h2 className="text-md font-semibold">Stats</h2>
                <div className="mt-4 grid grid-cols-2 gap-2 tracking-tight">
                    <Stat heading="Steps Today" value={steps} />
                </div>
            </div>

            <div className="mt-8">
                <h2 className="text-md font-semibold">Latest Activity</h2>
                <div className="mt-4 grid grid-cols-2 gap-2 tracking-tight">
                    <Stat heading="Name" value={activity.name} />
                    <Stat heading="Date" value={activity.started_at} />
                    <Stat heading="Distance" value={`${activity.distance} km`} visible={activity.type === 'Run'} />
                    <Stat heading="Elapsed Time" value={activity.elapsed_time} />
                    <Stat heading="Moving Time" value={activity.moving_time} />
                    <Stat heading="Elevation Gain" value={`${activity.total_elevation_gain} m`} visible={activity.type === 'Run'} />
                </div>
            </div>
        </Layout>
    );
}
