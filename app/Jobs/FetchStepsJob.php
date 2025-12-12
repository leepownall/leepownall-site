<?php

namespace App\Jobs;

use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\ElementNotFoundException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchStepsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(): void
    {
        $browserFactory = new BrowserFactory;

        try {
            $browser = $browserFactory->createBrowser([
                'headless' => false,
                'userAgent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'customFlags' => [
                    '--disable-blink-features=AutomationControlled',
                    '--disable-dev-shm-usage',
                    '--no-sandbox',
                ],
            ]);

            $page = $browser->createPage();
            $page->navigate('https://sso.garmin.com/portal/sso/en-GB/sign-in?clientId=GarminConnect')->waitForNavigation();

            // Wait for JavaScript to execute and page to be ready
            sleep(3);

            // Get credentials from config
            $email = config('services.garmin.username');
            $password = config('services.garmin.password');

            // Wait for email field to be available (retry logic)
            $maxAttempts = 15;
            $attempt = 0;
            while ($attempt < $maxAttempts) {
                try {
                    $page->mouse()->find('#email');
                    break;
                } catch (ElementNotFoundException $e) {
                    $attempt++;
                    if ($attempt >= $maxAttempts) {
                        throw new \RuntimeException('Email field not found after waiting. Page may be showing an error or blocking automation.', 0, $e);
                    }
                    usleep(500000); // Wait 0.5 seconds
                }
            }

            // Click on email field and type email
            try {
                $page->mouse()->find('#email')->click();
                $page->keyboard()->typeText($email);
            } catch (ElementNotFoundException $e) {
                throw new \RuntimeException('Email field not found on page', 0, $e);
            }

            // Click on password field and type password
            try {
                $page->mouse()->find('#password')->click();
                $page->keyboard()->typeText($password);
            } catch (ElementNotFoundException $e) {
                throw new \RuntimeException('Password field not found on page', 0, $e);
            }

            // Find and click submit button
            try {
                $page->mouse()->find('button[type="submit"]')->click();
                $page->waitForReload();
            } catch (ElementNotFoundException $e) {
                throw new \RuntimeException('Submit button not found on page', 0, $e);
            }
        } finally {
            $browser->close();
        }
    }
}
