<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Job_OfferModel;
use App\Models\RecruiterModel;

class Job_OfferModelFactory extends Factory
{
    protected $model = Job_OfferModel::class;

    public function definition(): array
    {
        $recruiterIds = RecruiterModel::pluck('id_recruiter')->toArray();

        return [
            'id_recruiter' => $this->faker->randomElement($recruiterIds),
            'job_title'    => $this->faker->jobTitle(),
            'desc'         => $this->faker->paragraph(),
            'status'       => $this->faker->randomElement(['open', 'closed']),
        ];
    }
}