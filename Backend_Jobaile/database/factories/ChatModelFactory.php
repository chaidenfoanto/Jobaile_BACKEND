<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\ChatModel;
use App\Models\User;

class ChatModelFactory extends Factory
{
    protected $model = ChatModel::class;

    public function definition(): array
    {
        $userIds = User::pluck('id_user')->toArray();

        // Hindari pengirim dan penerima sama
        $sender = $this->faker->randomElement($userIds);
        $receiver = $this->faker->randomElement(array_filter($userIds, fn($id) => $id !== $sender));

        return [
            'id_sender'   => $sender,
            'id_receiver' => $receiver,
            'message'     => $this->faker->sentence(),
            'send_at'     => $this->faker->dateTimeBetween('-5 days', 'now'),
        ];
    }
}