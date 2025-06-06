<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\RecruiterModel;
use App\Models\WorkerModel;

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

            if ($user->role !== 'Recruiter') {
                return response()->json([
                    'status' => false,
                    'message' => 'Akses hanya untuk recruiter',
                ], 403);
            }

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

            return response()->json([
                'status' => true,
                'message' => 'Worker ditemukan',
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

    public function DetailWorker($id) {
        try{
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => 'false',
                    'message' => 'User belum terautentikasi'
                ], 401);
            }

            if (!$user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email not verified. Please verify your email first.',
                ], 403);
            }

            if ($user->role != 'Recruiter') {
                return response()->json([
                    'status' => false,
                    'messagee' => 'Hanya recruiter yang dapat mengakses detail worker'
                ], 401);
            }

            $worker = WorkerModel::where('id_worker', $id)
                ->with('user')
                ->first();

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
                    'availability' => $worker->availability
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occured',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
