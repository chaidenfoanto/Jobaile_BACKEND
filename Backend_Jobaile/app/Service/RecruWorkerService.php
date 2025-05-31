<?php

namespace App\Service;

use App\Models\User;
use App\Enums\Gender;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\URL;
use App\Notifications\CustomVerifyEmail;
use App\Repository\UserRepository;

class RecruWorkerService
{
    protected $repo;

    public function __construct(UserRepository $repo)
    {
        $this->repo = $repo;
    }

    public function registerRecruiter(array $data): array
    {
        try {
            $user = $this->repo->create([
                'fullname' => $data['fullname'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'],
                'gender' => Gender::from($data['gender']),
                'birthdate' => $data['birthdate'],
                'role' => 'Recruiter',
            ]);

            $file = $data['ktp_card_path'];
            $filename = $user->id_user . '.' . $file->getClientOriginalExtension();
            $file->storeAs('users', $filename, 'public');

            $this->repo->saveKtpPath($user, $filename);

            event(new Registered($user));

            return [
                'status' => true,
                'message' => 'User created successfully. Please verify your email.',
                'id_user' => $user->id_user,
                'data' => $user,
                'ktp_card_path' => $filename,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function registerWorker(array $data): array
    {
        try {
            $user = $this->repo->create([
                'fullname' => $data['fullname'],
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'phone' => $data['phone'],
                'gender' => Gender::from($data['gender']),
                'birthdate' => $data['birthdate'],
                'role' => 'Worker',
            ]);

            /** @var UploadedFile $file */
            $file = $data['ktp_card_path'];
            $filename = $user->id_user . '.' . $file->getClientOriginalExtension();
            $file->storeAs('users', $filename, 'public');

            $this->repo->saveKtpPath($user, $filename);

            event(new Registered($user));

            return [
                'status' => true,
                'message' => 'User created successfully. Please verify your email.',
                'id_user' => $user->id_user,
                'data' => $user,
                'ktp_card_path' => $filename,
            ];
        } catch (\Exception $e) {
            return [
                'status' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ];
        }
    }

    public function attemptLogin(string $email, string $password): array
    {
        $user = $this->repo->findByEmail($email);
        if (!$user) {
            return ['status' => false, 'message' => 'User not found', 'code' => 404];
        }

        if (!Hash::check($password, $user->password)) {
            return ['status' => false, 'message' => 'Invalid credentials', 'code' => 401];
        }

        if (!$user->hasVerifiedEmail()) {
            return ['status' => false, 'message' => 'Email not verified', 'code' => 403];
        }

        Auth::login($user);
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'status' => true,
            'message' => 'Login success',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'data' => $user,
        ];
    }
}
