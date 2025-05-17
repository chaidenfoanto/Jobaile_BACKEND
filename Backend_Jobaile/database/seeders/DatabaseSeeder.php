<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'fullname' => 'hehecjnfjcfnjf',
            'email' => 'test@example.com',
            'password' => bcrypt('hellobello'), // password
            'phone' => '08123456789',
            'gender' => 'Laki-laki',
            'birthdate' => '2000-01-01',
            'ktp_card_path' => 'path/to/ktp_card.jpg', // Simulasi path KTP
        ]);
    }
}
