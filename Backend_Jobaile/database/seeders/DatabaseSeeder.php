<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\WorkerModel;
use App\Models\RatingReviewModel;
use App\Models\RecruiterModel;
use App\Models\Job_OfferModel;
use App\Models\MatchmakingModel;
use App\Models\ChatModel;
use App\Models\ContractsModel;
use App\Models\DanaModel;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        \App\Models\User::factory()->count(10)->create();
        \App\Models\WorkerModel::factory()->count(5)->create();
        \App\Models\RecruiterModel::factory()->count(5)->create();

        // Tambah dummy rating-review (misal 10)
        RatingReviewModel::factory()->count(10)->create();

        // Tambah dummy job offer
        Job_OfferModel::factory()->count(10)->create();

        // Tambah dummy matchmaking
        MatchmakingModel::factory()->count(15)->create();

        // Tambah dummy contract
        ContractsModel::factory()->count(10)->create();

        // Tambah dummy chat
        ChatModel::factory()->count(30)->create();

        DanaModel::factory()->count(10)->create();
    }
}
