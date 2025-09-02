<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\api\UserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Api\FollowupController;
use App\Http\Controllers\Api\ReceiverController;
// use Illuminate\Foundation\Auth\EmailVerificationRequest; // 👈 Required for email verification
use Illuminate\Auth\Events\Verified;
use App\Models\User;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use Faker\Guesser\Name;

// use App\Http\Controllers\Auth\ResetPasswordController;
/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::post('/signup', [UserController::class, 'SignUp'])->name('signup');
// Route::post('/login', [UserController::class, 'Login'])->name('login');
Route::post('/register', [UserController::class, 'register'])->name('register');
Route::post('/verify-register-otp', [UserController::class, 'verifyRegisterOtp'])->name('verifyRegisterOtp');
Route::post('/resend-otp', [UserController::class, 'resendOTP'])->name('resendotp');
Route::post('/login', [UserController::class, 'Login'])->name('login');


// // 📧 Email Verification Routes

// // ✅ Send verification email using UserController method
// Route::post('/email/verify/send', [UserController::class, 'sendVerificationEmail'])->middleware(['auth:sanctum'])->name('verification.send');

// // ✅ Handle verification link
// // Route::get(...): Defines a GET route (used when clicking the verification link).
// // '/email/verify/{id}/{hash}': Dynamic URL with id (user ID) and hash (a hash of the user's email).
// // function (Request $request, $id, $hash): Inline closure to handle the route.
// Route::get('/email/verify/{id}/{hash}', function (Request $request, $id, $hash) {

//     // Retrieves the user by ID.
//     // If not found, Laravel throws a 404 error.
//     $user = User::findOrFail($id);

//     // Verifies the integrity of the link by checking if the hash from the URL matches the sha1 hash of the user's email.
//     // Uses hash_equals to prevent timing attacks.
//     // If mismatched, returns a 403 "Invalid verification link".
//     if (! hash_equals(sha1($user->email), $hash)) {
//         return response()->json(['message' => 'Invalid verification link.'], 403);
//     }

//     // Checks if the user already verified their email.
//     // If yes, responds with a simple message.
//     if ($user->hasVerifiedEmail()) {
//         return response()->json(['message' => 'Email already verified.']);
//     }

//     // Marks the email as verified in the database (typically updates email_verified_at timestamp).
//     $user->markEmailAsVerified();

//     // Fires the Verified event (used by Laravel to handle any post-verification actions like logging or notifications).
//     event(new Verified($user));

//     // ->middleware(['signed']): Ensures the verification link hasn't been tampered with (Laravel’s signed route validation).
//     return response()->json(['message' => 'Email verified successfully.']);
// })->middleware(['signed'])->name('verification.verify');

// // ✅ Check verification status
// // Defines a GET route to check if the authenticated user's email is verified.
// Route::get('/email/is-verified', function (Request $request) {

//     // Returns JSON with true or false based on verification status.
//     return response()->json(['verified' => $request->user()->hasVerifiedEmail()]);
// })->middleware(['auth:sanctum']);


// ✅ Protected Routes (only for authenticated & verified users)
// Route::middleware(['auth:sanctum', 'verified'])->group(function () {
// Route::middleware(['check.token_expiration', 'auth:sanctum'])->group(function () {
// Route::middleware(['auth:sanctum', 'role:admin|user'])->group(function () {
Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/profile', [UserController::class, 'profile'])->name('profile');
    // Route::get('/users', [UserController::class, 'AllUsers']);
    Route::get('/user/{user_id}', [UserController::class, 'getUserById'])->name('userbyid');
    Route::put('/user/update/{user_id}', [UserController::class, 'updateUser'])->name('updateuser');
    Route::post('/user/logout', [UserController::class, 'logout'])->name('logout');


    Route::post('/addfollowups', [FollowupController::class, 'AddFollowUp'])->name('addfollowup');
    Route::put('/followup/update/{task_id}', [FollowupController::class, 'UpdateFollowUp'])->name('updatefollowup');
    Route::get('/followups/user/{user_id}', [FollowupController::class, 'getFollowUpsByUserId'])->name('followupbyuserid');
    Route::delete('/followup/destroy/{id}', [FollowupController::class, 'Destroy'])->name('followupdelete');
    Route::get('/followups/receiver/{receiver_id}', [FollowupController::class, 'getFollowUpsByReceiverId'])->name('followpbyreceiverid');
    Route::get('/followups/monthly-followups', [FollowupController::class, 'getSurroundingMonthlyFollowups'])->name('monthlyfollowupcount');


    Route::post('/addreceivers', [ReceiverController::class, 'AddReceiver'])->name('addreceiver');
    Route::put('/receiver/update/{receiver_id}', [ReceiverController::class, 'UpdateReceiver'])->name('updatereceiver');
    Route::get('/receivers/user/{user_id}', [ReceiverController::class, 'getReceiversByUserId'])->name('getreceiverbyuserid');
    Route::get('/allreceivers', [ReceiverController::class, 'getAllReceivers'])->name('allreceivers');
    Route::delete('/receiver/destroy/{id}', [ReceiverController::class, 'destroy'])->name('receiverdelete');
});

//error handle like no user found
// Route::get('/Login', [UserController::class, 'Login'])->name('login');




// Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])->name('forgotpassword');
// Route::get('/reset-password-form/{token}', function ($token) {
//     // return view('auth.reset-password', ['token' => $token]);
// })->name('password.reset');

// // Route::get('/reset-password-form/{token}', function ($token, Request $request) {
// //     $email = $request->query('email');

// //     // Change to your actual frontend URL
// //     return redirect()->away("https://your-frontend.com/reset-password?token={$token}&email={$email}");
// // })->name('password.reset');


// Route::post('/reset-password', [ResetPasswordController::class, 'reset'])->name('resetpassword');

Route::post('/forgot-password', [ForgotPasswordController::class, 'sendOTP'])->middleware('throttle:2,1')->name('forgotpassword'); // ⬅️ 2 attempts per minute;
Route::post('/verify-otp', [ForgotPasswordController::class, 'verifyOTP'])->name('verifyotp');
Route::post('/reset-password', [ForgotPasswordController::class, 'resetPassword'])->name('resetpassword');


use App\Http\Controllers\FollowUpReportController;

Route::get('/export/save-summary', [FollowUpReportController::class, 'saveFullFollowupsWithSummary']);
