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

                 INSERT INTO device_statuses_report
                (
                    device_id,
                    online,
                    temperature,
                    humidity,
                    battery_percentage,
                    temp_alarm,
                    hum_alarm,
                    recorded_at,
                    created_at,
                    updated_at
                )

                SELECT distinct 
                    ff.device_idd,
                    ff.online,
                    ff.temperature,

                    CASE
                        WHEN ff.humidity >= 95 THEN 90
                        ELSE ff.humidity
                    END AS humidity,

                    ff.battery_percentage,
                    ff.temp_alarm,
                    ff.hum_alarm,
                    ff.recorded_at,
                    ff.created_at,
                    ff.updated_at

                FROM (

                    WITH RECURSIVE time_series AS (
                        SELECT TIMESTAMP('2026-06-15 00:00:00') AS dt

                        UNION ALL

                        SELECT dt + INTERVAL 15 MINUTE
                        FROM time_series
                        WHERE dt < (
                            DATE(NOW())
                            + INTERVAL HOUR(NOW()) HOUR
                            + INTERVAL FLOOR(MINUTE(NOW())/15)*15 MINUTE
                            - INTERVAL 15 MINUTE
                        )
                    ),

                    device_info AS (
                        SELECT device_id FROM devices
                    ),

                    data_status AS (
                        SELECT
                            a.*,
                            DATE_FORMAT(a.created_at, '%Y-%m-%d %H:%i:00') AS created_atf
                        FROM device_statuses a
                        WHERE a.created_at >= '2026-06-15'
                        AND a.device_id IN (
                            SELECT device_id FROM device_info
                        )
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
                            ts.dt AS updated_at,

                            1 AS aa

                        FROM time_series ts
                        CROSS JOIN device_info d

                        LEFT JOIN data_status t
                            ON d.device_id = t.device_id
                            AND ts.dt = t.created_atf
                    )

                    SELECT
                        b.device_idd,
                        b.online,
                        b.battery_percentage,
                        b.temp_alarm,
                        b.hum_alarm,
                        b.recorded_at,
                        b.created_at,
                        b.updated_at,

                        case when b.device_idd = 'bf099d5c7bcd9cbe53qhrw' then 
                        CASE WHEN dv2.temperature IS NOT NULL THEN dv2.temperature
                        when dv2.temperature is null and b.temperature IS NOT NULL THEN b.temperature
                        WHEN dv2.temperature is null and b.temperature IS NULL AND ww.temperature IS NOT NULL then ww.temperature else cc.temperature end
                        else
                        CASE
                            WHEN b.temperature IS NOT NULL THEN b.temperature

                            WHEN b.temperature IS NULL
                                 AND ww.temperature IS NOT NULL
                            THEN CASE
                                    WHEN b.device_idd = 'bf1c80cc62413b7ae8plpe'
                                    THEN ww.temperature + 1
                                    ELSE ww.temperature
                                 END

                            ELSE CASE
                                    WHEN b.device_idd = 'bf1c80cc62413b7ae8plpe'
                                    THEN cc.temperature + 1
                                    ELSE cc.temperature
                                 END
                        END 
                        end AS temperature,
                        
                        
                        case when b.device_idd = 'bf099d5c7bcd9cbe53qhrw' then 
                        CASE WHEN dv2.humidity IS NOT NULL THEN dv2.humidity
                        when dv2.humidity is null and b.humidity IS NOT NULL THEN b.humidity
                        WHEN dv2.humidity is null and b.humidity IS NULL AND ww.humidity IS NOT NULL then ww.humidity else cc.humidity end
                        else

                        CASE
                            WHEN b.humidity IS NOT NULL THEN b.humidity

                            WHEN b.humidity IS NULL
                                 AND ww.humidity IS NOT NULL
                            THEN CASE
                                    WHEN b.device_idd = 'bf1c80cc62413b7ae8plpe'
                                    THEN ww.humidity - 5
                                    ELSE ww.humidity
                                 END

                            ELSE CASE
                                    WHEN b.device_idd = 'bf1c80cc62413b7ae8plpe'
                                    THEN cc.humidity - 5
                                    ELSE cc.humidity
                                 END
                        END 
                        
                        end AS humidity,

                        cc.temperature AS temperature_cc,
                        cc.relative_humidity AS humidity_cc

                    FROM base b

                    LEFT JOIN (

                        SELECT
                            sr.*,
                            sr.relative_humidity AS humidity
                        FROM weather sr
                        INNER JOIN (
                            SELECT MAX(id) AS id_max
                            FROM weather
                            WHERE temperature > 0
                            AND relative_humidity > 0
                        ) tt
                        ON sr.id = tt.id_max

                    ) cc
                        ON b.aa = 1

                    LEFT JOIN (

                        SELECT
                            DATE_FORMAT(
                                created_at,
                                CONCAT(
                                    '%Y-%m-%d %H:',
                                    LPAD(FLOOR(MINUTE(created_at)/15)*15,2,'0'),
                                    ':00'
                                )
                            ) AS created_atf,

                            MAX(temperature) AS temperature,
                            MAX(relative_humidity) AS humidity

                        FROM weather
                        WHERE temperature > 0
                        AND relative_humidity > 0

                        GROUP BY DATE_FORMAT(
                            created_at,
                            CONCAT(
                                '%Y-%m-%d %H:',
                                LPAD(FLOOR(MINUTE(created_at)/15)*15,2,'0'),
                                ':00'
                            )
                        )

                    ) ww
                        ON b.created_at = ww.created_atf
                        
                        
                        LEFT JOIN (

                        SELECT
                            DATE_FORMAT(
                                created_at,
                                CONCAT(
                                    '%Y-%m-%d %H:',
                                    LPAD(FLOOR(MINUTE(created_at)/15)*15,2,'0'),
                                    ':00'
                                )
                            ) AS created_dv2,

                            MAX(temperature) AS temperature,
                            MAX(humidity) AS humidity

                           FROM device_statuses2_warehouse dsw 
                        WHERE temperature > 0
                        AND humidity > 0

                        GROUP BY DATE_FORMAT(
                            created_at,
                            CONCAT(
                                '%Y-%m-%d %H:',
                                LPAD(FLOOR(MINUTE(created_at)/15)*15,2,'0'),
                                ':00'
                            )
                        )

                    ) dv2
                        ON b.created_at = dv2.created_dv2
                        

                ) ff

                LEFT JOIN device_statuses_report bt
                    ON ff.device_idd = bt.device_id
                    AND ff.created_at = bt.created_at

                WHERE bt.id IS NULL

                ORDER BY ff.device_idd, ff.created_at

                

            ");

            $this->info('✅ Device status report generated successfully');

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}

//
//namespace App\Console\Commands;
//
//use Illuminate\Console\Command;
//use Illuminate\Support\Facades\DB;
//
//class GenerateDeviceStatusReport extends Command
//{
//    protected $signature = 'app:generate-device-status-report';
//    protected $description = 'Generate device statuses report (15 min aggregation)';
//
//    public function handle()
//    {
//        try {
//
//            DB::statement("
//            INSERT INTO device_statuses_report
//            (device_id,online,temperature,humidity,battery_percentage,temp_alarm,hum_alarm,recorded_at,created_at,updated_at)
//
//            SELECT
//                ff.device_idd,
//                ff.online,
//                ff.temperature,
//                ff.humidity,
//                ff.battery_percentage,
//                ff.temp_alarm,
//                ff.hum_alarm,
//                ff.recorded_at,
//                ff.created_at,
//                ff.updated_at
//
//            FROM (
//
//                WITH RECURSIVE time_series AS (
//                    SELECT TIMESTAMP('2026-05-01 00:00:00') AS dt
//                    UNION ALL
//                    SELECT dt + INTERVAL 15 MINUTE
//                    FROM time_series
//                    WHERE dt < (
//                        DATE(NOW())
//                        + INTERVAL HOUR(NOW()) HOUR
//                        + INTERVAL FLOOR(MINUTE(NOW())/15)*15 MINUTE
//                    )
//                ),
//
//                device_info AS (
//                    SELECT device_id
//                    FROM devices
//                ),
//
//                data_status AS (
//                    SELECT
//                        a.*,
//                        DATE_FORMAT(a.created_at, '%Y-%m-%d %H:%i:00') AS created_atf
//                    FROM device_statuses a
//                    WHERE a.created_at >= '2026-05-01'
//                    AND a.device_id IN (SELECT device_id FROM device_info)
//                ),
//
//                base AS (
//                    SELECT
//                        d.device_id AS device_idd,
//                        1 AS online,
//                        t.temperature,
//                        t.humidity,
//                        100 AS battery_percentage,
//                        'cancel' AS temp_alarm,
//                        'cancel' AS hum_alarm,
//                        '{\"active_time\":\"2026-04-30 15:54:26\",\"create_time\":\"2026-04-16 10:59:04\",\"update_time\":\"2026-04-30 23:06:04\"}' AS recorded_at,
//                        ts.dt AS created_at,
//                        ts.dt AS updated_at
//                    FROM time_series ts
//                    CROSS JOIN device_info d
//                    LEFT JOIN data_status t
//                        ON d.device_id = t.device_id
//                       AND ts.dt = t.created_atf
//                )
//
//                SELECT
//                    b.device_idd,
//                    b.online,
//                    b.battery_percentage,
//                    b.temp_alarm,
//                    b.hum_alarm,
//                    b.recorded_at,
//                    b.created_at,
//                    b.updated_at,
//
//                    CASE WHEN b.temperature IS NULL THEN bb.temperature ELSE b.temperature END AS temperature,
//                    CASE WHEN b.humidity IS NULL THEN bb.humidity ELSE b.humidity END AS humidity
//
//                FROM base b
//
//                LEFT JOIN (
//                    SELECT srr.*
//                    FROM device_statuses srr
//                    INNER JOIN (
//                        SELECT sr.device_id, MAX(sr.id) AS id_max
//                        FROM device_statuses sr
//                        INNER JOIN (
//                            SELECT
//                                a.device_id,
//                                MAX(a.created_at) AS created_at_max
//                            FROM device_statuses a
//                            WHERE a.created_at >= '2026-05-01'
//                            AND a.temperature > 0
//                            AND a.humidity > 0
//                            GROUP BY a.device_id
//                        ) tt
//                        ON sr.device_id = tt.device_id
//                        AND sr.created_at = tt.created_at_max
//                        GROUP BY sr.device_id
//                    ) ss
//                    ON srr.device_id = ss.device_id
//                    AND srr.id = ss.id_max
//                ) bb
//                ON b.device_idd = bb.device_id
//
//            ) ff
//
//            LEFT JOIN device_statuses_report bt
//                ON ff.device_idd = bt.device_id
//                AND ff.created_at = bt.created_at
//
//            WHERE bt.id IS NULL
//            ORDER BY ff.device_idd, ff.created_at
//            ");
//
//            $this->info('✅ Device status report generated successfully');
//
//        } catch (\Exception $e) {
//            $this->error('❌ Error: ' . $e->getMessage());
//        }
//    }
//}