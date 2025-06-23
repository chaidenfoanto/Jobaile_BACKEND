<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\RatingReviewModel;
use App\Models\User;

class RatingReviewModelFactory extends Factory
{
    protected $model = RatingReviewModel::class;

    public function definition(): array
    {
        $userIds = User::pluck('id_user')->toArray();

        // Ambil reviewer dan reviewed yang tidak sama
        do {
            $reviewer = $this->faker->randomElement($userIds);
            $reviewed = $this->faker->randomElement($userIds);
        } while ($reviewer === $reviewed);

        return [
            'id_reviewer' => $reviewer,
            'id_reviewed' => $reviewed,
            'ulasan' => $this->faker->sentence(),
            'rating' => $this->faker->numberBetween(1, 5),
            'tanggal_rating' => now(),
            'role' => $this->faker->randomElement(['worker', 'recruiter']),
        ];
    }
}
