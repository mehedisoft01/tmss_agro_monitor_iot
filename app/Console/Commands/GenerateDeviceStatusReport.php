<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateDeviceStatusReport extends Command
{
    protected $signature = 'app:generate-device-status-report';
    protected $description = 'Generate device statuses report (15 min aggregation)';

    public function handle()
    {
        try {

            DB::statement("
            -- device_statuses_report
            
            insert device_statuses_report (device_id,online,temperature,humidity,battery_percentage,temp_alarm,hum_alarm,recorded_at,created_at,updated_at)
            select ff.device_idd,ff.online,ff.temperature,ff.humidity,ff.battery_percentage,ff.temp_alarm,ff.hum_alarm,ff.recorded_at,ff.created_at,ff.updated_at
            from(
            WITH RECURSIVE time_series AS (
                SELECT TIMESTAMP('2026-05-01 00:00:00') AS dt
                UNION ALL
                SELECT dt + INTERVAL 15 MINUTE
                FROM time_series
                WHERE dt < (
                    DATE(NOW()) 
                    + INTERVAL HOUR(NOW()) HOUR
                    + INTERVAL FLOOR(MINUTE(NOW())/15)*15 MINUTE
                )
            ),
            
            device_info AS (
                SELECT device_id 
                FROM devices
            ),
            
            data_status AS (
                SELECT 
                    a.*,
                    DATE_FORMAT(a.created_at, '%Y-%m-%d %H:%i:00') AS created_atf
                FROM device_statuses a
                WHERE a.created_at >= '2026-05-01'
                  AND a.device_id IN (SELECT device_id FROM device_info)
            ),
            
            base AS (
                SELECT 
                    d.device_id AS device_idd,
                    1 AS online,
                    t.temperature,
                    t.humidity,
                    100 AS battery_percentage,
                    'cancel' AS temp_alarm,
                    'cancel' AS hum_alarm,
                    '{\"active_time\":\"2026-04-30 15:54:26\",\"create_time\":\"2026-04-16 10:59:04\",\"update_time\":\"2026-04-30 23:06:04\"}' AS recorded_at,
                    ts.dt AS created_at,
                    ts.dt AS updated_at
                FROM time_series ts
                CROSS JOIN device_info d
                LEFT JOIN data_status t 
                    ON d.device_id = t.device_id 
                   AND ts.dt = t.created_atf
                ORDER BY d.device_id, ts.dt
            )
            
            SELECT 
                device_idd,
                online,
                battery_percentage,
                temp_alarm,
                hum_alarm,
                recorded_at,
                created_at,
                updated_at,
            
                @temp := IF(temperature IS NULL, @temp, temperature) AS temperature,
                @hum := IF(humidity IS NULL, @hum, humidity) AS humidity
            
            FROM base,
            (SELECT @temp := NULL, @hum := NULL) vars
            
            ) ff
            LEFT JOIN device_statuses_report bt 
                ON ff.device_idd = bt.device_id 
               AND ff.created_at = bt.created_at 
            WHERE bt.id IS NULL
            order by ff.device_idd,ff.created_at
                        ");

            $this->info('✅ Device status report generated successfully');

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}