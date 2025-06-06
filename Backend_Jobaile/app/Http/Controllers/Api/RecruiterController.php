<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WorkerModel;
use App\Models\RatingReviewModel;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class RecruiterController extends Controller
{
    public function search(Request $request)
    {
        try {
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
                    'message' => 'Anda belum melakukan verifikasi email'
                ], 403);
            }

            $keyword = $request->input('keyword');

            if (!$keyword) {
                return response()->json([
                    'status' => false,
                    'message' => 'Keyword pencarian tidak boleh kosong'
                ], 400);
            }

            if (strlen($keyword) < 3) {
                return response()->json([
                    'status' => false,
                    'message' => 'Keyword pencarian harus minimal 3 karakter'
                ], 400);
            }

            // Query worker yang user-nya memiliki role 'worker' dan cocok dengan keyword
            $workers = WorkerModel::with('user')
                ->whereHas('user', function ($query) {
                    $query->where('role', 'worker');
                })
                ->where(function ($query) use ($keyword) {
                    $query->where('bio', 'like', "%{$keyword}%")
                        ->orWhere('skill', 'like', "%{$keyword}%")
                        ->orWhere('location', 'like', "%{$keyword}%")
                        ->orWhereHas('user', function ($q) use ($keyword) {
                            $q->where('fullname', 'like', "%{$keyword}%");
                        });
                })
                ->get();

            if ($workers->isEmpty()) {
                return response()->json([
                    'status' => true,
                    'message' => "Tidak ada worker dengan keyword: {$keyword}",
                    'data' => []
                ], 200); // tetap status 200 untuk respon sukses meski kosong
            }

            // Hitung rating per worker
            $workersWithRating = $workers->map(function ($worker) {
                $averageRating = \App\Models\RatingReviewModel::where('id_reviewed', $worker->id_user)
                    ->where('role', 'worker')
                    ->avg('rating');

                return [
                    'id_worker' => $worker->id_worker,
                    'id_user' => $worker->id_user,
                    'fullname' => $worker->user->fullname ?? '',
                    'bio' => $worker->bio,
                    'skill' => $worker->skill,
                    'location' => $worker->location,
                    'expected_salary' => $worker->expected_salary,
                    'availability' => $worker->availability,
                    'profile_picture' => $worker->profile_picture,
                    'rating' => round($averageRating ?? 0, 2)
                ];
            });

            return response()->json([
                'status' => true,
                'message' => 'Worker ditemukan',
                'data' => $workersWithRating
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
