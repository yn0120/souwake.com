<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RolesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->insert([
            ['name' => 'システム管理者', 'note' => 'システムのあらゆる操作が可能', 'created_at' => Carbon::now()],
            ['name' => '一般', 'note' => '一般的な操作が可能', 'created_at' => Carbon::now()],
        ]);
    }
}
