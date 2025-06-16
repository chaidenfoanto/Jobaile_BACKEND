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
use App\Http\Controllers\Api\RecruiterController;
use App\Http\Controllers\Api\RatingReviewController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\InstantMatchController;
use App\Http\Controllers\Api\Job_OfferController;
use App\Http\Controllers\Api\MatchmakingController;
use App\Http\Controllers\Api\ContractController;
use App\Http\Controllers\Api\DanaPaymentsController;

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
    Route::get('/test-relasi', function () {
        $user = \App\Models\User::with('recruiterProfile')->where('id_user', 'h0YcaSIsAxXi2dvEgxqy')->first();
        return $user->recruiterProfile;
    });
    
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/profile', [Profilecontroller::class, 'getProfile']); //sdh
    Route::post('/postworec', [ProfileWoRecController::class, 'postworec']); //sdh
    Route::post('/putworec', [ProfileWoRecController::class, 'updateProfile']); //sdh
    Route::get('/dashboardrecruiter', [DashboardController::class, 'DashboardRecruiter']); //sdh
    Route::get('/detailprofileworec/{id}', [DashboardController::class, 'DetailWorker']); //sdh

    Route::get('/dashboardworker', [DashboardController::class, 'DashboardWorker']); 
    Route::get('/dashboardofferrecruiter/{id}', [Job_OfferController::class, 'DetailOffer']); //sdh

    Route::post('/job_offer', [Job_OfferController::class, 'PostJob_Offer']); //sdh
    Route::put('/job_offer/{id}', [Job_OfferController::class, 'PutJob_Offer']); //sdh
    // Route::delete('/job_offer/{id}', [Job_OfferController::class, 'DeleteJob_Offer']); //kalau perlu saja

    // ❓ Cek status email sudah terverifikasi atau belum
    Route::get('/email-status', function (Request $request) {
        return response()->json([
            'email_verified' => $request->user()->hasVerifiedEmail(),
        ]);
    });

    Route::post('/accbyworker/{id}', [MatchmakingController::class, 'Jobofferaccbyworker']); //sdh
    Route::post('/match/tolak/{id_job}', [MatchmakingController::class, 'TolakMatch']); //sdh
    Route::post('/matchaccbyrecruiterorrequestbyrecruiter/{id_job}', [MatchmakingController::class, 'Matchmakingaccbyrecruiter']); //sdh

    Route::get('/searchworker', [RecruiterController::class, 'search']); //sdh
    Route::post('/chat/send/{id_receiver}', [ChatController::class, 'sendMessage']); //sdh
    Route::get('/chat/{id_user_b}', [ChatController::class, 'getMessages']); // ini yg dalam chat 
    Route::get('/getchat', [ChatController::class, 'getConversations']); // ini diluarnya

    Route::post('/review/{id_reviewed}', [RatingReviewController::class, 'kasihrating']); // sdh
    Route::patch('/review/{id_reviewed}', [RatingReviewController::class, 'kasihrating']); // sdh 
    Route::get('/review/{id_reviewed}', [RatingReviewController::class, 'lihatrating']); // sddh 
    Route::get('/review', [RatingReviewController::class, 'lihatratingSaya']); // sdh

    Route::get('/instantmatch', [InstantMatchController::class, 'getInstantMatch']); // sdh

    Route::post('/contracts/{id}', [ContractController::class, 'ContractController']); // sdh

    Route::post('/payments/qr/{contractId}', [DanaPaymentsController::class, 'createQrPayment']); // sdh
});

Route::post('/payments/callback', [DanaPaymentsController::class, 'handleCallback']);//sdh
