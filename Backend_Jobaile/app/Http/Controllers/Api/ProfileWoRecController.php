<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Rule;
use Illuminate\Validation\Rules\Enum;
use App\Enums\Gender;
use Illuminate\Support\Facades\Storage;
use App\Models\RecruiterModel;
use App\Models\WorkerModel;


class ProfileWoRecController extends Controller
{
    public function posteworec(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ($user->role == 'Recruiter') {
                $validator = Validator::make($request->all(), [
                    'house_type' => 'nullable|string|max:100',
                    'family_size' => 'nullable|integer|min:1',
                    'location_address' => 'nullable|string|max:100',
                    'desc' => 'nullable|string',
                ]);
        
                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $user = RecruiterModel::create([
                    'id_recruiter' => $request->id_recruiter,
                    'id_user' => $user->id_user,
                    'house_type' => $request->house_type,
                    'family_size' => $request->family_size,
                    'location_address' => $request->location_address,
                    'desc' => $request->desc,
                ]);

                $uploadfolder = 'profile';
                $profile = $request->file('profile_picture');

                if ($profile && $profile->isValid()) {
                    $custom = $user->id_recruiter . '.' . $profile->getClientOriginalExtension();
                    $profile->storeAs($uploadfolder, $custom , 'public');
                } else {
                    $user->profile_picture = null;
                }

                $user->profile_picture = $custom;
                $user->save();

                return response()->json([
                    'status' => true,
                    'message' => 'user created successfully',
                    'data' => $user,
                ]);
            } else if ($user->role == 'Worker') {
                $validator = Validator::make($request->all(), [
                    'bio' => 'nullable|string',
                    'skill' => 'nullable|string',
                    'experience_years' => 'nullable|integer|min:0',
                    'location' => 'nullable|string|max:100',
                    'expected_salary' => 'nullable|integer|min:0',
                    'availability' => 'nullable|in:penuh_waktu,paruh_waktu,mingguan,bulanan',
                ]);
        
                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $user = WorkerModel::create([
                    'id_worker' => $request->id_worker,
                    'id_user' => $user->id_user,
                    'bio' => $request->bio,
                    'skill' => $request->skill,
                    'experience_years' => $request->experience_years,
                    'location' => $request->location,
                    'expected_salary' => $request->expected_salary,
                    'availability' => $request->availability,
                ]);

                $tempatnya = 'profile';
                $profile = $request->file('profile_picture');

                if ($profile && $profile->isValid()) {
                    $custom = $user->id_worker . '.' . $profile->getClientOriginalExtension();
                    $profile->storeAs($tempatnya, $custom, 'public');
                } else {
                    $user->profile_picture = null;
                }

                $user->profile_picture = $custom;
                $user->save();

                return response()->json([
                    'status' => true,
                    'message' => 'user created successfully',
                    'data' => $user,
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while fetching user profile',
                'error' => $e->getMessage(),
            ], 500);
        }

    }



    public function updateProfile(Request $request)
    {
        try {
            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'User not found',
                ], 404);
            }

            if ($user->role == 'Recruiter') {
                $validator = Validator::make($request->all(), [
                    'house_type' => 'required|string|max:100',
                    'family_size' => 'required|integer|min:1',
                    'location_address' => 'required|string|max:100',
                    'desc' => 'nullable|string',
                    'profile_picture' => 'nullable|image|max:2048',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $profile = RecruiterModel::where('id_user', $user->id_user)->first();
                if (!$profile) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Profile not found',
                    ], 404);
                }

                $data = $request->only(['house_type', 'family_size', 'location_address', 'desc']);

                if ($request->hasFile('profile_picture')) {
                    if ($profile->profile_picture) {
                        Storage::disk('public')->delete('profile/' . $profile->profile_picture);
                    }

                    $uploadfolder = 'profile';
                    $profile_uploaded_path = $request->file('profile_picture');
                    $filename = $profile->id_recruiter . '.' . $profile_uploaded_path->getClientOriginalExtension();
                    $profile_uploaded_path->storeAs($uploadfolder, $filename, 'public');

                    $data['profile_picture'] = $filename;
                }

                $profile->update($data);

                return response()->json([
                    'status' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $profile,
                ]);
            }

            // ------------------- Worker -------------------
            if ($user->role == 'Worker') {
                $validator = Validator::make($request->all(), [
                    'bio' => 'nullable|string',
                    'skill' => 'nullable|string',
                    'experience_years' => 'nullable|integer|min:0',
                    'location' => 'nullable|string|max:100',
                    'expected_salary' => 'nullable|integer|min:0',
                    'availability' => 'nullable|in:penuh_waktu,paruh_waktu,mingguan,bulanan',
                    'profile_picture' => 'nullable|image|max:2048',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors()
                    ], 422);
                }

                $profile = WorkerModel::where('id_user', $user->id_user)->first();
                if (!$profile) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Profile not found',
                    ], 404);
                }

                $data = $request->only(['bio', 'skill', 'experience_years', 'location', 'expected_salary', 'availability']);

                if ($request->hasFile('profile_picture')) {
                    if ($profile->profile_picture) {
                        Storage::disk('public')->delete('profile/' . $profile->profile_picture);
                    }

                    $uploadfolder = 'profile';
                    $profile_uploaded_path = $request->file('profile_picture');
                    $filename = $profile->id_worker . '.' . $profile_uploaded_path->getClientOriginalExtension();
                    $profile_uploaded_path->storeAs($uploadfolder, $filename, 'public');

                    $data['profile_picture'] = $filename;
                }

                $profile->update($data);

                return response()->json([
                    'status' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $profile,
                ]);
            }

            return response()->json([
                'status' => false,
                'message' => 'Invalid role',
            ], 403);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'An error occurred while updating profile',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

}
