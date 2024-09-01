<?php

namespace App\Console\Commands\Dev;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheCurrencyRates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dev:cache_currency_rates';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get hardcoded currency rates for dev';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $rates = config('dev.rates');

        Cache::store('file')->put('rates', $rates);

        return 0;
    }
}
