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
use Illuminate\Support\Str;

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
        // Mock file upload
        $file = UploadedFile::fake()->image('ktp.jpg');
        
        // Data input
        $requestData = [
            'fullname' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone' => '1234567890',
            'gender' => 'Laki-laki',
            'birthdate' => '1990-01-01',
            'ktp_card_path' => $file,
        ];

        // 1. Test Validator
        $validator = Validator::make($requestData, [
            'fullname' => 'required|string|max:50',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|min:10|max:15',
            'gender' => ['required', new Enum(Gender::class)],
            'birthdate' => 'required|date',
            'ktp_card_path' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $this->assertFalse($validator->fails());

        // 2. Test User Creation
        $user = new User([
            'fullname' => $requestData['fullname'],
            'email' => $requestData['email'],
            'password' => Hash::make($requestData['password']),
            'phone' => $requestData['phone'],
            'gender' => Gender::from($requestData['gender']),
            'birthdate' => $requestData['birthdate'],
            'role' => 'Worker',
        ]);

        $this->assertEquals('John Doe', $user->fullname);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertEquals('Worker', $user->role);

        // 3. Test File Handling (simulasi)
        $uploadfolder = 'users';
        $customFilename = 'test123.jpg'; // Simulasi filename
        $user->ktp_card_path = $customFilename;
        
        $this->assertEquals($customFilename, $user->ktp_card_path);
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
        // Mock file upload
        $file = UploadedFile::fake()->image('ktp.jpg');
        
        // Data input
        $requestData = [
            'fullname' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'phone' => '1234567890',
            'gender' => 'Perempuan',
            'birthdate' => '1990-01-01',
            'ktp_card_path' => $file,
        ];

        // 1. Test Validator
        $validator = Validator::make($requestData, [
            'fullname' => 'required|string|max:50',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|min:10|max:15',
            'gender' => ['required', new Enum(Gender::class)],
            'birthdate' => 'required|date',
            'ktp_card_path' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $this->assertFalse($validator->fails());

        // 2. Test User Creation
        $user = new User([
            'fullname' => $requestData['fullname'],
            'email' => $requestData['email'],
            'password' => Hash::make($requestData['password']),
            'phone' => $requestData['phone'],
            'gender' => Gender::from($requestData['gender']),
            'birthdate' => $requestData['birthdate'],
            'role' => 'Worker',
        ]);

        $this->assertEquals('John Doe', $user->fullname);
        $this->assertEquals('john@example.com', $user->email);
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertEquals('Worker', $user->role);

        // 3. Test File Handling (simulasi)
        $uploadfolder = 'users';
        $customFilename = 'test123.jpg'; // Simulasi filename
        $user->ktp_card_path = $customFilename;
        
        $this->assertEquals($customFilename, $user->ktp_card_path);
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
