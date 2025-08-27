<?php

// namespace App\Http\Middleware;

// use Closure;
// use Illuminate\Http\Request;
// use Laravel\Sanctum\PersonalAccessToken;
// use Carbon\Carbon;

// class ExpireOldTokens
// {
//     public function handle(Request $request, Closure $next)
//     {
//         $tokenString = $request->bearerToken();

//         if ($tokenString) {
//             $accessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($tokenString);

//             if ($accessToken) {
//                 $lastUsedAt = $accessToken->last_used_at ?? $accessToken->created_at;
//                 $expiresAt = $lastUsedAt->copy()->addDays(15);

//                 if (now()->greaterThan($expiresAt)) {
//                     $accessToken->delete();

//                     return response()->json([
//                         'message' => 'Token expired due to inactivity.'
//                     ], 401);
//                 }

//                 // Only update after checking expiration
//                 $accessToken->forceFill(['last_used_at' => now()])->save();
//             }
//         }

//         return $next($request);
//     }
// }
