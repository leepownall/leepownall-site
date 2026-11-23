import { Head, useForm } from '@inertiajs/react';
import type { FormEvent } from 'react';

import { store } from '@/actions/App/Http/Controllers/Auth/AuthenticatedSessionController';
import Layout from '@/Layout';

const inputClassName =
    'h-10 rounded-lg border border-border bg-background px-3 text-[15px] outline-none focus-visible:border-foreground/40 focus-visible:ring-2 focus-visible:ring-foreground/10';

export default function Login() {
    const form = useForm<{ email: string; password: string; remember: boolean }>({ email: '', password: '', remember: false });

    function submit(event: FormEvent<HTMLFormElement>): void {
        event.preventDefault();
        form.submit(store(), { onFinish: () => form.reset('password') });
    }

    return (
        <Layout>
            <Head title="Sign in" />
            <div className="mx-auto flex w-full max-w-sm flex-col gap-6 pt-8 sm:pt-16">
                <h1 className="font-['Instrument_Serif'] text-5xl leading-none font-normal">Sign in</h1>
                <form onSubmit={submit} className="flex flex-col gap-4">
                    <label className="flex flex-col gap-1.5 text-sm font-medium">
                        Email
                        <input
                            type="email"
                            name="email"
                            autoComplete="email"
                            required
                            autoFocus
                            value={form.data.email}
                            onChange={(event) => form.setData('email', event.target.value)}
                            className={inputClassName}
                        />
                        {form.errors.email && <span className="text-sm font-normal text-red-600">{form.errors.email}</span>}
                    </label>
                    <label className="flex flex-col gap-1.5 text-sm font-medium">
                        Password
                        <input
                            type="password"
                            name="password"
                            autoComplete="current-password"
                            required
                            value={form.data.password}
                            onChange={(event) => form.setData('password', event.target.value)}
                            className={inputClassName}
                        />
                        {form.errors.password && <span className="text-sm font-normal text-red-600">{form.errors.password}</span>}
                    </label>
                    <label className="flex items-center gap-2 text-sm text-muted-foreground">
                        <input type="checkbox" checked={form.data.remember} onChange={(event) => form.setData('remember', event.target.checked)} />
                        Keep me signed in
                    </label>
                    <button
                        type="submit"
                        disabled={form.processing}
                        className="mt-2 h-10 rounded-lg bg-foreground text-sm font-medium text-background transition-opacity disabled:opacity-60"
                    >
                        Sign in
                    </button>
                </form>
            </div>
        </Layout>
    );
}
