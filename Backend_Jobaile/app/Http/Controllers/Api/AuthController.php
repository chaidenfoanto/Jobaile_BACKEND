<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Gender;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\URL;

class AuthController extends Controller
{
    public function registerRecruiter(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:50',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|min:10|max:15',
            'gender' => ['required', new Enum(Gender::class)],
            'birthdate' => 'required|date',
            'ktp_card_path' => 'required|image|mimes:jpeg,png,jpg|max:2048',
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
                'fullname' => $request->fullname,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $request->phone,
                'gender' => Gender::from($request->gender),
                'birthdate' => $request->birthdate,
                'role' => 'Recruiter',
            ]);

            // Upload file KTP
            $uploadfolder = 'users';
            $ktp_card = $request->file('ktp_card_path');
            $custom = $user->id_user . '.' . $ktp_card->getClientOriginalExtension();
            $ktp_card->storeAs($uploadfolder, $custom, 'public');

            $user->ktp_card_path = $custom;
            $user->save();

            // Kirim email verifikasi otomatis
            event(new Registered($user));

            return response()->json([
                'status' => true,
                'message' => 'User created successfully. Please verify your email.',
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

    public function registerWorker(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fullname' => 'required|string|max:50',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
            'phone' => 'required|string|min:10|max:15',
            'gender' => ['required', new Enum(Gender::class)],
            'birthdate' => 'required|date',
            'ktp_card_path' => 'required|image|mimes:jpeg,png,jpg|max:2048',
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

            event(new Registered($user));

            return response()->json([
                'status' => true,
                'message' => 'User created successfully. Please verify your email.',
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

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            // Ambil user dulu berdasarkan email
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }

            // Cek password manual
            if (!Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid credentials',
                ], 401);
            }

            // Cek email sudah verified atau belum
            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email not verified. Please verify your email first.',
                ], 403);
            }

            // Login user
            Auth::login($user);

            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'status' => true,
                'message' => 'Login success',
                'access_token' => $token,
                'data' => $user,
                'token_type' => 'Bearer',
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

        if ($user) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Logout success'
        ]);
    }

    public function gantiPassword(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found'
            ], 404);
        }

        // Implementasi ganti password sesuai kebutuhan
    }
}
