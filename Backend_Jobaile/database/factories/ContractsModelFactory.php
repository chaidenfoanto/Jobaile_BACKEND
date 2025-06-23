<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ContractsModel;
use App\Models\WorkerModel;
use App\Models\RecruiterModel;
use App\Models\Job_OfferModel;

class ContractsModelFactory extends Factory
{
    protected $model = ContractsModel::class;

    public function definition(): array
    {
        $workerIds = WorkerModel::pluck('id_worker')->toArray();
        $recruiterIds = RecruiterModel::pluck('id_recruiter')->toArray();
        $jobIds = Job_OfferModel::pluck('id_job')->toArray();

        $start = $this->faker->dateTimeBetween('-1 month', '+1 week');
        $end = (clone $start)->modify('+1 week');

        return [
            'id_worker'     => $this->faker->randomElement($workerIds),
            'id_recruiter'  => $this->faker->randomElement($recruiterIds),
            'id_job'        => $this->faker->randomElement($jobIds),
            'start_date'    => $start->format('Y-m-d'),
            'end_date'      => $end->format('Y-m-d'),
            'terms'         => $this->faker->sentence(10),
            'status_pay'    => $this->faker->randomElement(['pending', 'success']),
            'sign_at'       => $this->faker->dateTimeBetween($start, $end),
        ];
    }
}