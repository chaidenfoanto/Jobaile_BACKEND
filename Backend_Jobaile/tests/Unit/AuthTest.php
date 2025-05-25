<?php
namespace Tests\Unit;

use Tests\TestCase;
use App\Enums\Gender;
use App\Http\Controllers\Api\AuthController;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Laravel\Sanctum\PersonalAccessToken;
use Mockery;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Enum;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    private AuthController $controller;

    protected function setUp(): void
    {
        parent::setUp();
        $this->controller = new AuthController();
        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createRequest($data, $files = []): Request
    {
        $request = new Request();
        $request->replace($data);
        foreach ($files as $key => $file) {
            $request->files->set($key, $file);
        }
        return $request;
    }

    // Register Recruiter Tests
    public function test_register_recruiter_validation_fails()
    {
        $validator = Mockery::mock(\Illuminate\Validation\Validator::class);
        $validator->shouldReceive('fails')->once()->andReturn(true);
        $validator->shouldReceive('errors')->once()->andReturn(collect(['field' => ['Error message']]));
        
        Validator::shouldReceive('make')->once()->andReturn($validator);

        $request = $this->createRequest(['invalid' => 'data']);
        $response = $this->controller->registerRecruiter($request);

        $this->assertEquals(422, $response->status());
        $this->assertArrayHasKey('errors', $response->getData(true));
    }

    public function test_registers_a_worker_successfully()
    {
        Storage::fake('public');
        Event::fake();

        // Create a mock request
        $request = new Request([
            'fullname' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone' => '1234567890',
            'gender' => Gender::Laki_laki->value, // Ensure this matches your
            'birthdate' => '1990-01-01',
        ]);

        // Simulate file upload
        $request->files->set('ktp_card_path', UploadedFile::fake()->image('ktp.jpg'));

        // Call the method directly (no HTTP request)
        $controller = new \App\Http\Controllers\Api\AuthController(); // Adjust to your controller
        $response = $controller->registerWorker($request);

        // Assert response
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->getData()->status);
        $this->assertEquals('User created successfully. Please verify your email.', $response->getData()->message);

        // Check database
        $this->assertDatabaseHas('users', [
            'email' => 'john@example.com',
            'role' => 'Worker'
        ]);

        // Check file storage
        $user = User::first();
        Storage::disk('public')->assertExists('users/' . $user->id_user . '.jpg');

        // Check event
        Event::assertDispatched(Registered::class);
    }

    // Register Worker Tests
    public function test_register_worker_validation_fails()
    {
        $validator = Mockery::mock(\Illuminate\Validation\Validator::class);
        $validator->shouldReceive('fails')->once()->andReturn(true);
        $validator->shouldReceive('errors')->once()->andReturn(collect(['field' => ['Error message']]));
        
        Validator::shouldReceive('make')->once()->andReturn($validator);

        $request = $this->createRequest(['invalid' => 'data']);
        $response = $this->controller->registerWorker($request);

        $this->assertEquals(422, $response->status());
        $this->assertArrayHasKey('errors', $response->getData(true));
    }

    public function test_registers_a_recruiter_successfully()
    {
        Storage::fake('public');
        Event::fake();

        // Buat instance request palsu
        $request = new \Illuminate\Http\Request([
            'fullname' => 'Jane Recruiter',
            'email' => 'jane.recruiter@example.com',
            'password' => 'securepass123',
            'phone' => '081234567890',
            'gender' => Gender::Perempuan->value, // pastikan enum ini cocok
            'birthdate' => '1992-05-15',
        ]);

        // Simulasikan upload file KTP
        $request->files->set('ktp_card_path', \Illuminate\Http\UploadedFile::fake()->image('ktp.jpg'));

        // Panggil controller langsung
        $controller = new \App\Http\Controllers\Api\AuthController(); // pastikan namespace cocok
        $response = $controller->registerRecruiter($request);

        // Ambil responsenya
        $responseData = $response->getData(true);

        // Cek status HTTP & isi respons
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['status']);
        $this->assertEquals('User created successfully. Please verify your email.', $responseData['message']);

        // Cek user di database
        $this->assertDatabaseHas('users', [
            'email' => 'jane.recruiter@example.com',
            'role' => 'Recruiter',
        ]);

        // Cek file tersimpan
        $user = \App\Models\User::where('email', 'jane.recruiter@example.com')->first();
        Storage::disk('public')->assertExists('users/' . $user->id_user . '.jpg');

        // Cek event terkirim
        Event::assertDispatched(\Illuminate\Auth\Events\Registered::class);
    }


    // Login Tests
    public function test_login_validation_fails()
    {
        $validator = Mockery::mock(\Illuminate\Validation\Validator::class);
        $validator->shouldReceive('fails')->once()->andReturn(true);
        $validator->shouldReceive('errors')->once()->andReturn(collect(['field' => ['Error message']]));
        
        Validator::shouldReceive('make')->once()->andReturn($validator);

        $request = $this->createRequest(['invalid' => 'data']);
        $response = $this->controller->login($request);

        $this->assertEquals(422, $response->status());
        $this->assertArrayHasKey('errors', $response->getData(true));
    }

    public function test_login_user_not_found()
    {
        $validator = Mockery::mock(\Illuminate\Validation\Validator::class);
        $validator->shouldReceive('fails')->once()->andReturn(false);
        Validator::shouldReceive('make')->once()->andReturn($validator);

        $request = $this->createRequest([
            'email' => 'nonexistent@example.com',
            'password' => 'password123'
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(404, $response->status());
        $this->assertFalse($response->getData(true)['status']);
    }

    public function test_login_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('correctpassword'),
            'role' => 'Worker'
        ]);

        $validator = Mockery::mock(\Illuminate\Validation\Validator::class);
        $validator->shouldReceive('fails')->once()->andReturn(false);
        Validator::shouldReceive('make')->once()->andReturn($validator);

        $request = $this->createRequest([
            'email' => $user->email,
            'password' => 'wrongpassword',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(401, $response->status());
        $this->assertFalse($response->getData(true)['status']);
    }

    public function test_login_email_not_verified()
    {
        $user = User::factory()->unverified()->create([
            'password' => Hash::make('password123'),
            'role' => 'Worker'
        ]);

        $validator = Mockery::mock(\Illuminate\Validation\Validator::class);
        $validator->shouldReceive('fails')->once()->andReturn(false);
        Validator::shouldReceive('make')->once()->andReturn($validator);

        $request = $this->createRequest([
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(403, $response->status());
        $this->assertFalse($response->getData(true)['status']);
    }

    public function test_login_success()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'role' => 'Worker'
        ]);

        $validator = Mockery::mock(\Illuminate\Validation\Validator::class);
        $validator->shouldReceive('fails')->once()->andReturn(false);
        Validator::shouldReceive('make')->once()->andReturn($validator);

        // Mock Auth facade
        Auth::shouldReceive('login')->once();

        $request = $this->createRequest([
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response = $this->controller->login($request);

        $this->assertEquals(200, $response->status());
        $this->assertTrue($response->getData(true)['status']);
        $this->assertArrayHasKey('access_token', $response->getData(true));
    }

    // Logout Tests
    public function test_logout_success()
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'role' => 'Worker' // Explicitly set a valid role
        ]);
        
        // Create a real token for the user
        $token = $user->createToken('auth_token');
        
        // Mock Auth to return our user
        Auth::shouldReceive('user')->once()->andReturn($user);

        $response = $this->controller->logout();

        $this->assertEquals(200, $response->status());
        $this->assertEquals('Logout success', $response->getData(true)['message']);
    }

    public function test_logout_email_not_verified()
    {
        $user = User::factory()->unverified()->create([
            'role' => 'Worker' // Explicitly set a valid role
        ]);
        
        Auth::shouldReceive('user')->once()->andReturn($user);

        $response = $this->controller->logout();

        $this->assertEquals(403, $response->status());
        $this->assertFalse($response->getData(true)['status']);
    }
}
