<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\RecruiterModel;
use App\Models\WorkerModel;
use App\Models\RatingReviewModel;

class DashboardController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/dashboardworec",
     *     summary="Menampilkan dashboard recruiter berisi daftar ART (worker)",
     *     tags={"Dashboard"},
     *     security={{"sanctum":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Daftar ART berhasil diambil",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Worker found successfully"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(
     *                     @OA\Property(property="id_worker", type="integer", example=1),
     *                     @OA\Property(property="bio", type="string", example="Saya ART berpengalaman..."),
     *                     @OA\Property(property="umur", type="integer", example=25),
     *                     @OA\Property(property="fullname", type="string", example="Siti Aminah"),
     *                     @OA\Property(property="profile_picture", type="string", example="siti.png")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="User not found"
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Terjadi error saat mengambil data"
     *     )
     * )
     */
    public function DashboardWoRec(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ($user->role == 'Recruiter') {
                // Ambil input filter (bisa kosong)
                $filters = $request->only(['bio', 'location']);

                // Mulai query base: hanya ambil user dengan role 'worker'
                $query = \App\Models\WorkerModel::with('user')
                    ->whereHas('user', function ($q) {
                        $q->where('role', 'worker');
                    });

                // Terapkan filter jika diberikan
                if (!empty($filters['bio'])) {
                    $query->where('bio', 'like', '%' . $filters['bio'] . '%');
                }

                if (!empty($filters['location'])) {
                    $query->where('location', 'like', '%' . $filters['location'] . '%');
                }

                // Ambil data
                $workers = $query->inRandomOrder()->get();

                // Format data untuk respons
                $result = $workers->map(function ($worker) {
                    $birthdate = $worker->user->birthdate ?? null;
                    $umur = $birthdate ? \Carbon\Carbon::parse($birthdate)->age : "Belum ada umur";

                    $rating = \App\Models\RatingReviewModel::where('id_reviewed', $worker->id_user)
                        ->where('role', 'worker')
                        ->avg('rating');

                    return [
                        'id_worker' => $worker->id_worker,
                        'fullname' => $worker->user->fullname ?? '-',
                        'bio' => $worker->bio ?? 'Belum ada bio',
                        'umur' => $umur,
                        'location' => $worker->location ?? '-',
                        'profile_picture' => $worker->profile_picture ?? null,
                        'rating' => round($rating ?? 0, 2)
                    ];
                });
            } else if ($user->role == 'Worker') {
                $filters = $request->only(['family_size', 'location_address']);

                // Mulai query base: hanya ambil user dengan role 'worker'
                $query = \App\Models\RecruiterModel::with('user')
                    ->whereHas('user', function ($q) {
                        $q->where('role', 'recruiter');
                    });

                // Terapkan filter jika diberikan
                if (!empty($filters['family_size'])) {
                    $query->where('family_size', 'like', '%' . $filters['family_size'] . '%');
                }

                if (!empty($filters['location_address'])) {
                    $query->where('location_address', 'like', '%' . $filters['location_address'] . '%');
                }

                // Ambil data
                $recruiters = $query->inRandomOrder()->get();

                // Format data untuk respons
                $result = $recruiters->map(function ($recruiter) {
                    $birthdate = $recruiter->user->birthdate ?? null;
                    $umur = $birthdate ? \Carbon\Carbon::parse($birthdate)->age : "Belum ada umur";

                    $rating = \App\Models\RatingReviewModel::where('id_reviewed', $recruiter->id_user)
                        ->where('role', 'recruiter')
                        ->avg('rating');

                    return [
                        'id_recruiter' => $recruiter->id_recruiter,
                        'fullname' => $recruiter->user->fullname ?? '-',
                        'location_address' => $recruiter->location_address,
                        'umur' => $umur,
                        'house_type' => $recruiter->house_type,
                        'family_size' => $recruiter->family_size ?? '-',
                        'profile_picture' => $recruiter->profile_picture ?? null,
                        'rating' => round($rating ?? 0, 2)
                    ];
                });
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Role tidak dikenali',
                ], 403);
            }

            return response()->json([
                'status' => true,
                'message' => 'User ditemukan',
                'data' => $result
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function DetailWoRec($id) {
        try {
            $user = auth()->user();
    
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User belum terautentikasi'
                ], 401);
            }
    
            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email belum diverifikasi. Silakan verifikasi email terlebih dahulu.',
                ], 403);
            }
    
            if ($user->role == 'Recruiter') {
                $worker = WorkerModel::where('id_worker', $id)
                    ->with('user')
                    ->first();
    
                if (!$worker) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Worker tidak ditemukan.'
                    ], 404);
                }
    
                // Ambil rating rata-rata dan jumlah rating untuk worker ini
                $ratings = RatingReviewModel::where('id_reviewed', $worker->user->id_user)->get();
                $avgRating = $ratings->avg('rating');
                $countRating = $ratings->count();
    
                return response()->json([
                    'status' => true,
                    'message' => 'Detail worker found successfully',
                    'data' => [
                        'id_worker' => $worker->id_worker,
                        'fullname' => $worker->user->fullname,
                        'bio' => $worker->bio,
                        'umur' => Carbon::parse($worker->user->birthdate)->age ?? "Belum ada umur",
                        'profile_picture' => $worker->profile_picture,
                        'skill' => $worker->skill,
                        'experience_years' => $worker->experience_years,
                        'location' => $worker->location,
                        'expected_salary' => $worker->expected_salary,
                        'availability' => $worker->availability,
                        'rating_average' => round($avgRating, 2),
                    ]
                ], 200);
    
            } else if ($user->role == 'Worker') {
                $recruiter = RecruiterModel::where('id_recruiter', $id)
                    ->with('user')
                    ->first();
    
                if (!$recruiter) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Recruiter tidak ditemukan.'
                    ], 404);
                }
    
                // Ambil rating rata-rata dan jumlah rating untuk recruiter ini
                $ratings = RatingReviewModel::where('id_reviewed', $recruiter->user->id_user)->get();
                $avgRating = $ratings->avg('rating');
                $countRating = $ratings->count();
    
                return response()->json([
                    'status' => true,
                    'message' => 'Detail recruiter found successfully',
                    'data' => [
                        'id_recruiter' => $recruiter->id_recruiter,
                        'fullname' => $recruiter->user->fullname,
                        'house_type' => $recruiter->house_type,
                        'umur' => Carbon::parse($recruiter->user->birthdate)->age ?? "Belum ada umur",
                        'profile_picture' => $recruiter->profile_picture,
                        'family_size' => $recruiter->family_size,
                        'location_adddress' => $recruiter->location_address,
                        'desc' => $recruiter->desc,
                        'rating_average' => round($avgRating, 2),
                        'rating_count' => $countRating,
                    ]
                ], 200);
            }
    
            return response()->json([
                'status' => false,
                'message' => 'Role tidak valid untuk mengakses detail ini.'
            ], 403);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
