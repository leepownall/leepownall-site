<?php

namespace App\Jobs;

use HeadlessChromium\BrowserFactory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchHeartRateJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $browserFactory = new BrowserFactory();

        try {
            $browser = $browserFactory->createBrowser([
                'headless' => false,
            ]);

            $page = $browser->createPage();
            $page->navigate('https://sso.garmin.com/portal/sso/en-GB/sign-in?clientId=GarminConnect')->waitForNavigation();

            // get page title
            $pageTitle = $page->evaluate('document.title')->getReturnValue();

            $page->dom()->querySelector('#email')->c

            dump($pageTitle);
        } finally {
            $browser->close();
        }
    }
}
