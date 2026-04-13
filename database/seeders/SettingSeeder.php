<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Setting::truncate();

        $users = [
            [
                'key'=>'app_name',
                'type'=>'text',
                'setting_type'=>'general_setting',
                'value'=>'BatterGo',
                'is_visible'=>1,
            ],
            [
                'key'=>'app_logo',
                'type'=>'file',
                'setting_type'=>'general_setting',
                'value'=>null,
                'is_visible'=>1,
            ],
            [
                'key'=>'notify_per_minuit',
                'type'=>'number',
                'setting_type'=>'notification_setting',
                'value'=>1,
                'is_visible'=>1,
            ],
            [
                'key' => 'company_name',
                'type' => 'text',
                'setting_type' => 'company_information',
                'value' => 'BatterGo (SHANGHAI) Tech. Co., LTD.',
                'is_visible' => 1,
            ],
            [
                'key' => 'company_address',
                'type' => 'text',
                'setting_type' => 'company_information',
                'value' => 'Dhaka Office: #100, #30, 3B, Gulshan-2, Dhaka-1212, Bangladesh.',
                'is_visible' => 1,
            ],
            [
                'key' => 'company_city',
                'type' => 'text',
                'setting_type' => 'company_information',
                'value' => 'Dhaka-1212',
                'is_visible' => 1,
            ],
            [
                'key'=>'company_phone',
                'type'=>'text',
                'setting_type'=>'company_information',
                'value' => '01324-246691, 01324-246692, 01324-246693',
                'is_visible'=>1,
            ],
            [
                'key' => 'company_email',
                'type' => 'text',
                'setting_type' => 'company_information',
                'value' => 'gengzz@163.com',
                'is_visible' => 1,
            ],
            [
                'key' => 'company_website',
                'type' => 'text',
                'setting_type' => 'company_information',
                'value' => 'www.battergo.com',
                'is_visible' => 1,
            ],
            [
                'key' => 'return_refund',
                'type' => 'number',
                'setting_type' => 'return_refund',
                'value' => 7,
                'is_visible' => 1,
            ],
            [
                'key' => 'dealer_role',
                'type' => 'select',
                'setting_type' => 'dealer_setting',
                'value' => null,
                'is_visible' => 1,
            ],
            [
                'key' => 'salesman_role',
                'type' => 'select',
                'setting_type' => 'role_setting',
                'value' => null,
                'is_visible' => 1,
            ],
            [
                'key' => 'division_manager_role',
                'type' => 'select',
                'setting_type' => 'role_setting',
                'value' => null,
                'is_visible' => 1,
            ],
            [
                'key' => 'district_manager_role',
                'type' => 'select',
                'setting_type' => 'role_setting',
                'value' => null,
                'is_visible' => 1,
            ],
            [
                'key' => 'financial_manager_role',
                'type' => 'select',
                'setting_type' => 'role_setting',
                'value' => null,
                'is_visible' => 1,
            ],
            [
                'key' => 'batter_go_admin_role',
                'type' => 'select',
                'setting_type' => 'role_setting',
                'value' => null,
                'is_visible' => 1,
            ],
            [
                'key' => 'app_download_link',
                'type' => 'text',
                'setting_type' => 'app_download_link',
                'value' => 'https://apkpure.com/bn/google-play-store-2025/com.android.vending/download',
                'is_visible' => 1,
            ],

        ];

        Setting::insert($users);
    }
}
