<?php

namespace Tests\Unit;

use Tests\TestCase;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Mockery;
use App\Http\Controllers\Api\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use App\Enums\Gender;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class ProfileUserTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_profile_user()
    {
        $mockObject = Mockery::mock(User::class)->makePartial();
        $mockObject->id_user = Str::random(20);
        $mockObject->fullname = 'John Doe';
        $mockObject->email = 'john@example.com';
        $mockObject->phone = '08123456789';

        Auth::shouldReceive('user')->andReturn($mockObject);
        $mockObject->shouldReceive('hasVerifiedEmail')->andReturn(True);

        $controller = new Profilecontroller();

        $request = new Request();
        $response = $controller->getProfile($request);
        $responseData = $response->getData(true);

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($responseData['status']);
        $this->assertEquals('user found successfully', $responseData['message']);
        $this->assertEquals($mockObject->id_user, $responseData['id_user']);
        $this->assertEquals($mockObject->fullname, $responseData['fullname']);
        $this->assertEquals($mockObject->email, $responseData['email']);
        $this->assertEquals($mockObject->phone, $responseData['phone']);
    }
}
