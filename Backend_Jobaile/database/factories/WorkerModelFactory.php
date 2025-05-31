<?php

namespace Database\Factories;

use App\Models\WorkerModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

class WorkerModelFactory extends Factory
{
    protected $model = WorkerModel::class;

    public function definition(): array
    {
        $imagePath = $this->faker->image(
            storage_path('app/public/profile/'), // folder tujuan
            300, 300,
            'people', // kategori
            false      // return filename saja, bukan full path
        );

        return [
            'id_worker' => Str::random(20),
            'id_user' => User::factory(), // otomatis buat User baru
            'bio' => $this->faker->paragraph(),
            'skill' => implode(', ', $this->faker->words(3)),
            'experience_years' => $this->faker->numberBetween(0, 10),
            'location' => $this->faker->city(),
            'expected_salary' => $this->faker->numberBetween(1000000, 5000000),
            'availability' => $this->faker->randomElement(['penuh_waktu', 'paruh_waktu', 'mingguan', 'bulanan']),
            'profile_picture' => 'storage/profile/' . $imagePath, // path yang bisa diakses publik
        ];
    }
}
