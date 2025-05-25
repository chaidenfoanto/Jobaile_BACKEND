<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\Profilecontroller;
use App\Http\Controllers\Api\ProfileWoRecController;
use App\Http\Controllers\Api\DashboardController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\User;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Storage;

/*
|--------------------------------------------------------------------------
| Auth Routes (Public)
|--------------------------------------------------------------------------
*/
Route::post('/registerrecruiter', [AuthController::class, 'registerRecruiter']);
Route::post('/registerworker', [AuthController::class, 'registerWorker']);
Route::post('/login', [AuthController::class, 'login']);

/*
|--------------------------------------------------------------------------
| Email Verification Routes
|--------------------------------------------------------------------------
*/
// 🔗 Verifikasi dari link email
use Illuminate\Support\Facades\Log;

Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {
    $user = User::where('id_user', $id)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    if (! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
        return response()->json(['message' => 'Invalid verification link'], 403);
    }

    if ($user->hasVerifiedEmail()) {
        return response()->json(['message' => 'Email already verified']);
    }

    $user->markEmailAsVerified();
    $user->is_verified = true; // Set user as active after verification
    $user->save();

    return response()->json(['message' => 'Email verified successfully']);
})->middleware(['signed'])->name('verification.verify');


// 🔁 Kirim ulang email verifikasi
Route::post('/email/verification-notification', function (Request $request) {
    $request->user()->sendEmailVerificationNotification();
    return response()->json(['message' => 'Verification link sent']);
})->middleware('throttle:6,1')->name('verification.send');

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/
Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [Profilecontroller::class, 'getProfile']);
    Route::post('/postworec', [ProfileWoRecController::class, 'postworec']);
    Route::post('/putworec', [ProfileWoRecController::class, 'updateProfile']);
    Route::get('/dashboardworec', [DashboardController::class, 'DashboardWoRec']);

    // ❓ Cek status email sudah terverifikasi atau belum
    Route::get('/email-status', function (Request $request) {
        return response()->json([
            'email_verified' => $request->user()->hasVerifiedEmail(),
        ]);
    });
});
