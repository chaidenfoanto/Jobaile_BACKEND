<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class Profilecontroller extends Controller
{
    public function profileuser(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }

            $userProfile = [
                'fullname' => $user->fullname,
                'email' => $user->email,
                'phone' => $user->phone,
                'ktp_card_path' => $user->ktp_card_path
            ];

            return response()->json([
                'status' => true,
                'message' => 'User profile fetched successfully',
                'data' => $userProfile,
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching user profile',
                'error' => $e->getMessage(),
            ], 500);
        }

    }
}
