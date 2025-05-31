<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Gender;
use Illuminate\Support\Facades\Storage;
use App\Models\RecruiterModel;

class Profilecontroller extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/profile",
     *     summary="Get authenticated user profile",
     *     tags={"Profile Worker Recruiter"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="tukang found successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id_user", type="integer", example=1),
     *                 @OA\Property(property="fullname", type="string", example="John Doe"),
     *                 @OA\Property(property="email", type="string", example="johndoe@example.com"),
     *                 @OA\Property(property="phone", type="string", example="08123456789"),
     *                 @OA\Property(property="gender", type="string", example="male"),
     *                 @OA\Property(property="birthdate", type="string", format="date", example="2000-01-01"),
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Email not verified"
     *     )
     * )
     */

    public function getProfile(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => 'error',
                'message' => 'User tidak terautentikasi.',
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => false,
                'message' => 'Email not verified. Please verify your email first.',
            ], 403);
        }

        return response()->json([
            'status' => true,
            'message' => 'tukang found successfully',
            'id_user' => $user->id_user,
            'fullname' => $user->fullname,
            'email' => $user->email,
            'phone' => $user->phone
        ], 200);
    }

    
}