<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\MatchmakingModel;
use App\Models\WorkerModel;
use App\Models\RecruiterModel;
use App\Models\Job_OfferModel;
use Illuminate\Support\Str;

class MatchmakingModelFactory extends Factory
{
    protected $model = MatchmakingModel::class;

    public function definition(): array
    {
        $workerIds = WorkerModel::pluck('id_worker')->toArray();
        $recruiterIds = RecruiterModel::pluck('id_recruiter')->toArray();
        $jobIds = Job_OfferModel::pluck('id_job')->toArray();

        return [
            'id_match'         => Str::random(20),
            'id_worker'        => $this->faker->randomElement($workerIds),
            'id_recruiter'     => $this->faker->randomElement($recruiterIds),
            'id_job'           => $this->faker->randomElement($jobIds),
            'status'           => $this->faker->randomElement(['pending', 'accepted', 'rejected']),
            'status_worker'    => $this->faker->randomElement(['pending', 'accepted', 'rejected']),
            'status_recruiter' => $this->faker->randomElement(['pending', 'accepted', 'rejected']),
            'matched_at'       => $this->faker->dateTimeBetween('-1 month', 'now'),
        ];
    }
}