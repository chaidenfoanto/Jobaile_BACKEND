<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job_OfferModel;
use App\Models\MatchmakingModel;

class MatchmakingController extends Controller
{
    private function cekDanUpdateStatusMatch($match)
    {
        if (
            $match->status_worker === 'accepted' &&
            $match->status_recruiter === 'accepted'
        ) {
            $match->status = 'accepted';
            $match->matched_at = now();
            $match->save();
        }
    }

    /**
 * @OA\Post(
 *     path="/api/accbyworker/{id}",
 *     summary="Worker menerima job offer",
 *     tags={"Matchmaking"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         description="ID job offer yang ingin diterima",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="Job offer diterima, menunggu konfirmasi recruiter"),
 *     @OA\Response(response=401, description="Belum login / email belum diverifikasi"),
 *     @OA\Response(response=403, description="Hanya worker yang dapat menerima job"),
 *     @OA\Response(response=404, description="Job offer tidak valid atau sudah ditutup")
 * )
 */

    public function Jobofferaccbyworker($id)
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Autentikasi atau verifikasi email dibutuhkan.'
                ], 401);
            }

            if ($user->role !== 'Worker') {
                return response()->json([
                    'status' => false,
                    'message' => 'Hanya Worker yang bisa menerima job offer.'
                ], 403);
            }

            $job = Job_OfferModel::find($id);
            if (!$job || $job->status !== 'open') {
                return response()->json([
                    'status' => false,
                    'message' => 'Job offer tidak valid atau sudah ditutup.'
                ]);
            }

            // Ambil atau buat record match baru
            $match = MatchmakingModel::firstOrNew([
                'id_worker' => $user->workerProfile->id_worker,
                'id_job' => $id
            ]);

            // Update hanya status_worker saja
            $match->id_recruiter = $job->id_recruiter;
            $match->status_worker = 'accepted';
            $match->status_recruiter = $match->status_recruiter ?? 'pending';
            $match->status = 'pending';
            $match->matched_at = null;
            $match->save();

            $this->cekDanUpdateStatusMatch($match);

            return response()->json([
                'status' => true,
                'message' => 'Kamu telah menerima job offer ini. Menunggu konfirmasi recruiter.',
                'match' => $match
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
 * @OA\Post(
 *     path="/api/matchaccbyrecruiterorrequestbyrecruiter/{id_job}",
 *     summary="Recruiter mengirimkan tawaran ke worker",
 *     tags={"Matchmaking"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id_job",
 *         in="path",
 *         description="ID dari job offer yang ingin ditawarkan",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"id_worker"},
 *             @OA\Property(property="id_worker", type="integer", example=12)
 *         )
 *     ),
 *     @OA\Response(response=200, description="Tawaran berhasil dikirim ke worker"),
 *     @OA\Response(response=401, description="Unauthorized atau email belum diverifikasi"),
 *     @OA\Response(response=403, description="Bukan recruiter"),
 *     @OA\Response(response=404, description="Job offer tidak ditemukan atau bukan milik recruiter")
 * )
 */

    public function Matchmakingaccbyrecruiter(Request $request, $id_job)
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Autentikasi atau verifikasi email dibutuhkan.'
                ], 401);
            }

            if ($user->role !== 'Recruiter') {
                return response()->json([
                    'status' => false,
                    'message' => 'Hanya Recruiter yang bisa menawarkan job.'
                ], 403);
            }

            $job = Job_OfferModel::where('id_job', $id_job)
                ->where('id_recruiter', $user->recruiterProfile->id_recruiter)
                ->first();

            if (!$job || $job->status !== 'open') {
                return response()->json([
                    'status' => false,
                    'message' => 'Job offer tidak valid atau bukan milik Anda.'
                ]);
            }

            $id_worker = $request->id_worker;
            if (!$id_worker) {
                return response()->json([
                    'status' => false,
                    'message' => 'ID Worker wajib diisi.'
                ]);
            }

            // Ambil atau buat record match baru
            $match = MatchmakingModel::firstOrNew([
                'id_worker' => $id_worker,
                'id_job' => $id_job
            ]);

            // Update hanya status_recruiter saja
            $match->id_recruiter = $user->recruiterProfile->id_recruiter;
            $match->status_recruiter = 'accepted';
            $match->status_worker = $match->status_worker ?? 'pending';
            $match->status = 'pending';
            $match->matched_at = null;
            $match->save();

            $this->cekDanUpdateStatusMatch($match);

            return response()->json([
                'status' => true,
                'message' => 'Tawaran telah dikirim ke worker. Menunggu respon mereka.',
                'match' => $match
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
 * @OA\Post(
 *     path="/api/match/tolak/{id_job}",
 *     summary="Tolak tawaran/match oleh worker atau recruiter",
 *     tags={"Matchmaking"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id_job",
 *         in="path",
 *         description="ID job offer yang ingin ditolak",
 *         required=true,
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="Match berhasil ditolak dan dihapus"),
 *     @OA\Response(response=401, description="Belum login atau email belum diverifikasi"),
 *     @OA\Response(response=404, description="Match tidak ditemukan")
 * )
 */

    public function TolakMatch(Request $request, $id_job)
    {
        try {
            $user = auth()->user();

            if (!$user || !$user->hasVerifiedEmail()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Autentikasi atau verifikasi email dibutuhkan.'
                ], 401);
            }

            $match = MatchmakingModel::where('id_job', $id_job)
                        ->where(function($query) use ($user) {
                            if ($user->role === 'Worker') {
                                $query->where('id_worker', $user->workerProfile->id_worker);
                            } elseif ($user->role === 'Recruiter') {
                                $query->where('id_recruiter', $user->recruiterProfile->id_recruiter);
                            }
                        })->first();

            if (!$match) {
                return response()->json([
                    'status' => false,
                    'message' => 'Match tidak ditemukan untuk user ini.'
                ]);
            }

            // Update status penolakan sesuai role
            if ($user->role === 'Worker') {
                $match->status_worker = 'rejected';
            } elseif ($user->role === 'Recruiter') {
                $match->status_recruiter = 'rejected';
            }

            $match->status = 'rejected';
            $match->matched_at = null;
            $match->delete();

            return response()->json([
                'status' => true,
                'message' => 'Match telah ditolak dan dihapus.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }


}
