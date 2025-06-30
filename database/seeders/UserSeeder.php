<?php
// database/seeders/UserSeeder.php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run()
    {
        // Create Guru
        $guru = User::create([
            'username' => 'guru001',
            'password' => Hash::make('password123'),
            'nama' => 'Ibu Guru',
            'role' => 'guru',
        ]);

        // Create Siswa
        $siswa1 = User::create([
            'username' => 'siswa001',
            'password' => Hash::make('password123'),
            'nama' => 'Siswa1',
            'role' => 'siswa',
        ]);



        // Create Orangtua
        $orangtua1 = User::create([
            'username' => 'orangtua001',
            'password' => Hash::make('password123'),
            'nama' => 'Ortu1',
            'role' => 'orangtua',
        ]);

        // Create profil siswa
        // $siswa1->profilSiswa()->create([
        //     'badges' => [],
        //     'total_badges' => 0,
        // ]);


        $this->command->info('Sample users created successfully!');
        $this->command->info('Guru: guru001 / password123');
        $this->command->info('Siswa: siswa001 / password123');
        $this->command->info('Orangtua: orangtua001 / password123');
    }
}
