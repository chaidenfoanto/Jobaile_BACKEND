<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\DanaModel;
use App\Models\ContractsModel;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\DanaModel>
 */
class DanaModelFactory extends Factory
{
    protected $model = DanaModel::class;

    public function definition(): array
    {
        $contract = ContractsModel::inRandomOrder()->first();

        return [
            'id_contract'       => $contract?->id_contract,
            'merchant_trans_id' => $this->faker->uuid,
            'acquirement_id'    => $this->faker->uuid,
            'status'            => $this->faker->randomElement(['pending', 'success', 'failed']),
        ];
    }
}