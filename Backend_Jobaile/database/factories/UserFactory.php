<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $imagePath = $this->faker->image(
            storage_path('app/public/users/'), // folder tujuan
            300, 300,
            'people', // kategori
            false      // return filename saja, bukan full path
        );
        
        $gender = $this->faker->randomElement(['Laki-laki', 'Perempuan']);

        return [
            'id_user' => Str::random(20),
            'fullname' => $this->faker->name($gender == 'Laki-laki' ? 'male' : 'female'),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'), // default password
            'phone' => $this->faker->numerify('08##########'),
            'gender' => $gender,
            'birthdate' => $this->faker->date('Y-m-d', '-20 years'),
            'ktp_card_path' => 'storage/users' . $imagePath, // path yang bisa diakses publik
            'role' => $this->faker->randomElement(['Worker', 'Recruiter']),
            'is_verified' => true,
            'email_verified_at' => now(),
            'remember_token' => Str::random(10),
        ];
    }
}
