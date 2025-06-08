<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkerModel;
use App\Models\RatingReviewModel;
use Illuminate\Support\Facades\DB;

class InstantMatchController extends Controller
{
    /**
 * @OA\Get(
 *     path="/api/instantmatch",
 *     summary="Ambil worker secara acak untuk recruiter",
 *     tags={"Instant Match"},
 *     security={{"sanctum":{}}},
 *     @OA\Response(
 *         response=200,
 *         description="Worker berhasil ditemukan secara acak",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Worker ditemukan secara acak!"),
 *             @OA\Property(
 *                 property="data",
 *                 type="object",
 *                 @OA\Property(property="id_worker", type="integer", example=1),
 *                 @OA\Property(property="bio", type="string", example="Tukang AC berpengalaman 5 tahun."),
 *                 @OA\Property(property="skill", type="string", example="AC Service, Las, Plumbing"),
 *                 @OA\Property(property="experience_years", type="integer", example=5),
 *                 @OA\Property(property="location", type="string", example="Jakarta"),
 *                 @OA\Property(property="expected_salary", type="integer", example=5000000),
 *                 @OA\Property(property="availability", type="string", example="Full-time"),
 *                 @OA\Property(property="profile_picture", type="string", example="https://example.com/images/profile.jpg"),
 *                 @OA\Property(
 *                     property="user",
 *                     type="object",
 *                     @OA\Property(property="fullname", type="string", example="Budi Santoso"),
 *                     @OA\Property(property="email", type="string", format="email", example="budi@example.com"),
 *                     @OA\Property(property="phone", type="string", example="081234567890"),
 *                     @OA\Property(property="gender", type="string", example="Male"),
 *                     @OA\Property(property="age", type="integer", example=30),
 *                 ),
 *                 @OA\Property(
 *                     property="rating",
 *                     type="object",
 *                     @OA\Property(property="average", type="number", format="float", example=4.5),
 *                     @OA\Property(property="total_reviews", type="integer", example=12),
 *                 ),
 *             ),
 *         )
 *     ),
 *     @OA\Response(
 *         response=401,
 *         description="Unauthorized - Bukan recruiter atau user belum login",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Instant match hanya untuk recruiter")
 *         )
 *     ),
 *     @OA\Response(
 *         response=403,
 *         description="Forbidden - Email belum diverifikasi",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Email belum diverifikasi")
 *         )
 *     ),
 *     @OA\Response(
 *         response=404,
 *         description="Tidak ada worker yang tersedia",
 *         @OA\JsonContent(
 *             type="object",
 *             @OA\Property(property="status", type="boolean", example=false),
 *             @OA\Property(property="message", type="string", example="Belum ada worker yang tersedia.")
 *         )
 *     )
 * )
 */
    public function getInstantMatch(Request $request) {
        $user = auth()->user();
    
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User belum terautentikasi'
            ]);
        }
    
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => false,
                'message' => 'Email belum diverifikasi',
            ], 403);
        }
    
        if ($user->role !== 'Recruiter') {
            return response()->json([
                'status' => false,
                'message' => 'Instant match hanya untuk recruiter'
            ], 401);
        }
    
        // Ambil worker acak dan include relasi user-nya
        $worker = WorkerModel::with('user')->inRandomOrder()->first();
    
        if (!$worker) {
            return response()->json([
                'status' => false,
                'message' => 'Belum ada worker yang tersedia.',
            ], 404);
        }
    
        // Ambil data rating dari tabel RatingReviewModel
        $ratingStats = RatingReviewModel::select(
                DB::raw('AVG(rating) as average_rating'),
                DB::raw('COUNT(*) as total_reviews')
            )
            ->where('id_reviewed', $worker->user->id_user)
            ->first();
    
        return response()->json([
            'status' => true,
            'message' => 'Worker ditemukan secara acak!',
            'data' => [
                'id_worker' => $worker->id_worker,
                'bio' => $worker->bio,
                'skill' => $worker->skill,
                'experience_years' => $worker->experience_years,
                'location' => $worker->location,
                'expected_salary' => $worker->expected_salary,
                'availability' => $worker->availability,
                'profile_picture' => $worker->profile_picture,
                'user' => [
                    'fullname' => $worker->user->fullname,
                    'email' => $worker->user->email,
                    'phone' => $worker->user->phone,
                    'gender' => $worker->user->gender,
                    'age' => $worker->user->age,
                ],
                'rating' => [
                    'average' => round($ratingStats->average_rating, 1),
                    'total_reviews' => $ratingStats->total_reviews
                ]
            ]
        ]);
    }
}
