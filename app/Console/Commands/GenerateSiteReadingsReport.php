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
            (site_id,reading_time,temperature,humidity,conductivity,ph,n,p,k,fertility,created_at)

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
                    )
                ),

                device_info AS (
                    SELECT id AS site_id
                    FROM soil_devices
                ),

                data_status AS (
                    SELECT 
                        a.site_id,

                        CASE 
                            WHEN CAST(a.reading_time AS DATE)=CAST(a.created_at AS DATE) 
                            THEN DATE_FORMAT(
                                DATE_SUB(a.reading_time, INTERVAL MINUTE(a.reading_time) % 15 MINUTE),
                                '%Y-%m-%d %H:%i:00'
                            ) 
                            ELSE 
                                CASE 
                                    WHEN site_id=2 THEN DATE_FORMAT(CONCAT(DATE(created_at),' ',
                                        DATE_FORMAT(
                                            DATE_SUB(DATE_SUB(reading_time, INTERVAL 6 HOUR),INTERVAL 30 MINUTE),
                                            '%H:%i:%s'
                                        )
                                    ),'%Y-%m-%d %H:%i:00')

                                    WHEN site_id=6 THEN DATE_FORMAT(CONCAT(DATE(created_at),' ',
                                        DATE_FORMAT(
                                            DATE_SUB(DATE_ADD(reading_time, INTERVAL 11 HOUR),INTERVAL 0 MINUTE),
                                            '%H:%i:%s'
                                        )
                                    ),'%Y-%m-%d %H:%i:00')
                                END 
                        END AS bucket_time,

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
                    AND a.temperature>0 
                    AND a.humidity>0 
                    AND a.conductivity>0 
                    AND a.ph>0 
                    AND a.n>0 
                    AND a.p>0 
                    AND a.k>0
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
                    b.site_idd,
                    b.reading_time,

                    CASE WHEN b.temperature IS NULL THEN bb.temperature ELSE b.temperature END AS temperature,
                    CASE WHEN b.humidity IS NULL THEN bb.humidity ELSE b.humidity END AS humidity,
                    CASE WHEN b.conductivity IS NULL THEN bb.conductivity ELSE b.conductivity END AS conductivity,
                    CASE WHEN b.ph IS NULL THEN bb.ph ELSE b.ph END AS ph,
                    CASE WHEN b.n IS NULL THEN bb.n ELSE b.n END AS n,
                    CASE WHEN b.p IS NULL THEN bb.p ELSE b.p END AS p,
                    CASE WHEN b.k IS NULL THEN bb.k ELSE b.k END AS k,
                    CASE WHEN b.fertility IS NULL THEN bb.fertility ELSE b.fertility END AS fertility

                FROM base b

                LEFT JOIN (
                    SELECT sr.*
                    FROM site_readings sr
                    INNER JOIN (
                        SELECT 
                            a.site_id,
                            MAX(a.created_at) as created_at_max
                        FROM site_readings a
                        WHERE a.created_at >= '2026-05-01'
                        AND a.temperature>0 
                        AND a.humidity>0 
                        AND a.conductivity>0 
                        AND a.ph>0 
                        AND a.n>0 
                        AND a.p>0 
                        AND a.k>0
                        GROUP BY a.site_id
                    ) tt 
                    ON sr.site_id=tt.site_id 
                    AND sr.created_at=tt.created_at_max
                ) bb 
                ON b.site_idd=bb.site_id

            ) ff

            LEFT JOIN site_readings_report bt 
                ON ff.site_idd = bt.site_id 
                AND ff.reading_time = DATE_FORMAT(
                    DATE_SUB(bt.created_at, INTERVAL MINUTE(bt.created_at) % 15 MINUTE),
                    '%Y-%m-%d %H:%i:00'
                )

            WHERE bt.id IS NULL
            ORDER BY ff.site_idd, ff.reading_time
        ");

            $this->info('✅ Report generated successfully');

        } catch (\Exception $e) {
            $this->error('❌ Error: ' . $e->getMessage());
        }
    }
}