import Layout from '@/Layout';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';

export default function Home() {
    return (
        <Layout>
            <div className="flex items-center space-x-4">
                <Avatar className="h-16 w-16 rounded-lg">
                    <AvatarImage src="lee-small.jpg" alt="@leepownall" />
                    <AvatarFallback>LP</AvatarFallback>
                </Avatar>
                <div className="flex flex-col">
                    <h1 className="tracking-tigher text-2xl font-semibold">Lee Pownall</h1>
                    <h2 className="tracking-tigher text-lg">Developer & Runner</h2>
                </div>
            </div>
            <p className="mt-6 leading-7 tracking-tight">
                A Senior Developer from the West Midlands, currently building things in Laravel. I also enjoy running, currently focusing on 5k, 10k
                and half marathon distances.
            </p>
        </Layout>
    );
}
