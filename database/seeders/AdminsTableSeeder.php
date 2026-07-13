<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('admins')->insert([
            'email' => 'test@co.jp', // メール
            'role_id' => 1, // 権限
            'password' => Hash::make('!Pass0120'), // ハッシュ化パスワード
            'created_at' => Carbon::now(), // 登録日時
        ]);
    }
}
