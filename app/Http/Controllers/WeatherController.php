<?php

namespace App\Http\Controllers;

use App\Models\ApiConfiguration;
use App\Models\Weather;
use App\Models\WeatherLocation;
use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WeatherController extends Controller
{
    public function index()
    {
        $locationId = request()->get('id', 1);
        $weatherLocation = WeatherLocation::find($locationId);
        $weatherData = Weather::where('location_id', $weatherLocation->id)->orderBy('recorded_at', 'desc')->first();
        return returnData(2000, $weatherData);
    }
    
    public function getForecasts(Request $request, $locationId = 1) {
        $client = new Client();
        $config = ApiConfiguration::where('slug', 'accu-weather')->first();
        if(!$config) {
            return null;
        }
        
        // Ukhiya 27816
        $locationCode = WeatherLocation::find($locationId)->code;
        
        $apiBase = $config->base_url;
        $apiToken = $config->token;
        $dayCount = request()->get('day', 5);
        
        $path = "/forecasts/v1/daily/{$dayCount}day/$locationCode?details=true&metric=true";

            $method = 'GET';
            $response = $client->get("{$apiBase}{$path}", [
                'headers' => [
                    'Authorization'  => "Bearer $apiToken",
                ]
            ]);

        $weatherForecastData = json_decode($response->getBody(), true);
        

        return returnData(2000, $weatherForecastData);
    }

    public function getStatus($localCode)
    {
        $client = new Client();
        $config = ApiConfiguration::where('slug', 'accu-weather')->first();
        if(!$config) {
            return null;
        }
        
        $apiBase = $config->base_url;
        $apiToken = $config->token;

        $path = "/currentconditions/v1/$localCode?details=true";

        $method = 'GET';
        $response = $client->get("{$apiBase}{$path}", [
            'headers' => [
                'Authorization'  => "Bearer $apiToken",
            ]
        ]);

        $data = json_decode($response->getBody(), true);

        if (!$data) {
            throw new \Exception("Failed to fetch device status: ");
        }

        return $data[0];
    }

    public function fetchAndStoreStatus(Request $request, $locationId = 1)
    {
        try {
            // Ukhiya 27816
            $locationCode = WeatherLocation::find($locationId)->code;
            $weatherData = $this->getStatus($locationCode);
            if(!$weatherData) { return null; }
            
            $weather = Weather::create([
                'location_id' => $locationId,
                'local_observation_date_time' => @$weatherData['LocalObservationDateTime'],
                'epoch_time' => @$weatherData['EpochTime'],
                'weather_text' => @$weatherData['WeatherText'],
                'weather_icon' => @$weatherData['WeatherIcon'],
                'has_precipitation' => @$weatherData['HasPrecipitation'],
                'precipitation_type' => @$weatherData['PrecipitationType'],
                'is_day_time' => @$weatherData['IsDayTime'],
                'temperature' => @$weatherData['Temperature']['Metric']['Value'],
                'real_feel_temperature' => @$weatherData['RealFeelTemperature']['Metric']['Value'],
                'real_feel_temperature_phrase' => @$weatherData['RealFeelTemperature']['Metric']['Phrase'],
                'real_feel_temperature_shade' => @$weatherData['RealFeelTemperatureShade']['Metric']['Value'],
                'real_feel_temperature_shade_phrase' => @$weatherData['RealFeelTemperatureShade']['Metric']['Phrase'],
                'uv_index' => @$weatherData['UVIndex'],
                'uv_index_text' => @$weatherData['UVIndexText'],
                'wind' => @$weatherData['Wind']['Speed']['Metric']['Value'],
                'wind_direction' => @$weatherData['Wind']['Direction']['Degrees'],
                'wind_direction_text' => @$weatherData['Wind']['Direction']['Localized'],
                'wind_gust' => @$weatherData['WindGust']['Speed']['Metric']['Value'],
                'relative_humidity' => @$weatherData['RelativeHumidity'],
                'indoor_relative_humidity' => @$weatherData['IndoorRelativeHumidity'],
                'dew_point' => @$weatherData['DewPoint']['Metric']['Value'],
                'pressure' => @$weatherData['Pressure']['Metric']['Value'],
                'pressure_tendency' => @$weatherData['PressureTendency']['LocalizedText'],
                'pressure_tendency_code' => @$weatherData['PressureTendency']['Code'],
                'cloud_cover' => @$weatherData['CloudCover'],
                'visibility' => @$weatherData['Visibility']['Metric']['Value'],
                'ceiling' => @$weatherData['Ceiling']['Metric']['Value'],
                'recorded_at' => @$weatherData['LocalObservationDateTime'],
            ]);

            return [
                'success' => true,
                'message' => 'Weather data fetched and stored successfully',
                'data' => [
                    'weather' => $weather,
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Weather data fetch/store failed for location {$locationId}: ".$e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
    
    
    public function fetchStatus(Request $request, $locationId = 1)
    {
        try {
            // Ukhiya 27816
            $locationCode = WeatherLocation::find($locationId)->code;
            $weatherData = $this->getStatus($locationCode);
            if(!$weatherData) { return null; }
            
            $weather = Weather::orderBy('recorded_at', 'desc')->first();

            return [
                'success' => true,
                'message' => 'Weather data fetched successfully',
                'data' => [
                    'weather' => $weather,
                ]
            ];

        } catch (\Exception $e) {
            Log::error("Weather data fetch failed for location {$locationId}: ".$e->getMessage());
            return ['success' => false, 'msg' => $e->getMessage()];
        }
    }
    
}