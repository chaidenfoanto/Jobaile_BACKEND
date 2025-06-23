<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\RecruiterModel;
use App\Models\WorkerModel;
use App\Models\RatingReviewModel;
use App\Models\Job_OfferModel;

class DashboardController extends Controller
{
    /**
 * @OA\Get(
 *     path="/api/dashboardrecruiter",
 *     summary="Menampilkan daftar ART (worker) untuk recruiter",
 *     tags={"Dashboard"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Daftar worker berhasil diambil",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="User ditemukan"),
 *             @OA\Property(
 *                 property="data",
 *                 type="array",
 *                 @OA\Items(
 *                     @OA\Property(property="id_worker", type="integer", example=1),
 *                     @OA\Property(property="fullname", type="string", example="Siti Aminah"),
 *                     @OA\Property(property="bio", type="string", example="Saya ART berpengalaman"),
 *                     @OA\Property(property="umur", type="integer", example=25),
 *                     @OA\Property(property="location", type="string", example="Makassar"),
 *                     @OA\Property(property="profile_picture", type="string", example="foto.png"),
 *                     @OA\Property(property="rating", type="number", format="float", example=4.7)
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=403, description="Role tidak dikenali"),
 *     @OA\Response(response=404, description="User not found"),
 *     @OA\Response(response=500, description="Terjadi kesalahan")
 * )
 */
    public function DashboardRecruiter(Request $request)
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

    /**
 * @OA\Get(
 *     path="/api/dashboardworker",
 *     summary="Menampilkan 1 job offer acak kepada worker",
 *     tags={"Dashboard"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Job offer tersedia",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="List job offer tersedia"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id_job", type="string", example="job123"),
 *                 @OA\Property(property="job_title", type="string", example="Bersih-bersih rumah"),
 *                 @OA\Property(property="desc", type="string", example="Pekerjaan mingguan membersihkan rumah..."),
 *                 @OA\Property(property="status", type="string", example="open"),
 *                 @OA\Property(
 *                     property="recruiter",
 *                     type="object",
 *                     @OA\Property(property="id_recruiter", type="string", example="rec123"),
 *                     @OA\Property(property="fullname", type="string", example="Bu Rina"),
 *                     @OA\Property(property="email", type="string", example="rina@example.com")
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response=403, description="Email belum diverifikasi / bukan worker"),
 *     @OA\Response(response=404, description="User tidak ditemukan"),
 *     @OA\Response(response=500, description="Terjadi kesalahan")
 * )
 */


    public function DashboardWorker() {
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
                    'message' => 'Email belum diverifikasi. Silakan verifikasi email terlebih dahulu.',
                ], 403);
            }

            if ($user->role !== 'Worker') {
                return response()->json([
                    'status' => false,
                    'message' => 'Cuma worker yang bisa post job_offer'
                ]);
            }

            $worker = \App\Models\WorkerModel::where('id_user', $user->id_user)->first();
            if (!$worker) {
                return response()->json([
                    'status' => false,
                    'message' => 'Lengkapi profil worker terlebih dahulu untuk melihat job offer.',
                ], 403);
            }

            $job = Job_OfferModel::with('recruiter')
                    ->where('status', 'open')
                    ->inRandomOrder()
                    ->first(); // ambil satu job acak

            if (!$job) {
                return response()->json([
                    'message' => 'Belum ada Job yang dipost oleh recruiter'
                ]);
            }

            return response()->json([
                'status' => true,
                'message' => 'List job offer tersedia',
                'data' => [
                    'id_job'     => $job->id_job,
                    'job_title'  => $job->job_title,
                    'desc'       => $job->desc,
                    'status'     => $job->status,
                    'recruiter'  => [
                        'id_recruiter' => $job->recruiter->id_recruiter ?? null,
                        'fullname'     => $job->recruiter->fullname ?? null,
                        'email'        => $job->recruiter->email ?? null,
                        // tambahkan data lain sesuai kebutuhan
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
 * @OA\Get(
 *     path="/api/detailprofileworec/{id}",
 *     summary="Menampilkan detail profile Worker (jika user recruiter) atau Recruiter (jika user worker)",
 *     tags={"Dashboard"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID Worker atau Recruiter",
 *         required=true,
 *         @OA\Schema(type="integer", example=2)
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Data ditemukan",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Detail worker found successfully"),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=401, description="Belum login"),
 *     @OA\Response(response=403, description="Email belum diverifikasi / role tidak valid"),
 *     @OA\Response(response=404, description="Worker atau Recruiter tidak ditemukan"),
 *     @OA\Response(response=500, description="Terjadi kesalahan")
 * )
 */


    public function DetailWorker($id) {
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
