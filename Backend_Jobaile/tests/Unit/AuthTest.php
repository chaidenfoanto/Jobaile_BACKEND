<?php

namespace Tests\Unit\Controllers;

use Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use App\Service\RecruWorkerService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use App\Enums\Gender;
use Illuminate\Support\Facades\Event;

class AuthTest extends TestCase
{
    public function test_successful_login()
    {
        $email = 'user@example.com';
        $password = 'password123';

        $userMock = Mockery::mock(User::class)->makePartial();
        $userMock->password = 'hashed-password';
        $userMock->email = $email;

        $userMock->shouldReceive('hasVerifiedEmail')->andReturn(true);
        $userMock->shouldReceive('createToken')->andReturn((object)[
            'plainTextToken' => 'mocked-token'
        ]);

        // Mock UserRepository
        $repoMock = Mockery::mock(\App\Repository\UserRepository::class);
        $repoMock->shouldReceive('findByEmail')->with($email)->andReturn($userMock);

        // Mock Hash dan Auth
        Hash::shouldReceive('check')->with($password, 'hashed-password')->andReturn(true);
        Auth::shouldReceive('login')->with($userMock)->once();

        $service = new \App\Service\RecruWorkerService($repoMock);
        $result = $service->attemptLogin($email, $password);

        $this->assertTrue($result['status']);
        $this->assertEquals('Login success', $result['message']);
        $this->assertEquals('mocked-token', $result['access_token']);
    }


    public function test_fail_login_without_verif()
    {
        $email = 'user@example.com';
        $password = 'password123';

        $userMock = Mockery::mock(User::class)->makePartial();
        $userMock->password = 'hashed-password';
        $userMock->email = $email;

        $userMock->shouldReceive('hasVerifiedEmail')->andReturn(false);

        $repoMock = Mockery::mock(\App\Repository\UserRepository::class);
        $repoMock->shouldReceive('findByEmail')->with($email)->andReturn($userMock);

        Hash::shouldReceive('check')->with($password, 'hashed-password')->andReturn(true);
        Auth::shouldReceive('login')->with($userMock)->never();

        $service = new \App\Service\RecruWorkerService($repoMock);
        $result = $service->attemptLogin($email, $password);

        $this->assertFalse($result['status']);
        $this->assertEquals('Email not verified', $result['message']);
        $this->assertEquals(403, $result['code']);
    }

    public function test_register_recruiter_logic()
    {
        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('getClientOriginalExtension')->andReturn('jpg');
        $file->shouldReceive('storeAs')->once()->with('users', 'qwertyuiopasdfghjklz.jpg', 'public');

        $mockUser = Mockery::mock(User::class)->makePartial();
        $mockUser->shouldReceive('save')->once(); // save() harus terpanggil
        $mockUser->id_user = 'qwertyuiopasdfghjklz';

        $mockRepo = Mockery::mock('App\Repository\UserRepository');
        $mockRepo->shouldReceive('create')->once()->andReturn($mockUser);
        $mockRepo->shouldReceive('saveKtpPath')->once()->with($mockUser, 'qwertyuiopasdfghjklz.jpg')->andReturnUsing(function($user, $filename) {
            $user->ktp_card_path = $filename;
            $user->save(); // ini panggil save() pada objek yang sama
        });

        Event::fake();

        $service = new \App\Service\RecruWorkerService($mockRepo);
        $result = $service->registerRecruiter([
            'fullname' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'secret123',
            'phone' => '081234567890',
            'gender' => 'Laki-laki',
            'birthdate' => '2000-01-01',
            'ktp_card_path' => $file,
        ]);

        $this->assertTrue($result['status']);
        $this->assertEquals('User created successfully. Please verify your email.', $result['message']);
        $this->assertEquals('qwertyuiopasdfghjklz', $result['id_user']);
        $this->assertEquals('qwertyuiopasdfghjklz.jpg', $result['ktp_card_path']);
    }

    public function test_successful_logout()
    {
        // Mock user with verified email
        $userMock = Mockery::mock(User::class)->makePartial();
        $userMock->shouldReceive('hasVerifiedEmail')->andReturn(true);

        // Mock tokens() dan delete()
        $tokensMock = Mockery::mock();
        $tokensMock->shouldReceive('delete')->once()->andReturn(true);
        $userMock->shouldReceive('tokens')->andReturn($tokensMock);

        // Mock auth()->user()
        Auth::shouldReceive('user')->andReturn($userMock);

        // ⬇ Tambahkan ini
        $dummyService = Mockery::mock(\App\Service\RecruWorkerService::class);

        // Buat controller dengan mock service
        $controller = new \App\Http\Controllers\Api\AuthController($dummyService);

        // Jalankan dan test logout()
        $response = $controller->logout();
        $responseData = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Logout success', $responseData['message']);
    }


    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
