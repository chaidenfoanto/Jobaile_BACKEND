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

     public function getProfile()
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
     
         if ($user->role === 'Worker') {
             $worker = \App\Models\WorkerModel::with('user')
                 ->where('id_user', $user->id_user)
                 ->whereHas('user', function ($q) {
                     $q->where('role', 'worker');
                 })
                 ->first();
     
             return response()->json([
                 'status' => true,
                 'message' => 'Worker profile ditemukan.',
                 'id_user' => $user->id_user,
                 'fullname' => $user->fullname,
                 'email' => $user->email,
                 'phone' => $user->phone,
                 'gender' => $user->gender,
                 'birthdate' => $user->birthdate,
                 'photo' => $worker && $worker->profile_picture
                     ? asset('storage/profile/' . $worker->profile_picture)
                     : null
             ]);
         }
     
         if ($user->role === 'Recruiter') {
             $recruiter = \App\Models\RecruiterModel::with('user')
                 ->where('id_user', $user->id_user)
                 ->whereHas('user', function ($q) {
                     $q->where('role', 'recruiter');
                 })
                 ->first();
     
             return response()->json([
                 'status' => true,
                 'message' => 'Recruiter profile ditemukan.',
                 'id_user' => $user->id_user,
                 'fullname' => $user->fullname,
                 'email' => $user->email,
                 'phone' => $user->phone,
                 'gender' => $user->gender,
                 'birthdate' => $user->birthdate,
                 'photo' => $recruiter && $recruiter->profile_picture
                     ? asset('storage/profile/' . $recruiter->profile_picture)
                     : null
             ]);
         }
     
         return response()->json([
             'status' => false,
             'message' => 'Role tidak valid.',
         ], 400);
     }
     
}