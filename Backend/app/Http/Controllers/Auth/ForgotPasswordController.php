<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Carbon\Carbon;
use OwenIt\Auditing\Models\Audit;

class ForgotPasswordController extends Controller
{
    public function sendOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $otp = rand(100000, 999999); // 6-digit OTP

        DB::table('password_resets')->updateOrInsert(
            ['email' => $request->email],
            [
                'otp' => $otp,
                'expires_at' => Carbon::now()->addMinutes(5), //otp valid for 5 minutes
                'created_at' => now()
            ]
        );

        // Send OTP via email directly (no view or mailable)
        $subject = 'Your OTP for FOLLOWUP App Password Reset';

        $htmlMessage = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>$subject</title>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; background-color: #f9f9f9; padding: 20px;'>
    <div style='max-width: 600px; margin: auto; background-color: #fff; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);'>
        <strong>Dear user,</strong>

        <p>Your One-Time Password (OTP) for resetting your password is:</p>

        <h1 style='text-align: center; font-size: 36px; color: #2c3e50;'>$otp</h1>

        <p>This OTP is valid for 5 minutes. Please do not share it with anyone.</p>

        <p>If you did not request a password reset, please ignore this message.</p>

        <p?>Thank you,</p><br><strong>FOLLOWUP Team</strong>
    </div>
</body>
</html>
";

        Mail::html($htmlMessage, function ($mail) use ($request, $subject) {
            $mail->to($request->email)
                ->subject($subject);
        });



        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully to your email.',
            // 'otp' => $otp // ❗Only include in dev/test — remove in production
        ], 200);
    }

    public function verifyOTP(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp'   => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $otpEntry = DB::table('password_resets')
            ->where('email', $request->email)
            ->where('otp', $request->otp)
            ->first();

        if (!$otpEntry) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid OTP.'
            ], 400);
        }

        if (Carbon::now()->greaterThan(Carbon::parse($otpEntry->expires_at))) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired.'
            ], 410); // HTTP 410 Gone
        }

        // ✅ Mark as verified
        DB::table('password_resets')
            ->where('email', $request->email)
            ->update(['verified_at' => now()]);

        return response()->json([
            'success' => true,
            'message' => 'OTP verified successfully.'
        ], 200);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|exists:users,email',
            'password' => [
                'required',
                'string',
                'min:8',
                'same:confirm_password',
                'regex:/[a-z]/',
                'regex:/[A-Z]/',
                'regex:/[0-9]/',
                'regex:/[@$!%*#?&]/',
            ],
            'confirm_password' => 'required|string|min:6|max:20',
        ], [
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.same' => 'Password and Confirm Password must match.',
            'password.regex' => 'Password must include: one uppercase, lowercase, number, and special character.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $validator->errors()
            ], 422);
        }

        $otpEntry = DB::table('password_resets')
            ->where('email', $request->email)
            ->first();

        // Check if verified_at is set, else deny reset
        if (!$otpEntry || !$otpEntry->verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'OTP not verified. Cannot reset password.'
            ], 403);
        }

        // (Optional) Check if verified_at is still valid (e.g., within 5 mins)
        if (Carbon::now()->greaterThan(Carbon::parse($otpEntry->verified_at)->addMinutes(10))) {
            return response()->json([
                'success' => false,
                'message' => 'Verification expired. Please request a new OTP.'
            ], 410);
        }

        $user = User::where('email', $request->email)->first();
        $user->password = Hash::make($request->password);
        $user->save();
        // Delete the OTP and verification record
        DB::table('password_resets')->where('email', $request->email)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successful.'
        ], 200);
    }
}

//     public function resetPassword(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             'email'    => 'required|email|exists:users,email',
//             'otp'      => 'required|digits:6',
//             'password' => [
//                 'required',
//                 'string',
//                 'min:8',
//                 'same:confirm_password',
//                 'regex:/[a-z]/',      // at least one lowercase letter
//                 'regex:/[A-Z]/',      // at least one uppercase letter
//                 'regex:/[0-9]/',      // at least one digit
//                 'regex:/[@$!%*#?&]/', // at least one special character
//             ],
//             'confirm_password' => 'required|string|min:6|max:20',
//         ], [
//             // Custom messages ONLY for password field
//             'password.required' => 'Password is required.',
//             'password.min' => 'Password must be at least 8 characters.',
//             'password.same' => 'Password and Confirm Password must match.',
//             'password.regex' => 'Password must include: one uppercase letter, lowercase letter, number, and special character.',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Validation failed.',
//                 'errors' => $validator->errors()
//             ], 422);
//         }

//         $otpEntry = DB::table('password_resets')
//             ->where('email', $request->email)
//             ->where('otp', $request->otp)
//             ->first();

//         if (!$otpEntry) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Invalid OTP.'
//             ], 400);
//         }

//         if (Carbon::now()->greaterThan(Carbon::parse($otpEntry->expires_at))) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'OTP has expired.'
//             ], 410);
//         }

//         $user = User::where('email', $request->email)->first();
//         $user->password = Hash::make($request->password);
//         $user->save();

//         // Delete the OTP after successful reset
//         DB::table('password_resets')->where('email', $request->email)->delete();

//         return response()->json([
//             'success' => true,
//             'message' => 'Password reset successful.'
//         ], 200);
//     }
// }
