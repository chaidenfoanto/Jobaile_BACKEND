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
use Illuminate\Support\Facades\Storage;
use App\Service\RecruWorkerService;

class AuthController extends Controller
{
    protected $recruiterService;

    public function __construct(RecruWorkerService $recruiterService)
    {
        $this->recruiterService = $recruiterService;
    }
        /*
     * @OA\Post(
     *     path="/api/registerrecruiter",
     *     summary="Registrasi akun untuk recruiter",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"fullname", "email", "password", "phone", "gender", "birthdate", "ktp_card_path"},
     *                 @OA\Property(property="fullname", type="string", maxLength=50),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="password", type="string", format="password"),
     *                 @OA\Property(property="phone", type="string", minLength=10, maxLength=15),
     *                 @OA\Property(property="gender", type="string", enum={"Male", "Female"}),
     *                 @OA\Property(property="birthdate", type="string", format="date"),
     *                 @OA\Property(property="ktp_card_path", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="User berhasil didaftarkan"),
     *     @OA\Response(response=422, description="Validasi gagal"),
     *     @OA\Response(response=500, description="Kesalahan server")
     * )
     */

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
             return (object)[
                 'status' => false,
                 'message' => 'Validation error',
                 'errors' => $validator->errors(),
             ];
         }
 
         $data = $request->only(['fullname', 'email', 'password', 'phone', 'gender', 'birthdate']);
         $data['ktp_card_path'] = $request->file('ktp_card_path');
 
         return (object) $this->recruiterService->registerRecruiter($data);
     }

        /**
     * @OA\Post(
     *     path="/api/registerworker",
     *     summary="Registrasi akun untuk worker",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"fullname", "email", "password", "phone", "gender", "birthdate", "ktp_card_path"},
     *                 @OA\Property(property="fullname", type="string", maxLength=50),
     *                 @OA\Property(property="email", type="string", format="email"),
     *                 @OA\Property(property="password", type="string", format="password"),
     *                 @OA\Property(property="phone", type="string", minLength=10, maxLength=15),
     *                 @OA\Property(property="gender", type="string", enum={"Male", "Female"}),
     *                 @OA\Property(property="birthdate", type="string", format="date"),
     *                 @OA\Property(property="ktp_card_path", type="string", format="binary")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=200, description="User berhasil didaftarkan"),
     *     @OA\Response(response=422, description="Validasi gagal"),
     *     @OA\Response(response=500, description="Kesalahan server")
     * )
     */


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
             return (object)[
                 'status' => false,
                 'message' => 'Validation error',
                 'errors' => $validator->errors(),
             ];
         }
 
         $data = $request->only(['fullname', 'email', 'password', 'phone', 'gender', 'birthdate']);
         $data['ktp_card_path'] = $request->file('ktp_card_path');
 
         return (object) $this->recruiterService->registerWorker($data);
     }
     
        /**
     * @OA\Post(
     *     path="/api/login",
     *     summary="Login user",
     *     tags={"Auth"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email"),
     *             @OA\Property(property="password", type="string", format="password")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Login berhasil dan token diberikan"),
     *     @OA\Response(response=401, description="Kredensial salah"),
     *     @OA\Response(response=403, description="Email belum diverifikasi"),
     *     @OA\Response(response=422, description="Validasi gagal"),
     *     @OA\Response(response=500, description="Kesalahan server")
     * )
     */


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
             $result = $this->recruiterService->attemptLogin($request->email, $request->password);
 
             if (!$result['status']) {
                 return response()->json([
                     'status' => false,
                     'message' => $result['message'],
                 ], $result['code']);
             }
 
             return response()->json($result);
         } catch (\Exception $e) {
             return response()->json([
                 'status' => false,
                 'message' => 'Login failed',
                 'error' => $e->getMessage()
             ], 500);
         }
     }

        /**
     * @OA\Post(
     *     path="/api/logout",
     *     summary="Logout user",
     *     tags={"Auth"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(response=200, description="Logout berhasil"),
     *     @OA\Response(response=401, description="Unauthorized")
     * )
     */


    public function logout()
    {
        $user = auth()->user();

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => false,
                'message' => 'Email not verified. Please verify your email first.',
            ], 403);
        }

        if ($user) {
            $user->tokens()->delete();
        }

        return response()->json([
            'message' => 'Logout success'
        ]);
    }

    // public function gantiPassword(Request $request)
    // {
    //     $user = auth()->user();

    //     if (!$user) {
    //         return response()->json([
    //             'status' => false,
    //             'message' => 'User not found'
    //         ], 404);
    //     }

    //     // Implementasi ganti password sesuai kebutuhan
    // }
}
