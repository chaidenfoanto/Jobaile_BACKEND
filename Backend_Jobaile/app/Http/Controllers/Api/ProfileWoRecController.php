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
use App\Models\WorkerModel;

class ProfileWoRecController extends Controller
{
    
        /**
     * @OA\Post(
     *     path="/api/postworec",
     *     operationId="createWorkerOrRecruiterProfile",
     *     tags={"ProfileWoRec"},
     *     summary="Membuat profil Worker atau Recruiter",
     *     description="Endpoint ini digunakan untuk membuat profil sesuai role user yang sedang login.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             anyOf={
     *                 @OA\Schema(
     *                     required={"id_recruiter"},
     *                     @OA\Property(property="id_recruiter", type="string", example="R001"),
     *                     @OA\Property(property="house_type", type="string", example="Rumah"),
     *                     @OA\Property(property="family_size", type="integer", example=4),
     *                     @OA\Property(property="location_address", type="string", example="Jl. Kenangan No.10"),
     *                     @OA\Property(property="desc", type="string", example="Mencari ART untuk membantu bersih-bersih."),
     *                 ),
     *                 @OA\Schema(
     *                     required={"id_worker"},
     *                     @OA\Property(property="id_worker", type="string", example="W001"),
     *                     @OA\Property(property="bio", type="string", example="Saya berpengalaman 5 tahun di bidang kebersihan."),
     *                     @OA\Property(property="skill", type="string", example="Bersih rumah, memasak"),
     *                     @OA\Property(property="experience_years", type="integer", example=5),
     *                     @OA\Property(property="location", type="string", example="Depok"),
     *                     @OA\Property(property="expected_salary", type="integer", example=3000000),
     *                     @OA\Property(property="availability", type="string", enum={"penuh_waktu","paruh_waktu","mingguan","bulanan"}, example="penuh_waktu")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil membuat profil",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="user created successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Email belum diverifikasi"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */

    public function postworec(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email not verified. Please verify your email first.',
                ], 403);
            }

            if ($user->role == 'Recruiter') {
                $validator = Validator::make($request->all(), [
                    'house_type' => 'nullable|string|max:100',
                    'family_size' => 'nullable|integer|min:1',
                    'location_address' => 'nullable|string|max:100',
                    'desc' => 'nullable|string',
                    'profile_picture' => 'nullable|image|mimes:jpeg,jpg,png|max:2048'
                ]);
        
                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $user = RecruiterModel::create([
                    'id_recruiter' => $request->id_recruiter,
                    'id_user' => $user->id_user,
                    'house_type' => $request->house_type,
                    'family_size' => $request->family_size,
                    'location_address' => $request->location_address,
                    'desc' => $request->desc,
                ]);

                $uploadfolder = 'profile';
                $profile = $request->file('profile_picture');

                $custom = null;

                if ($profile && $profile->isValid()) {
                    $custom = $user->id_recruiter . '.' . $profile->getClientOriginalExtension();
                    $profile->storeAs($uploadfolder, $custom , 'public');
                }

                $user->profile_picture = $custom;
                $user->save();

                return response()->json([
                    'status' => true,
                    'message' => 'user created successfully',
                    'data' => $user,
                ]);
            } else if ($user->role == 'Worker') {
                $validator = Validator::make($request->all(), [
                    'bio' => 'nullable|string',
                    'skill' => 'nullable|string',
                    'experience_years' => 'nullable|integer|min:0',
                    'location' => 'nullable|string|max:100',
                    'expected_salary' => 'nullable|integer|min:0',
                    'availability' => 'nullable|in:penuh_waktu,paruh_waktu,mingguan,bulanan',
                    'profile_picture' => 'nullable|image|mimes:jpeg,jpg,png|max:2048'
                ]);
        
                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $user = WorkerModel::create([
                    'id_worker' => $request->id_worker,
                    'id_user' => $user->id_user,
                    'bio' => $request->bio,
                    'skill' => $request->skill,
                    'experience_years' => $request->experience_years,
                    'location' => $request->location,
                    'expected_salary' => $request->expected_salary,
                    'availability' => $request->availability,
                ]);

                $tempatnya = 'profile';
                $profile = $request->file('profile_picture');

                $custom = null;

                if ($profile && $profile->isValid()) {
                    $custom = $user->id_worker . '.' . $profile->getClientOriginalExtension();
                    $profile->storeAs($tempatnya, $custom, 'public');
                }

                $user->profile_picture = $custom;
                $user->save();

                return response()->json([
                    'status' => true,
                    'message' => 'user created successfully',
                    'data' => $user,
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching user profile',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

        /**
     * @OA\Post(
     *     path="/api/updateworec",
     *     operationId="updateWorkerOrRecruiterProfile",
     *     tags={"ProfileWoRec"},
     *     summary="Memperbarui profil Worker atau Recruiter",
     *     description="Endpoint ini digunakan untuk memperbarui profil sesuai role user yang sedang login.",
     *     security={{"bearerAuth":{}}},
     *
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             oneOf={
     *                 @OA\Schema(
     *                     @OA\Property(property="house_type", type="string", example="Apartemen"),
     *                     @OA\Property(property="family_size", type="integer", example=3),
     *                     @OA\Property(property="location_address", type="string", example="Jl. Baru No. 1"),
     *                     @OA\Property(property="desc", type="string", example="Butuh ART paruh waktu."),
     *                 ),
     *                 @OA\Schema(
     *                     @OA\Property(property="bio", type="string", example="Pengalaman ART 3 tahun."),
     *                     @OA\Property(property="skill", type="string", example="Menyetrika, mencuci"),
     *                     @OA\Property(property="experience_years", type="integer", example=3),
     *                     @OA\Property(property="location", type="string", example="Jakarta"),
     *                     @OA\Property(property="expected_salary", type="integer", example=2500000),
     *                     @OA\Property(property="availability", type="string", enum={"penuh_waktu","paruh_waktu","mingguan","bulanan"}, example="mingguan")
     *                 )
     *             }
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Berhasil memperbarui profil",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Profile updated successfully"),
     *             @OA\Property(property="data", type="object")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Email belum diverifikasi atau role tidak valid"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Profil tidak ditemukan"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validasi gagal"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error"
     *     )
     * )
     */


    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email not verified. Please verify your email first.',
                ], 403);
            }

            if ($user->role == 'Recruiter') {
                $validator = Validator::make($request->all(), [
                    'house_type' => 'nullable|string|max:100',
                    'family_size' => 'nullable|integer|min:1',
                    'location_address' => 'nullable|string|max:100',
                    'desc' => 'nullable|string',
                    'profile_picture' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $profile = RecruiterModel::where('id_user', $user->id_user)->first();
                if (!$profile) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Profile not found',
                    ], 404);
                }

                $data = $request->only(['house_type', 'family_size', 'location_address', 'desc']);

                if ($request->hasFile('profile_picture')) {
                    if ($profile->profile_picture) {
                        Storage::disk('public')->delete('profile/' . $profile->profile_picture);
                    }

                    $uploadfolder = 'profile';
                    $profile_uploaded_path = $request->file('profile_picture');
                    $filename = $profile->id_recruiter . '.' . $profile_uploaded_path->getClientOriginalExtension();
                    $profile_uploaded_path->storeAs($uploadfolder, $filename, 'public');

                    $data['profile_picture'] = $filename;
                }

                $profile->update($data);

                return response()->json([
                    'status' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $profile,
                ]);
            }

            // ------------------- Worker -------------------
            if ($user->role == 'Worker') {
                $validator = Validator::make($request->all(), [
                    'bio' => 'nullable|string',
                    'skill' => 'nullable|string',
                    'experience_years' => 'nullable|integer|min:0',
                    'location' => 'nullable|string|max:100',
                    'expected_salary' => 'nullable|integer|min:0',
                    'availability' => 'nullable|in:penuh_waktu,paruh_waktu,mingguan,bulanan',
                    'profile_picture' => 'nullable|image|mimes:jpeg,jpg,png|max:2048',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $profile = WorkerModel::where('id_user', $user->id_user)->first();
                if (!$profile) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Profile not found',
                    ], 404);
                }

                $data = $request->only(['bio', 'skill', 'experience_years', 'location', 'expected_salary', 'availability']);

                if ($request->hasFile('profile_picture')) {
                    if ($profile->profile_picture) {
                        Storage::disk('public')->delete('profile/' . $profile->profile_picture);
                    }

                    $uploadfolder = 'profile';
                    $profile_uploaded_path = $request->file('profile_picture');
                    $filename = $profile->id_worker . '.' . $profile_uploaded_path->getClientOriginalExtension();
                    $profile_uploaded_path->storeAs($uploadfolder, $filename, 'public');

                    $data['profile_picture'] = $filename;
                }

                $profile->update($data);

                return response()->json([
                    'status' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $profile,
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Invalid role',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
