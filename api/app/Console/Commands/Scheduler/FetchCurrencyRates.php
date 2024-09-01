<?php

namespace App\Console\Commands\Scheduler;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class FetchCurrencyRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'scheduler:fetch_currency_rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetches the current currency rates';

    protected $appKey;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $appKey = config('services.fixer.key');
        $this->appKey = $appKey;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        try {
            echo "Fetching currency rates\n", $this->appKey;
            $response = Http::get('http://data.fixer.io/api/latest?access_key=' . $this->appKey);
            $rates = $response->json()['rates'];
            $rates['USDC'] = $rates['USD'];

            if ($response->json()['success'] == 'true') {
                Cache::store('file')->put('rates', [
                'success' => $response->json()['success'],
                'timestamp' => $response->json()['timestamp'],
                'base' => $response->json()['base'],
                'date' => $response->json()['date'],
                'rates' => $rates,
                ]);
            }
        } catch (\Exception $exception) {
            if (config('app.env') == 'local' || config('app.env') == 'stage') {
                $response['rates'] = Http::get('https://api.exchangeratesapi.io/latest')['rates'];
                Cache::store('file')->put('rates', $response);
            }
            app('sentry')->captureException($exception);
        }

        return 0;
    }
}
