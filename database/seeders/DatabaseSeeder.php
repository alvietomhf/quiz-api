<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
            'name' => 'siswa',
            'email' => 'siswa@gmail.com',
            'role' => 'siswa',
            'email_verified_at' => Carbon::now(),
            'remember_token' => Str::Random(50),
            'password' => Hash::make('Password`'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('users')->insert([
            'name' => 'guru',
            'email' => 'guru@gmail.com',
            'role' => 'guru',
            'email_verified_at' => Carbon::now(),
            'remember_token' => Str::Random(50),
            'password' => Hash::make('Password`'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
        DB::table('users')->insert([
            'name' => 'admin',
            'email' => 'admin@gmail.com',
            'role' => 'admin',
            'email_verified_at' => Carbon::now(),
            'remember_token' => Str::Random(50),
            'password' => Hash::make('Password`'),
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now()
        ]);
    }
}
