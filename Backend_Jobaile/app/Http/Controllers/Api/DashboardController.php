<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\RecruiterModel;
use App\Models\WorkerModel;

class DashboardController extends Controller
{
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
