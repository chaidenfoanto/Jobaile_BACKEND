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
