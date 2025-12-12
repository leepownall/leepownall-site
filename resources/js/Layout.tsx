import { Head, usePage } from '@inertiajs/react';

export default function Layout({ children }) {
    const { appUrl, currentYear } = usePage().props;

    return (
        <>
            <Head>
                <link rel="icon" type="image/png" href="/favicon-96x96.png" sizes="96x96" />
                <link rel="icon" type="image/svg+xml" href="/favicon.svg" />
                <link rel="shortcut icon" href="/favicon.ico" />
                <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png" />
                <meta name="apple-mobile-web-app-title" content="Lee Pownall" />
                <link rel="manifest" href="/site.webmanifest" />

                <meta name="description" content="A Senior Developer from the West Midlands, currently building things in Laravel." />

                <meta property="og:url" content="https://leepownall.com" />
                <meta property="og:type" content="website" />
                <meta property="og:title" content="Lee Pownall" />
                <meta property="og:description" content="A Senior Developer from the West Midlands, currently building things in Laravel." />
                <meta property="og:image" content={`${appUrl}/og-image.png`} />

                <meta name="twitter:card" content="summary_large_image" />
                <meta property="twitter:domain" content="leepownall.com" />
                <meta property="twitter:url" content="https://leepownall.com" />
                <meta name="twitter:title" content="Lee Pownall" />
                <meta name="twitter:description" content="A Senior Developer from the West Midlands, currently building things in Laravel." />
                <meta name="twitter:image" content={`${appUrl}/og-image.png`} />
            </Head>
            <div className="flex min-h-screen flex-col">
                <main className="mx-auto max-w-2xl flex-1 p-4 sm:p-8">{children}</main>
                <footer className="sticky bottom-0 mt-auto bg-background">
                    <div className="mx-auto flex max-w-2xl items-center justify-between border-t border-border/50 px-4 py-4 sm:px-8">
                        <span className="text-sm text-muted-foreground">© {currentYear} Lee Pownall</span>
                        <nav className="flex gap-3">
                            <a
                                href="https://x.com/leepownall"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-sm text-muted-foreground/70 hover:text-muted-foreground"
                            >
                                X
                            </a>
                            <a
                                href="https://github.com/leepownall"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-sm text-muted-foreground/70 hover:text-muted-foreground"
                            >
                                GitHub
                            </a>
                            <a
                                href="https://www.linkedin.com/in/lee-pownall"
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-sm text-muted-foreground/70 hover:text-muted-foreground"
                            >
                                LinkedIn
                            </a>
                            <a href="mailto:lee@pownall.uk" className="text-sm text-muted-foreground/70 hover:text-muted-foreground">
                                Email
                            </a>
                        </nav>
                    </div>
                </footer>
            </div>
        </>
    );
}
