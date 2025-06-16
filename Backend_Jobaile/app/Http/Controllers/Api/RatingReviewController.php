<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RatingReviewModel;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class RatingReviewController extends Controller
{
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
