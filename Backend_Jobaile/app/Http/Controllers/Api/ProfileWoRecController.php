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
                    'house_type' => 'required|string|max:100',
                    'family_size' => 'required|integer|min:1',
                    'location_address' => 'required|string|max:100',
                    'desc' => 'nullable|string',
                ]);
        
                if ($validator->fails()) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Validation error',
                        'errors' => $validator->errors()
                    ], 422);
                }
        
                $uploadfolder = 'profile';

                $profile = $request->file('profile_picture');

                $profile_uploaded_path = $profile->store($uploadfolder, 'public');

                $uploadedImageResponse = array(
                    "image_name" => basename($profile_uploaded_path),
                    "image_url" => Storage::disk('public')->url($profile_uploaded_path),
                    "mime" => $profile->getClientMimeType()
                );

                $user = RecruiterModel::create([
                    'id_recruiter' => $request->id_recruiter,
                    'id_user' => $user->id_user,
                    'house_type' => $request->house_type,
                    'family_size' => $request->family_size,
                    'location_address' => $request->location_address,
                    'desc' => $request->desc,
                ]);

                return response()->json([
                    'status' => true,
                    'message' => 'user created successfully',
                    'data' => $user,
                    'profile_picture' => $uploadedImageResponse,
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
        
                $uploadfolder = 'profile';

                $profile = $request->file('profile_picture');

                $profile_uploaded_path = $profile->store($uploadfolder, 'public');

                $uploadedImageResponse = array(
                    "image_name" => basename($profile_uploaded_path),
                    "image_url" => Storage::disk('public')->url($profile_uploaded_path),
                    "mime" => $profile->getClientMimeType()
                );

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

                return response()->json([
                    'status' => true,
                    'message' => 'user created successfully',
                    'data' => $user,
                    'profile_picture' => $uploadedImageResponse,
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
                    'profile_picture' => 'nullable|image|max:2048', // optional upload validation
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

                $profile->house_type = $request->house_type;
                $profile->family_size = $request->family_size;
                $profile->location_address = $request->location_address;
                $profile->desc = $request->desc;

                if ($request->hasFile('profile_picture')) {
                    $uploadfolder = 'profile';
                    $profile_uploaded_path = $request->file('profile_picture')->store($uploadfolder, 'public');
                    $profile->profile_picture = basename($profile_uploaded_path);

                    $uploadedImageResponse = [
                        "image_name" => basename($profile_uploaded_path),
                        "image_url" => Storage::disk('public')->url($profile_uploaded_path),
                        "mime" => $request->file('profile_picture')->getClientMimeType()
                    ];
                } else {
                    $uploadedImageResponse = null;
                }

                $profile->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $profile,
                    'profile_picture' => $uploadedImageResponse,
                ]);
            } else if ($user->role == 'Worker') {
                $validator = Validator::make($request->all(), [
                    'bio' => 'nullable|string',
                    'skill' => 'nullable|string',
                    'experience_years' => 'nullable|integer|min:0',
                    'location' => 'nullable|string|max:100',
                    'expected_salary' => 'nullable|integer|min:0',
                    'availability' => 'nullable|in:penuh_waktu,paruh_waktu,mingguan,bulanan',
                    'profile_picture' => 'nullable|image|max:2048', // optional upload validation
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

                $profile->bio = $request->bio;
                $profile->skill = $request->skill;
                $profile->experience_years = $request->experience_years;
                $profile->location = $request->location;
                $profile->expected_salary = $request->expected_salary;
                $profile->availability = $request->availability;

                if ($request->hasFile('profile_picture')) {
                    $uploadfolder = 'profile';
                    $profile_uploaded_path = $request->file('profile_picture')->store($uploadfolder, 'public');
                    $profile->profile_picture = basename($profile_uploaded_path);

                    $uploadedImageResponse = [
                        "image_name" => basename($profile_uploaded_path),
                        "image_url" => Storage::disk('public')->url($profile_uploaded_path),
                        "mime" => $request->file('profile_picture')->getClientMimeType()
                    ];
                } else {
                    $uploadedImageResponse = null;
                }

                $profile->save();

                return response()->json([
                    'status' => true,
                    'message' => 'Profile updated successfully',
                    'data' => $profile,
                    'profile_picture' => $uploadedImageResponse,
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
