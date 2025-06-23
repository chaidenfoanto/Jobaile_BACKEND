<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ContractModel;
use App\Models\ContractsModel; // Kalau ini yang dipakai
use App\Models\MatchmakingModel;
use App\Models\Job_OfferModel;

use Illuminate\Support\Carbon;

class ContractController extends Controller
{
        /**
     * @OA\Post(
     *     path="/api/contracts/{id}",
     *     summary="Recruiter membuat kontrak kerja dengan worker",
     *     tags={"Contract"},
     *     security={{"sanctum":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID dari worker yang akan dikontrak",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"id_job", "start_date", "end_date", "terms"},
     *             @OA\Property(property="id_job", type="string", example="job123"),
     *             @OA\Property(property="start_date", type="string", format="date", example="2025-07-01"),
     *             @OA\Property(property="end_date", type="string", format="date", example="2025-07-10"),
     *             @OA\Property(property="terms", type="string", example="Kontrak berlaku 10 hari dan dapat diperpanjang.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Kontrak berhasil dibuat",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Kontrak berhasil dibuat."),
     *             @OA\Property(
     *                 property="kontrak",
     *                 type="object",
     *                 @OA\Property(property="id_worker", type="string", example="worker123"),
     *                 @OA\Property(property="id_recruiter", type="string", example="recruiter123"),
     *                 @OA\Property(property="id_job", type="string", example="job123"),
     *                 @OA\Property(property="start_date", type="string", example="2025-07-01"),
     *                 @OA\Property(property="end_date", type="string", example="2025-07-10"),
     *                 @OA\Property(property="status_pay", type="string", example="pending"),
     *                 @OA\Property(property="terms", type="string", example="Detail syarat dan ketentuan"),
     *                 @OA\Property(property="sign_at", type="string", format="date-time", example="2025-06-23T12:00:00Z")
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, description="User belum terautentikasi"),
     *     @OA\Response(response=403, description="Email belum diverifikasi atau bukan recruiter"),
     *     @OA\Response(response=404, description="Profil recruiter tidak ditemukan"),
     *     @OA\Response(response=422, description="Validasi gagal (format atau data tidak sesuai)"),
     *     @OA\Response(response=500, description="Kesalahan server")
     * )
     */
    public function ContractController(Request $request, $id) {
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
    
            if ($user->role !== 'Recruiter') {
                return response()->json([
                    'status' => false,
                    'message' => 'Cuma recruiter yang bisa membuat kontrak'
                ], 403);
            }

            $recruiter = $user->recruiterProfile; // atau $user->recruiterModel tergantung nama relasi

            if (!$recruiter) {
                return response()->json([
                    'status' => false,
                    'message' => 'Profil recruiter tidak ditemukan.'
                ], 404);
            }

            // Validasi input
            $request->validate([
                'id_job' => 'required|exists:job__offer_models,id_job',
                'start_date' => 'required|date|after_or_equal:today',
                'end_date' => 'required|date|after:start_date',
                'terms' => 'required|string'
            ]);

            $jobmatch = MatchmakingModel::where('id_job', $request->id_job)
                    ->where('id_worker', $id)
                    ->where('status', 'accepted')
                    ->first();

            if (!$jobmatch) {
                return response()->json([
                    'status' => false,
                    'message' => 'Tidak dapat membuat kontrak karena anda belum menjalin kerja sama dengan worker ini'
                ]);
            }

            $match = Job_OfferModel::where('id_job', $request->id_job)
                    ->where('id_recruiter', $recruiter->id_recruiter)
                    ->first();

            if (!$match) {
                return response()->json([
                    'status' => false,
                    'messaage' => 'Job offer tidak valid'
                ]);
            }

            $existing = ContractsModel::where('id_worker', $id)
                    ->where('id_recruiter', $recruiter->id_recruiter)
                    ->where('id_job', $request->id_job)
                    ->first();

            if ($existing) {
                return response()->json([
                    'status' => false,
                    'message' => 'Kontrak sudah pernah dibuat untuk kombinasi worker dan job ini.'
                ]);
            }

            // Buat kontrak baru
            $kontrak = ContractsModel::create([
                'id_worker' => $id,
                'id_recruiter' => $recruiter->id_recruiter,
                'id_job' => $request->id_job,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'status_pay' => 'pending',
                'terms' => $request->terms,
                'sign_at' => Carbon::now()
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Kontrak berhasil dibuat.',
                'kontrak' => $kontrak
            ]);
    
            // $contract = ContractsModel::where()    
        
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
