<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TambahUserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'ujang@kampus.ac.id'],
            [
                'name' => 'Andi',
                'password' => Hash::make('ujang010109'),
            ]
        );
    }
}