<?php

namespace Database\Factories;

use App\Models\RecruiterModel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use App\Models\User;

class RecruiterModelFactory extends Factory
{
    protected $model = RecruiterModel::class;

    public function definition(): array
    {
        $imagePath = $this->faker->image(
            storage_path('app/public/profile/'), // folder tujuan
            300, 300,
            'people', // kategori
            false      // return filename saja, bukan full path
        );

        return [
            'id_recruiter' => Str::random(20),
            'id_user' => User::factory(),
            'house_type' => $this->faker->randomElement(['Rumah', 'Apartemen', 'Kontrakan']),
            'family_size' => $this->faker->numberBetween(2, 6),
            'location_address' => $this->faker->address(),
            'desc' => $this->faker->sentence(),
            'profile_picture' => 'storage/profile/' . $imagePath, // path yang bisa diakses publik
        ];
    }
}
