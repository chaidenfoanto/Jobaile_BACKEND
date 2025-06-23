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
 *     summary="Ambil profil user yang sedang login (worker atau recruiter)",
 *     tags={"Profile Worker Recruiter"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Profil ditemukan",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Worker profile ditemukan."),
 *             @OA\Property(property="id_user", type="string", example="u5Xd33W..."),
 *             @OA\Property(property="fullname", type="string", example="John Doe"),
 *             @OA\Property(property="email", type="string", example="john@example.com"),
 *             @OA\Property(property="phone", type="string", example="081234567890"),
 *             @OA\Property(property="gender", type="string", example="male"),
 *             @OA\Property(property="birthdate", type="string", format="date", example="1990-01-01"),
 *             @OA\Property(property="photo", type="string", format="url", example="https://yourapp.com/storage/profile/image.jpg")
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized - User belum login",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="string", example="error"),
 *             @OA\Property(property="message", type="string", example="User tidak terautentikasi.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Email belum diverifikasi",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Email not verified. Please verify your email first.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=400,
 *         description="Role tidak valid",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Role tidak valid.")
 *         )
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