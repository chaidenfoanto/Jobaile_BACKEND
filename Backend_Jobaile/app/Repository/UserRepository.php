<?php

namespace App\Repository;

use App\Models\User;

class UserRepository
{
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function saveKtpPath(User $user, string $filename)
    {
        $user->ktp_card_path = $filename;
        $user->save();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }
}
