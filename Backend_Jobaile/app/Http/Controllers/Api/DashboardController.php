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
    public function DashboardWoRec(Request $request) {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ($user->role == 'Recruiter') {
                $workers = WorkerModel::inRandomOrder()->with('user')->get()->map(function ($worker) {
                    $birthdate = $worker->user->birthdate ?? null;
                    $umur = $birthdate ? \Carbon\Carbon::parse($birthdate)->age : "Belum ada umur";

                    return [
                        'id_worker' => $worker->id_worker,
                        'bio' => $worker->user->bio ?? 'Belum ada bio',
                        'umur' => $umur,
                        'fullname' => $worker->user->fullname,
                        'profile_picture' => $worker->profile_picture,
                    ];
                });

                return response()->json([
                    'status' => true,
                    'message' => 'Worker found successfully',
                    'data' => $workers,
                ], 200);
                
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
