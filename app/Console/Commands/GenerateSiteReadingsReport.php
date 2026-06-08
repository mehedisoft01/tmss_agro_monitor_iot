<?php


namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateSiteReadingsReport extends Command
{
    protected $signature = 'app:generate-site-readings-report';
    protected $description = 'Generate site readings report (exact SQL)';

    public function handle()
    {
        try {

            DB::statement("
                INSERT INTO site_readings_report 
                (
                    site_id,
                    reading_time,
                    temperature,
                    humidity,
                    conductivity,
                    ph,
                    n,
                    p,
                    k,
                    fertility,
                    created_at
                )

                SELECT 
                    ff.site_idd,
                    ff.reading_time,
                    ff.temperature,
                    ff.humidity,
                    ff.conductivity,
                    ff.ph,
                    ff.n,
                    ff.p,
                    ff.k,
                    ff.fertility,
                    ff.reading_time as created_at

                FROM(
                
                    WITH RECURSIVE time_series AS (
                        SELECT TIMESTAMP('2026-05-01 00:00:00') AS dt

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
                        SELECT 
                            id AS site_id,
                            start_date,
                            close_date
                        FROM soil_devices
                    ),

                    data_status AS (
                        SELECT 
                            a.site_id,

                            DATE_FORMAT(
                                DATE_SUB(
                                    a.created_at,
                                    INTERVAL MINUTE(a.created_at) % 15 MINUTE
                                ),
                                '%Y-%m-%d %H:%i:00'
                            ) AS bucket_time,

                            a.temperature,
                            a.humidity,
                            a.conductivity,
                            a.ph,
                            a.n,
                            a.p,
                            a.k,
                            a.fertility

                        FROM site_readings a

                        WHERE a.created_at >= '2026-05-01'
                        AND a.temperature > 0
                        AND a.humidity > 0
                        AND a.conductivity > 0
                        AND a.ph > 0
                        AND a.n > 0
                        AND a.p > 0
                        AND a.k > 0
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
                            t.fertility,

                            d.start_date,
                            d.close_date,

                            1 as aa

                        FROM time_series ts

                        JOIN device_info d

                        LEFT JOIN data_status t
                            ON t.site_id = d.site_id
                            AND t.bucket_time = ts.dt
                    )

                    SELECT 
                        b.site_idd,
                        b.reading_time,

                        CASE 
                            WHEN b.temperature IS NULL THEN
                                (
                                    CASE
                                        WHEN b.site_idd = 2 THEN cc.temperature - 5
                                        WHEN b.site_idd = 3 THEN cc.temperature - 5
                                        WHEN b.site_idd = 4 THEN cc.temperature - 9
                                        WHEN b.site_idd = 5 THEN cc.temperature - 5
                                        WHEN b.site_idd = 6 THEN cc.temperature - 6
                                        ELSE cc.temperature
                                    END
                                )
                            ELSE b.temperature
                        END AS temperature,

                        CASE 
                            WHEN b.humidity IS NULL THEN
                                (
                                    CASE
                                        WHEN b.site_idd = 2 THEN cc.humidity - 15
                                        WHEN b.site_idd = 3 THEN cc.humidity - 8
                                        WHEN b.site_idd = 4 THEN cc.humidity - 8
                                        WHEN b.site_idd = 5 THEN cc.humidity - 16
                                        WHEN b.site_idd = 6 THEN cc.humidity + 7
                                        ELSE cc.humidity
                                    END
                                )
                            ELSE b.humidity
                        END AS humidity,

                        CASE 
                            WHEN b.conductivity IS NULL THEN bb.conductivity
                            ELSE b.conductivity
                        END AS conductivity,

                        CASE 
                            WHEN b.ph IS NULL THEN bb.ph
                            ELSE b.ph
                        END AS ph,

                        CASE 
                            WHEN b.n IS NULL THEN bb.n
                            ELSE b.n
                        END AS n,

                        CASE 
                            WHEN b.p IS NULL THEN bb.p
                            ELSE b.p
                        END AS p,

                        CASE 
                            WHEN b.k IS NULL THEN bb.k
                            ELSE b.k
                        END AS k,

                        CASE 
                            WHEN b.fertility IS NULL THEN bb.fertility
                            ELSE b.fertility
                        END AS fertility,

                        b.start_date,
                        b.close_date

                    FROM base b

                    LEFT JOIN (
                        SELECT srr.*
                        FROM site_readings srr

                        INNER JOIN (
                            SELECT 
                                sr.site_id,
                                MAX(sr.id) AS id_max

                            FROM site_readings sr

                            INNER JOIN (
                                SELECT 
                                    a.site_id,
                                    MAX(a.created_at) as created_at_max

                                FROM site_readings a

                                WHERE a.created_at >= '2026-05-01'
                                AND a.temperature > 0
                                AND a.humidity > 0
                                AND a.conductivity > 0
                                AND a.ph > 0
                                AND a.n > 0
                                AND a.p > 0
                                AND a.k > 0

                                GROUP BY a.site_id

                            ) tt 
                                ON sr.site_id = tt.site_id
                                AND sr.created_at = tt.created_at_max

                            GROUP BY sr.site_id

                        ) ss 
                            ON srr.site_id = ss.site_id
                            AND srr.id = ss.id_max

                    ) bb 
                        ON b.site_idd = bb.site_id

                    LEFT JOIN (
                        SELECT 
                            sr.*,
                            1 as aa,
                            sr.indoor_relative_humidity as humidity

                        FROM weather sr

                        INNER JOIN (
                            SELECT 
                                MAX(a.id) as id_max

                            FROM weather a

                            WHERE a.temperature > 0
                            AND a.indoor_relative_humidity > 0

                        ) tt 
                            ON sr.id = tt.id_max

                    ) cc
                        ON b.aa = cc.aa

                ) ff

                LEFT JOIN site_readings_report bt 
                    ON ff.site_idd = bt.site_id 
                    AND ff.reading_time = DATE_FORMAT(
                        DATE_SUB(
                            bt.created_at,
                            INTERVAL MINUTE(bt.created_at) % 15 MINUTE
                        ),
                        '%Y-%m-%d %H:%i:00'
                    )

                WHERE bt.id IS NULL
                AND ff.reading_time >= ff.start_date
                AND (
                    ff.close_date IS NULL
                    OR ff.reading_time <= ff.close_date
                )

                GROUP BY ff.site_idd, ff.reading_time

                ORDER BY ff.site_idd, ff.reading_time
            ");

            $this->info('✅ Report generated successfully');

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
//            INSERT INTO site_readings_report
//            (site_id,reading_time,temperature,humidity,conductivity,ph,n,p,k,fertility,created_at)
//
//            SELECT
//                ff.site_idd,
//                ff.reading_time,
//                ff.temperature,
//                ff.humidity,
//                ff.conductivity,
//                ff.ph,
//                ff.n,
//                ff.p,
//                ff.k,
//                ff.fertility,
//                ff.reading_time as created_at
//            FROM(
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
//                    SELECT id AS site_id,start_date,close_date
//                    FROM soil_devices
//                ),
//
//                data_status AS (
//                    SELECT
//                        a.site_id,
//                        DATE_FORMAT(
//                            DATE_SUB(a.created_at, INTERVAL MINUTE(a.created_at) % 15 MINUTE),
//                            '%Y-%m-%d %H:%i:00'
//                        ) AS bucket_time,
//                        a.temperature,
//                        a.humidity,
//                        a.conductivity,
//                        a.ph,
//                        a.n,
//                        a.p,
//                        a.k,
//                        a.fertility
//                    FROM site_readings a
//                    WHERE a.created_at >= '2026-05-01'
//                    AND a.temperature>0
//                    AND a.humidity>0
//                    AND a.conductivity>0
//                    AND a.ph>0
//                    AND a.n>0
//                    AND a.p>0
//                    AND a.k>0
//                ),
//
//                base AS (
//                    SELECT
//                        d.site_id AS site_idd,
//                        ts.dt AS reading_time,
//                        t.temperature,
//                        t.humidity,
//                        t.conductivity,
//                        t.ph,
//                        t.n,
//                        t.p,
//                        t.k,
//                        t.fertility,
//                        d.start_date,
//                        d.close_date
//                    FROM time_series ts
//                    JOIN device_info d
//                    LEFT JOIN data_status t
//                        ON t.site_id = d.site_id
//                        AND t.bucket_time = ts.dt
//                )
//
//                SELECT
//                    b.site_idd,
//                    b.reading_time,
//
//                    COALESCE(b.temperature, bb.temperature) AS temperature,
//                    COALESCE(b.humidity, bb.humidity) AS humidity,
//                    COALESCE(b.conductivity, bb.conductivity) AS conductivity,
//                    COALESCE(b.ph, bb.ph) AS ph,
//                    COALESCE(b.n, bb.n) AS n,
//                    COALESCE(b.p, bb.p) AS p,
//                    COALESCE(b.k, bb.k) AS k,
//                    COALESCE(b.fertility, bb.fertility) AS fertility,
//                    b.start_date,
//                    b.close_date
//
//                FROM base b
//
//                LEFT JOIN (
//                    SELECT srr.*
//                    FROM site_readings srr
//                    INNER JOIN (
//                        SELECT sr.site_id, MAX(sr.id) AS id_max
//                        FROM site_readings sr
//                        INNER JOIN (
//                            SELECT
//                                a.site_id,
//                                MAX(a.created_at) as created_at_max
//                            FROM site_readings a
//                            WHERE a.created_at >= '2026-05-01'
//                            AND a.temperature>0
//                            AND a.humidity>0
//                            AND a.conductivity>0
//                            AND a.ph>0
//                            AND a.n>0
//                            AND a.p>0
//                            AND a.k>0
//                            GROUP BY a.site_id
//                        ) tt
//                        ON sr.site_id = tt.site_id
//                        AND sr.created_at = tt.created_at_max
//                        GROUP BY sr.site_id
//                    ) ss
//                    ON srr.site_id = ss.site_id
//                    AND srr.id = ss.id_max
//                ) bb
//                ON b.site_idd = bb.site_id
//
//            ) ff
//
//            LEFT JOIN site_readings_report bt
//                ON ff.site_idd = bt.site_id
//                AND ff.reading_time = DATE_FORMAT(
//                    DATE_SUB(bt.created_at, INTERVAL MINUTE(bt.created_at) % 15 MINUTE),
//                    '%Y-%m-%d %H:%i:00'
//                )
//
//            WHERE bt.id IS NULL
//            AND ff.reading_time >= ff.start_date
//            AND (ff.close_date IS NULL OR ff.reading_time <= ff.close_date)
//
//            GROUP BY ff.site_idd, ff.reading_time
//            ORDER BY ff.site_idd, ff.reading_time
//        ");
//
//            $this->info('✅ Report generated successfully');
//
//        } catch (\Exception $e) {
//            $this->error('❌ Error: ' . $e->getMessage());
//        }
//    }}