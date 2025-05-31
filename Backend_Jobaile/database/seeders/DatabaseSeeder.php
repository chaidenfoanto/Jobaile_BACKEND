<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\WorkerModel;
use App\Models\RecruiterModel;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\User::factory()->count(10)->create();
        \App\Models\WorkerModel::factory()->count(5)->create();
        \App\Models\RecruiterModel::factory()->count(5)->create();
    }
}
