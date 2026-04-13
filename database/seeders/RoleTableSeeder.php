<?php

namespace Database\Seeders;

use App\Models\RBAC\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RoleTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Role::truncate();

        $roles = [
            [
                'display_name' => 'SuperUser',
                'name' => 'superadmin',
            ],
        ];

        foreach ($roles as $roleData) {

            $role = new Role();
            $role->display_name = $roleData['display_name'];
            $role->name = $roleData['name'];
            $role->save();
        }
    }
}
