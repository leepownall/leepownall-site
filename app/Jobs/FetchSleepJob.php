<?php

namespace App\Jobs;

use App\Models\Sleep;
use HeadlessChromium\BrowserFactory;
use HeadlessChromium\Exception\ElementNotFoundException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class FetchSleepJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $timeout = 120;

    public int $tries = 3;

    public function handle(): void
    {
        $browserFactory = new BrowserFactory(config('services.browser.chrome_binary'));
        $browser = null;

        try {
            $browser = $browserFactory->createBrowser([
                'headless' => config('services.browser.headless'),
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

            // Wait for login to complete
            sleep(3);

            // Navigate to steps page with today's date
            $today = now()->format('Y-m-d');
            $stepsUrl = "https://connect.garmin.com/modern/sleep/{$today}";
            $page->navigate($stepsUrl)->waitForNavigation();

            // Wait for JavaScript to execute and page to be ready
            sleep(3);

            // Find the div with class starting with "HealthStatGauge_gaugeLabelPrimary__"
            $evaluation = $page->evaluate('
                (function() {
                    const elements = document.querySelectorAll("div[class*=\'SleepScoreSummary_dailySleepScoreValue__\']");
                    if (elements.length > 0) {
                        return elements[0].textContent.trim();
                    }
                    return null;
                })();
            ');

            $value = $evaluation->getReturnValue();

            if ($value !== null) {

                Sleep::create([
                    'amount' => (int) Str::replace(',', '', $value),
                ]);
            }
        } finally {
            if ($browser !== null) {
                $browser->close();
            }
        }
    }
}
