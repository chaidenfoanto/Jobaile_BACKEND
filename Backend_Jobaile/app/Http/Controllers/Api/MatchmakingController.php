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
