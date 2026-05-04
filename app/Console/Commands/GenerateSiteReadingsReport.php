<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateSiteReadingsReport extends Command
{
    protected $signature = 'app:generate-site-readings-report';
    protected $description = 'Generate site readings report (updated SQL)';

    public function handle()
    {
        try {

            DB::statement("
            -- site_readings_report
            
            insert site_readings_report (site_id,reading_time,temperature,humidity,conductivity,ph,n,p,k,fertility,created_at)
            select ff.site_idd,ff.reading_time,ff.temperature,ff.humidity,ff.conductivity,ff.ph,ff.n,ff.p,ff.k,ff.fertility,ff.reading_time as created_at
            from(
            
            WITH RECURSIVE time_series AS (
                SELECT TIMESTAMP('2026-05-03 00:00:00') AS dt
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
                SELECT id AS site_id
                FROM soil_devices
            ),
            
            data_status AS (
                SELECT 
                    a.site_id,
            
                    case 
                        when cast(a.reading_time as date)=cast(a.created_at as date) 
                        then DATE_FORMAT(
                            DATE_SUB(a.reading_time, INTERVAL MINUTE(a.reading_time) % 15 MINUTE),
                            '%Y-%m-%d %H:%i:00'
                        ) 
                        else 
                            case 
                                when site_id=2 then DATE_FORMAT(CONCAT(DATE(created_at),' ',
                                DATE_FORMAT(
                                    DATE_SUB(DATE_SUB(reading_time, INTERVAL 6 HOUR),INTERVAL 30 MINUTE),
                                    '%H:%i:%s'
                                )),'%Y-%m-%d %H:%i:00')
            
                                when site_id=6 then DATE_FORMAT(CONCAT(DATE(created_at),' ',
                                DATE_FORMAT(
                                    DATE_SUB(DATE_ADD(reading_time, INTERVAL 11 HOUR),INTERVAL 00 MINUTE),
                                    '%H:%i:%s'
                                )),'%Y-%m-%d %H:%i:00')
                            end 
                    end AS bucket_time,
            
                    a.temperature,
                    a.humidity,
                    a.conductivity,
                    a.ph,
                    a.n,
                    a.p,
                    a.k,
                    a.fertility
            
                FROM site_readings a
                WHERE a.created_at >= '2026-05-03'
            ),
            
            base AS (
                SELECT 
                    d.site_id AS site_idd,
                    ts.dt AS reading_time,
                    t.temperature,
                    t.humidity,
                    t.conductivity,
                    t.ph,
                    t.n,
                    t.p,
                    t.k,
                    t.fertility
                FROM time_series ts
                JOIN device_info d
                LEFT JOIN data_status t
                    ON t.site_id = d.site_id
                   AND t.bucket_time = ts.dt
            )
            
            SELECT 
                site_idd,
                reading_time,
            
                MAX(CASE WHEN temperature IS NOT NULL THEN temperature END)
                    OVER (PARTITION BY site_idd ORDER BY reading_time) AS temperature,
            
                MAX(CASE WHEN humidity IS NOT NULL THEN humidity END)
                    OVER (PARTITION BY site_idd ORDER BY reading_time) AS humidity,
            
                MAX(CASE WHEN conductivity IS NOT NULL THEN conductivity END)
                    OVER (PARTITION BY site_idd ORDER BY reading_time) AS conductivity,
            
                MAX(CASE WHEN ph IS NOT NULL THEN ph END)
                    OVER (PARTITION BY site_idd ORDER BY reading_time) AS ph,
            
                MAX(CASE WHEN n IS NOT NULL THEN n END)
                    OVER (PARTITION BY site_idd ORDER BY reading_time) AS n,
            
                MAX(CASE WHEN p IS NOT NULL THEN p END)
                    OVER (PARTITION BY site_idd ORDER BY reading_time) AS p,
            
                MAX(CASE WHEN k IS NOT NULL THEN k END)
                    OVER (PARTITION BY site_idd ORDER BY reading_time) AS k,
            
                MAX(CASE WHEN fertility IS NOT NULL THEN fertility END)
                    OVER (PARTITION BY site_idd ORDER BY reading_time) AS fertility
            
            FROM base
            
            ) ff
            LEFT JOIN site_readings_report bt 
                ON ff.site_idd = bt.site_id 
               AND ff.reading_time = DATE_FORMAT(
                    DATE_SUB(bt.created_at, INTERVAL MINUTE(bt.created_at) % 15 MINUTE),
                    '%Y-%m-%d %H:%i:00'
               )
            WHERE bt.id IS NULL
            order by ff.site_idd, ff.reading_time
                        ");

            $this->info('✅ Updated report generated successfully');

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
//class GenerateSiteReadingsReport extends Command
//{
//    protected $signature = 'app:generate-site-readings-report';
//    protected $description = 'Generate site readings report (exact SQL)';
//
//    public function handle()
//    {
//        try {
//
//            DB::statement("
//        -- site_readings_report
//
//        insert site_readings_report (site_id,reading_time,temperature,humidity,conductivity,ph,n,p,k,fertility,created_at)
//        select ff.site_idd,ff.reading_time,ff.temperature,ff.humidity,ff.conductivity,ff.ph,ff.n,ff.p,ff.k,ff.fertility,ff.created_at
//        from(
//        WITH RECURSIVE time_series AS (
//            SELECT TIMESTAMP('2026-05-01 00:00:00') AS dt
//            UNION ALL
//            SELECT dt + INTERVAL 15 MINUTE
//            FROM time_series
//            WHERE dt < (
//                DATE(NOW())
//                + INTERVAL HOUR(NOW()) HOUR
//                + INTERVAL FLOOR(MINUTE(NOW())/15)*15 MINUTE
//            )
//        ),
//
//        device_info AS (
//            SELECT id as site_id
//            FROM soil_devices
//        ),
//
//        data_status AS (
//            SELECT
//                a.*,
//                case
//                    when cast(a.reading_time as date)=cast(a.created_at as date)
//                    then DATE_FORMAT(
//                        DATE_SUB(a.reading_time, INTERVAL MINUTE(a.reading_time) % 15 MINUTE),
//                        '%Y-%m-%d %H:%i:00'
//                    )
//                    else
//                        case
//                            when site_id=2 then DATE_FORMAT(CONCAT(DATE(created_at),' ',
//                            DATE_FORMAT(
//                                DATE_SUB(DATE_SUB(reading_time, INTERVAL 6 HOUR),INTERVAL 30 MINUTE),
//                                '%H:%i:%s'
//                            )),'%Y-%m-%d %H:%i:00')
//
//                            when site_id=6 then DATE_FORMAT(CONCAT(DATE(created_at),' ',
//                            DATE_FORMAT(
//                                DATE_SUB(DATE_ADD(reading_time, INTERVAL 11 HOUR),INTERVAL 00 MINUTE),
//                                '%H:%i:%s'
//                            )),'%Y-%m-%d %H:%i:00')
//                        end
//                end AS created_atf
//            FROM site_readings a
//            WHERE a.created_at >= '2026-05-01'
//              AND a.site_id IN (SELECT site_id FROM device_info)
//              and a.temperature>0 and a.humidity>0 and a.conductivity>0 and a.ph>0 and a.n>0 and a.p>0 and a.k>0
//        ),
//
//        base AS (
//            SELECT
//                d.site_id AS site_idd,
//                ts.dt AS reading_time,
//                t.temperature,t.humidity,t.conductivity,t.ph,t.n,t.p,t.k,t.fertility,
//                ts.dt AS created_at,
//                ts.dt AS updated_at
//            FROM time_series ts
//            CROSS JOIN device_info d
//            LEFT JOIN data_status t
//                ON d.site_id = t.site_id
//               AND DATE_FORMAT(ts.dt, '%Y-%m-%d %H:%i:00') = t.created_atf
//            ORDER BY d.site_id, ts.dt
//        )
//
//        SELECT
//            site_idd,
//            reading_time,
//            created_at,
//            updated_at,
//
//            @temp := IF(temperature IS NULL, @temp, temperature) AS temperature,
//            @hum := IF(humidity IS NULL, @hum, humidity) AS humidity,
//            @conductivity := IF(conductivity IS NULL, @conductivity, conductivity) AS conductivity,
//            @ph := IF(ph IS NULL, @ph, ph) AS ph,
//            @n := IF(n IS NULL, @n, n) AS n,
//            @p := IF(p IS NULL, @p, p) AS p,
//            @k := IF(k IS NULL, @k, k) AS k,
//            @fertility := IF(fertility IS NULL, @fertility, fertility) AS fertility
//
//        FROM base,
//        (SELECT @temp := NULL, @hum := NULL ,@conductivity := NULL ,@ph := NULL ,@n := NULL ,@p := NULL ,@k := NULL ,@fertility := NULL) vars
//
//        ) ff
//        LEFT JOIN site_readings_report bt ON ff.site_idd = bt.site_id
//        AND ff.created_at = bt.created_at
//        WHERE bt.id IS NULL
//        order by ff.site_idd,ff.created_at
//                    ");
//
//            $this->info('✅ Report generated successfully');
//
//        } catch (\Exception $e) {
//            $this->error('❌ Error: ' . $e->getMessage());
//        }
//    }
//}