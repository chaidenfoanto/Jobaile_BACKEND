<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Job_OfferModel;
use App\Models\RecruiterModel;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\PasswordResetServiceProvider;
use Illuminate\Support\Facades\PasswordBroker;
use Illuminate\Support\Facades\PasswordReset;

class Job_OfferController extends Controller
{
    /**
 * @OA\Post(
 *     path="/api/job_offer",
 *     summary="Recruiter membuat job offer baru",
 *     tags={"Job Offer"},
 *     security={{"sanctum":{}}},
 *     @OA\RequestBody(
 *         required=true,
 *         @OA\JsonContent(
 *             required={"job_title", "desc"},
 *             @OA\Property(property="job_title", type="string", example="Asisten Rumah Tangga"),
 *             @OA\Property(property="desc", type="string", example="Bersih-bersih dan masak setiap hari."),
 *             @OA\Property(property="status", type="string", enum={"open", "closed"}, example="open")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Job offer berhasil dibuat"),
 *     @OA\Response(response=401, description="Unauthorized / belum login"),
 *     @OA\Response(response=403, description="Email belum diverifikasi / bukan recruiter"),
 *     @OA\Response(response=422, description="Validasi gagal"),
 *     @OA\Response(response=500, description="Kesalahan server")
 * )
 */

    public function PostJob_Offer(Request $request) {
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
                    'message' => 'Cuma recruiter yang bisa post job_offer'
                ]);
            }

            $validator = Validator::make($request->all(), [
                'job_title' => 'required|string|max:100',
                'desc' => 'required|string',
                'status' => 'in:open,closed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'errors'  => $validator->errors()
                ], 422);
            }

            $recruiter = RecruiterModel::where('id_user', $user->id_user)
                    ->first();

            if (!$recruiter) {
                return response()->json([
                    'status' => false,
                    'message' => 'Recruiter tdk ada'
                ]);
            }

            $data = Job_OfferModel::create([
                'id_recruiter' => $recruiter->id_recruiter,
                'job_title'    => $request->job_title,
                'desc'         => $request->desc,
                'status'       => $request->status ?? 'open',
            ]);

            return response()->json([
                'status' => true,
                'message' => 'Review berhasil disimpan',
                'data'      => $data,
                'fullname' => $user->fullname,
                'id_user' => $user->id_user
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
 * @OA\Put(
 *     path="/api/job_offer/{id}",
 *     summary="Recruiter memperbarui job offer miliknya",
 *     tags={"Job Offer"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID job offer yang ingin diupdate",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\RequestBody(
 *         @OA\JsonContent(
 *             @OA\Property(property="job_title", type="string", example="ART Harian"),
 *             @OA\Property(property="desc", type="string", example="3x seminggu, bantu cuci dan setrika"),
 *             @OA\Property(property="status", type="string", enum={"open", "closed"}, example="closed")
 *         )
 *     ),
 *     @OA\Response(response=200, description="Job offer berhasil diperbarui"),
 *     @OA\Response(response=401, description="Belum login"),
 *     @OA\Response(response=403, description="Bukan recruiter / belum verifikasi email"),
 *     @OA\Response(response=404, description="Job offer tidak ditemukan"),
 *     @OA\Response(response=422, description="Validasi gagal")
 * )
 */

    public function PutJob_Offer(Request $request, $id)
    {
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
                    'message' => 'Cuma recruiter yang bisa update job_offer'
                ], 403);
            }

            // Validasi data yang akan diubah
            $validator = Validator::make($request->all(), [
                'job_title' => 'nullable|required|string|max:100',
                'desc'      => 'nullable|required|string',
                'status'    => 'nullable|in:open,closed'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status'  => false,
                    'errors'  => $validator->errors()
                ], 422);
            }

            $recruiter = RecruiterModel::where('id_user', $user->id_user)
                    ->first();

            // Pastikan job ini milik recruiter yang login
            $job = Job_OfferModel::where('id_job', $id)
                ->where('id_recruiter', $recruiter->id_recruiter)
                ->first();

            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => 'Job offer tidak ditemukan atau bukan milik Anda',
                ], 404);
            }

            // Update jika ada perubahan
            if ($request->has('job_title')) $job->job_title = $request->job_title;
            if ($request->has('desc')) $job->desc = $request->desc;
            if ($request->has('status')) $job->status = $request->status;

            $job->save();

            return response()->json([
                'status' => true,
                'message' => 'Job offer berhasil diperbarui',
                'data' => $job
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
 *     path="/api/dashboardofferrecruiter/{id}",
 *     summary="Worker melihat detail job offer dari recruiter",
 *     tags={"Job Offer"},
 *     security={{"sanctum":{}}},
 *     @OA\Parameter(
 *         name="id",
 *         in="path",
 *         required=true,
 *         description="ID dari job offer",
 *         @OA\Schema(type="integer")
 *     ),
 *     @OA\Response(response=200, description="Detail job offer ditemukan"),
 *     @OA\Response(response=401, description="Unauthorized"),
 *     @OA\Response(response=403, description="Email belum diverifikasi / bukan worker"),
 *     @OA\Response(response=404, description="Job offer tidak ditemukan")
 * )
 */

    public function DetailOffer($id) {
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
    
            if ($user->role !== 'Worker') {
                return response()->json([
                    'status' => false,
                    'message' => 'Cuma worker yang bisa update job_offer'
                ], 403);
            }
    
            $job = Job_OfferModel::where('id_job', $id)
                    ->with('recruiter.user') // eager load recruiter dan user terkait
                    ->first();
    
            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => 'Job_Offer tidak ditemukan.'
                ], 404);
            }
    
            return response()->json([
                'status' => true,
                'message' => 'Detail worker found successfully',
                'data' => [
                    'id_job'      => $job->id_job,
                    'job_title'   => $job->job_title,
                    'desc'        => $job->desc,
                    'status'      => $job->status,
                    'recruiter'   => [
                        'id_recruiter'     => $job->recruiter->id_recruiter,
                        'house_type'       => $job->recruiter->house_type,
                        'family_size'      => $job->recruiter->family_size,
                        'location_address' => $job->recruiter->location_address,
                        'desc'             => $job->recruiter->desc,
                        'profile_picture'  => $job->recruiter->profile_picture,
                        'user'             => [
                            'id_user'   => $job->recruiter->user->id_user ?? null,
                            'name'      => $user->fullname ?? null,
                            'email'     => $job->recruiter->user->email ?? null,
                            'phone'     => $job->recruiter->user->phone ?? null,
                            'role'      => $job->recruiter->user->role ?? null,
                        ]
                    ],
                ]
            ], 200);
    
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage()
            ]);
        }
    }    

    public function DeleteJob_Offer($id)
    {
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
                    'message' => 'Cuma recruiter yang bisa menghapus job_offer'
                ], 403);
            }

            $job = Job_OfferModel::where('id_job', $id)
                ->where('id_recruiter', $user->id_user)
                ->first();

            if (!$job) {
                return response()->json([
                    'status' => false,
                    'message' => 'Job offer tidak ditemukan atau bukan milik Anda'
                ], 404);
            }

            // Hapus relasi terkait terlebih dahulu
            $job->matchmakings()->delete();
            $job->contracts()->delete();

            // Hapus job itu sendiri
            $job->delete();

            return response()->json([
                'status' => true,
                'message' => 'Job offer berhasil dihapus beserta relasinya'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan saat menghapus job offer',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
