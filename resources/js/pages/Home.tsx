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
            <Avatar className="h-20 w-20 rounded-lg">
                <AvatarImage src="lee-small.jpg" alt="@leepownall" />
                <AvatarFallback>LP</AvatarFallback>
            </Avatar>
            <h1 className="mt-4 scroll-m-20 text-4xl font-semibold tracking-tight sm:text-3xl xl:text-4xl">Lee Pownall</h1>
            <p className="mt-4 text-xl text-muted-foreground">A Senior Developer from the West Midlands, currently building things in Laravel.</p>
            <p className="mt-6 leading-7">I also enjoy running, currently focusing on 5k, 10k and half marathon distances.</p>
            <p className="mt-2 leading-7">
                Some code can be seen on{' '}
                <a href="https://github.com/leepownall" className="font-medium text-primary underline underline-offset-4">
                    GitHub
                </a>
                .
            </p>
            <p className="mt-2 leading-7">
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
            <div className="mt-6 flex items-center space-x-6 border-t border-gray-200 pt-6">
                <Column heading="Name" value={activity.name} />
                <Column heading="Distance" value={`${activity.distance} km`} />
                <Column heading="Elapsed Time" value={activity.elapsed_time} />
                <Column heading="Moving Time" value={activity.moving_time} />
                <Column heading="Elevation Gain" value={`${activity.total_elevation_gain} km`} />
            </div>
        </Layout>
    );
}
