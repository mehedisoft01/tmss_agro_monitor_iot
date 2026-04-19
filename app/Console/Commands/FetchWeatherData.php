<?php

namespace App\Console\Commands;

use App\Http\Controllers\WeatherController;
use Illuminate\Console\Command;

class FetchWeatherData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'accuweather:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch and Store data from AccuWeather API for all configured locations';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $locationId = 1; // default location id from WeatherLocation Model.
        $weatherCtrl = new WeatherController();
        $weatherCtrl->fetchAndStoreStatus(request(), $locationId);
        $this->info('Weather data fetch and store process completed.');
        return Command::SUCCESS;
    }
}
