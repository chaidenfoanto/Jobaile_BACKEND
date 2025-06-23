<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RatingReviewModel;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class RatingReviewController extends Controller
{
    /**
 * @OA\Post(
 *     path="/api/review/{id_reviewed}",
 *     summary="Beri atau update rating",
 *     tags={"RatingReview"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id_reviewed",
 *         in="path",
 *         description="ID user yang diberi rating",
 *         required=true,
 *         @OA\Schema(type="string", example="USR123")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"rating"},
 *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=5),
 *             @OA\Property(property="ulasan", type="string", example="Kerja bagus dan profesional.")
 *         )
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Berhasil menyimpan rating",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Rating dan/atau ulasan berhasil disimpan."),
 *             @OA\Property(property="data", type="object")
 *         )
 *     ),
 *     @OA\Response(response=400, description="Tidak bisa memberi rating ke diri sendiri atau sesama role"),
 *     @OA\Response(response=401, description="Belum terautentikasi"),
 *     @OA\Response(response=403, description="Email belum diverifikasi"),
 *     @OA\Response(response=404, description="User tidak ditemukan"),
 *     @OA\Response(response=422, description="Validasi gagal"),
 * )
 *
 * @OA\Patch(
 *     path="/api/review/{id_reviewed}",
 *     summary="Update rating jika sudah ada",
 *     tags={"RatingReview"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id_reviewed",
 *         in="path",
 *         description="ID user yang diberi rating",
 *         required=true,
 *         @OA\Schema(type="string", example="USR123")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"rating"},
 *             @OA\Property(property="rating", type="integer", minimum=1, maximum=5, example=3),
 *             @OA\Property(property="ulasan", type="string", example="Cukup membantu, perlu ditingkatkan.")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Rating berhasil diperbarui"),
 *     @OA\Response(response=400, description="Tidak valid memberi rating"),
 *     @OA\Response(response=401, description="Tidak terautentikasi"),
 *     @OA\Response(response=403, description="Email belum diverifikasi"),
 *     @OA\Response(response=404, description="User tidak ditemukan"),
 *     @OA\Response(response=422, description="Validasi gagal"),
 * )
 */

    public function kasihrating(Request $request, $id_reviewed)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Anda belum terautentikasi'
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => false,
                'message' => 'Email belum diverifikasi. Silakan verifikasi email terlebih dahulu.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'rating' => 'required|integer|between:1,5',  // ubah jadi required agar wajib isi rating valid
            'ulasan' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $idReviewer = $user->id_user;

        if ($idReviewer == $id_reviewed) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak bisa memberikan rating kepada diri sendiri.'
            ], 400);
        }

        $reviewedUser = User::where('id_user', $id_reviewed)->first();

        if (!$reviewedUser) {
            return response()->json([
                'status' => false,
                'message' => 'User yang akan diberi rating tidak ditemukan.'
            ], 404);
        }

        // Pastikan role berbeda (case insensitive)
        if (strtolower($user->role) === strtolower($reviewedUser->role)) {
            return response()->json([
                'status' => false,
                'message' => 'Tidak bisa memberi rating kepada sesama ' . $user->role
            ], 400);
        }

        // Simpan atau perbarui rating
        $rating = RatingReviewModel::firstOrNew([
            'id_reviewer' => $idReviewer,
            'id_reviewed' => $id_reviewed,
        ]);
        
        $rating->rating = $request->rating;
        $rating->ulasan = $request->ulasan;
        $rating->tanggal_rating = now();
        $rating->role = strtolower($user->role);
        $rating->save();
        

        return response()->json([
            'status' => true,
            'message' => 'Rating dan/atau ulasan berhasil disimpan.',
            'data' => $rating
        ]);
    }

    /**
 * @OA\Get(
 *     path="/api/review/{id_reviewed}",
 *     summary="Lihat semua rating yang diterima user tertentu",
 *     tags={"RatingReview"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="id_reviewed",
 *         in="path",
 *         description="ID user yang ingin dilihat ratingnya",
 *         required=true,
 *         @OA\Schema(type="string", example="USR123")
 *     ),
 *     @OA\Parameter(
 *         name="rating",
 *         in="query",
 *         description="Filter rating (opsional, 1-5)",
 *         required=false,
 *         @OA\Schema(type="integer", enum={1,2,3,4,5})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Berhasil mengambil data rating",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Berhasil mengambil data rating."),
 *             @OA\Property(property="data", type="array", @OA\Items(
 *                 @OA\Property(property="reviewer_nama", type="string", example="Andi Budi"),
 *                 @OA\Property(property="rating", type="integer", example=4),
 *                 @OA\Property(property="ulasan", type="string", example="Sangat baik"),
 *                 @OA\Property(property="tanggal_rating", type="string", example="2025-06-23 10:12"),
 *                 @OA\Property(property="role", type="string", example="worker")
 *             ))
 *         )
 *     ),
 *     @OA\Response(response=401, description="Belum login"),
 *     @OA\Response(response=403, description="Email belum diverifikasi"),
 *     @OA\Response(response=404, description="User tidak ditemukan"),
 * )
 */

    public function lihatrating(Request $request, $id_reviewed)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Belum terautentikasi'
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => false,
                'message' => 'Email belum diverifikasi. Silakan verifikasi email terlebih dahulu.',
            ], 403);
        }

        $reviewedUser = User::where('id_user', $id_reviewed)->first();

        if (!$reviewedUser) {
            return response()->json([
                'status' => false,
                'message' => 'User yang dimaksud tidak ditemukan.'
            ], 404);
        }

        // Ambil filter rating dari query string (jika ada)
        $filterRating = $request->query('rating');

        $query = RatingReviewModel::with('reviewer')
            ->where('id_reviewed', $id_reviewed);

        if (in_array($filterRating, [1, 2, 3, 4, 5])) {
            $query->where('rating', (int)$filterRating);
        }

        $ratings = $query->get();

        if ($ratings->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Belum ada yang melakukan rating dengan kriteria tersebut.',
                'data' => []
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data rating.',
            'data' => $ratings->map(function ($item) {
                return [
                    'reviewer_nama' => $item->reviewer->fullname ?? 'Tidak diketahui',
                    'rating' => $item->rating,
                    'ulasan' => $item->ulasan,
                    'tanggal_rating' => $item->tanggal_rating->format('Y-m-d H:i'),
                    'role' => $item->role
                ];
            })
        ]);
    }

    /**
 * @OA\Get(
 *     path="/api/review",
 *     summary="Lihat semua rating yang saya terima",
 *     tags={"RatingReview"},
 *     security={{"bearerAuth":{}}},
 *     @OA\Parameter(
 *         name="rating",
 *         in="query",
 *         description="Filter berdasarkan rating (1-5)",
 *         required=false,
 *         @OA\Schema(type="integer", enum={1,2,3,4,5})
 *     ),
 *     @OA\Response(
 *         response=200,
 *         description="Berhasil mengambil data rating saya",
 *         @OA\JsonContent(
 *             @OA\Property(property="status", type="boolean", example=true),
 *             @OA\Property(property="message", type="string", example="Berhasil mengambil data rating Anda."),
 *             @OA\Property(property="data", type="array", @OA\Items(
 *                 @OA\Property(property="reviewer_nama", type="string", example="Dewi Kartika"),
 *                 @OA\Property(property="rating", type="integer", example=5),
 *                 @OA\Property(property="ulasan", type="string", example="Top performer"),
 *                 @OA\Property(property="tanggal_rating", type="string", example="2025-06-23 09:01"),
 *                 @OA\Property(property="role", type="string", example="recruiter")
 *             ))
 *         )
 *     ),
 *     @OA\Response(response=401, description="Belum login"),
 *     @OA\Response(response=403, description="Email belum diverifikasi"),
 * )
 */

    public function lihatratingSaya(Request $request)
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Belum terautentikasi'
            ], 401);
        }

        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'status' => false,
                'message' => 'Email belum diverifikasi. Silakan verifikasi email terlebih dahulu.',
            ], 403);
        }

        $filterRating = $request->query('rating');

        $query = RatingReviewModel::with('reviewer')
            ->where('id_reviewed', $user->id_user);

        if (in_array((int)$filterRating, [1, 2, 3, 4, 5])) {
            $query->where('rating', (int)$filterRating);
        }

        $ratings = $query->get();

        if ($ratings->isEmpty()) {
            return response()->json([
                'status' => true,
                'message' => 'Belum ada yang memberikan rating untuk Anda.',
                'data' => []
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Berhasil mengambil data rating Anda.',
            'data' => $ratings->map(function ($item) {
                return [
                    'reviewer_nama' => $item->reviewer->fullname ?? 'Tidak diketahui',
                    'rating' => $item->rating,
                    'ulasan' => $item->ulasan,
                    'tanggal_rating' => $item->tanggal_rating->format('Y-m-d H:i'),
                    'role' => $item->role
                ];
            })
        ]);
    }

}
