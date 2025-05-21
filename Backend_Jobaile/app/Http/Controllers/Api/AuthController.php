<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Gender;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    public function registerRecruiter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:50',
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|min:10|max:15',
            'gender' => ['required', new Enum(Gender::class)],
            'birthdate' => 'required|date',
            'ktp_card_path' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {

            $user = User::create([
                'id_user' => $request->id_user,
                'fullname' => $request->fullname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'gender' => Gender::from($request->gender),
                'birthdate' => $request->birthdate,
                'role' => 'Recruiter',
            ]);

            $uploadfolder = 'users';
            $ktp_card = $request->file('ktp_card_path');
            $custom = $user->id_user . '.' . $ktp_card->getClientOriginalExtension();
            $ktp_card->storeAs($uploadfolder, $custom, 'public');

            $user->ktp_card_path = $custom; 
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'user created successfully',
                'id_user' => $user->id_user,
                'data' => $user,
                'ktp_card_path' => $custom
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function registerWorker(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:50',
            'email' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|min:10|max:15',
            'gender' => ['required', new Enum(Gender::class)],
            'birthdate' => 'required|date',
            'ktp_card_path' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = User::create([
                'id_user' => $request->id_user,
                'fullname' => $request->fullname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'gender' => Gender::from($request->gender),
                'birthdate' => $request->birthdate,
                'role' => 'Worker',
            ]);

            $uploadfolder = 'users';
            $ktp_card = $request->file('ktp_card_path');
            $custom = $user->id_user . '.' . $ktp_card->getClientOriginalExtension();
            $ktp_card->storeAs($uploadfolder, $custom, 'public');

            $user->ktp_card_path = $custom;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'user created successfully',
                'id_user' => $user->id_user,
                'data' => $user,
                'ktp_card_path' => $custom,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8',
        ]);


        try {
            if (! Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'message' => 'Unauthorized'
                ], 401);
            }

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = User::where('email', $request->email)->firstOrFail();

            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid password',
                ], 401);
            }

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login success',
                'access_token' => $token,
                'data' => $user,
                'token_type' => 'Bearer'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Login failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function logout()
    {
        $user = auth()->user();

        $user->tokens()->delete();

        return response()->json([
            'message' => 'logout success'
        ]);
    }
}