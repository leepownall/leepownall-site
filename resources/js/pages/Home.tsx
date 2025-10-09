import Layout from "@/Layout";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";

export default function Home() {
    return (
        <Layout>
            <Avatar className='rounded-lg h-20 w-20'>
                <AvatarImage src="lee.jpg" alt="@leepownall" />
                <AvatarFallback>LP</AvatarFallback>
            </Avatar>
            <h1 className='scroll-m-20 text-4xl font-semibold tracking-tight sm:text-3xl xl:text-4xl mt-4'>Lee Pownall</h1>
            <p className='text-muted-foreground text-xl mt-4'>A Senior Developer from the West Midlands, currently building things in Laravel.</p>
            <p className='leading-7 mt-6'>I also enjoy running, currently focusing on 5k, 10k and half marathon distances.</p>
            <p className='leading-7 mt-2'>Some code can be seen on <a href='https://github.com/leepownall' className='text-primary font-medium underline underline-offset-4'>GitHub</a>.</p>
            <p className='leading-7 mt-2'>We can connect on <a href='https://www.linkedin.com/in/lee-pownall' className='text-primary font-medium underline underline-offset-4'>LinkedIn</a> or email me at <a href='mailto:lee@pownall.uk' className='text-primary font-medium underline underline-offset-4'>lee@pownall.uk</a>.</p>
        </Layout>
    )
}
