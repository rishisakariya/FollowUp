<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Auth\Events\Registered; // <-- Make sure this is imported

class UserController extends Controller
{
    use AuthorizesRequests, ValidatesRequests;
    public function profile(Request $request)
    {
        // Return authenticated user details
        return response()->json([
            'success' => true,
            'message' => 'User profile fetched successfully.',
            'user' => $request->user(), // Laravel gets user from Sanctum token
        ], 200); //ok
    }
    public function signup(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|min:2',
            // 'email' => 'required|email|unique',
            'email' => 'required|email|unique:users,email', // unique:<table_name>,<column_name>
            // 'password' => 'required|string|min:8|same:confirm_password|regex:/[a-z]/|regex:/[A-Z]/',
            'password' => [
                'required',
                'string',
                'min:8',
                'same:confirm_password',
                'regex:/[a-z]/',      // at least one lowercase letter
                'regex:/[A-Z]/',      // at least one uppercase letter
                'regex:/[0-9]/',      // at least one digit
                'regex:/[@$!%*#?&]/', // at least one special character
            ],
            'confirm_password' => 'required|string|min:6|max:20',
        ], [
            // Custom messages ONLY for password field
            'password.required' => 'Password is required.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.same' => 'Password and Confirm Password must match.',
            'password.regex' => 'Password must include: one uppercase letter, lowercase letter, number, and special character.',
        ]);

        // Return errors if validation fails
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors'  => $validator->errors(),
            ], 422); // Unprocessable Entity
        }

        // Create user
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password), // Use hashing
        ]);

        // Fire the Registered event to send verification email
        // event(new Registered($user));
        // Create token for API authentication
        // $token = $user->createToken('register_token')->plainTextToken;


        //return response
        return response()->json([
            'success' => true,
            'message' => 'User registered successfully. Please verify your email.',
            // 'token' => $token,
            'user' => [
                'user_id' => $user->user_id,
                'name'  => $user->name,
                'email' => $user->email,
            ]
        ], 201); // Created
    }


    public function login(Request $request)
    {
        // Validate input (no 'exists' to avoid info leak)
        $validator = Validator::make($request->all(), [
            'email'    => 'required|email|max:100',
            'password' => 'required|string',
        ], [
            'email.required'    => 'Email is required.',
            'email.email'       => 'Invalid email format.',
            'password.required' => 'Password is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors(),
            ], 422); // Unprocessable Entity
        }

        // Find user by email
        $user = User::where('email', $request->email)->first();

        // Check user exists & password matches
        if (!$user || !Hash::check($request->password, $user->password)) {
            // Generic error to avoid leaking info
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        // Check if email is verified
        // if (!$user->hasVerifiedEmail()) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Please verify your email before logging in.',
        //     ], 403); // Forbidden
        // }

        // Create Sanctum token
        $token = $user->createToken('login_token')->plainTextToken;

        // Return success response with token and user data
        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token'   => $token,
            'user'    => [
                'user_id' => $user->user_id,  // custom primary key
                'name'    => $user->name,
                'email'   => $user->email,
            ]
        ], 200);
    }

    //get all users
    //header passed Bearer <login_token>
    // public function AllUsers()
    // {
    //     // Fetch all users except password
    //     $users = User::select('user_id', 'name', 'email')->get();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Users fetched successfully',
    //         'total'   => $users->count(),
    //         'data'    => $users
    //     ]);
    // }

    //user by id
    public function getUserById($user_id)
    {
        $user = User::find($user_id);

        // if (auth()->id() != $user_id) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorized access.',
        //     ], 403);
        // }

        //verify user exist or not
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404); //Not Found
        }

        // Success response
        return response()->json([
            'success' => true,
            'message' => 'Users fetched successfully',
            'user' => [
                'user_id' => $user->user_id,
                'name'  => $user->name,
                'email' => $user->email,
            ]
        ], 200);
    }

    //update user
    public function updateUser(Request $request, $user_id)
    {
        $authUser = auth()->user();

        // Check if authenticated user is updating their own profile
        if ($authUser->user_id != $user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized: You can only update your own profile.'
            ], 403); //Forbidden
        }

        // Validate request input
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:50|min:2',
            'email' => 'sometimes|email|unique:users,email,' . $user_id . ',user_id',
            'password' => [
                'sometimes',
                'string',
                'min:8',
                'same:confirm_password',
                'regex:/[a-z]/',      // lowercase
                'regex:/[A-Z]/',      // uppercase
                'regex:/[0-9]/',      // digit
                'regex:/[@$!%*#?&]/', // special char
            ],
            'confirm_password' => 'sometimes|string|min:6|max:20',
        ], [
            'password.min' => 'Password must be at least 8 characters.',
            'password.same' => 'Password and Confirm Password must match.',
            'password.regex' => 'Password must include: one uppercase letter, one lowercase letter, one number, and one special character.',
            'email.unique' => 'This email is already taken.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Failed',
                'errors'  => $validator->errors(),
            ], 422); // Unprocessable Entity
        }

        // Find the user
        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Update only provided fields
        if ($request->filled('name')) {
            $user->name = $request->name;
        }

        if ($request->filled('email')) {
            $user->email = $request->email;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'user' => [
                'user_id' => $user->user_id,
                'name'  => $user->name,
                'email' => $user->email,
            ]
        ], 200); //Ok
    }
    /**
     * Send email verification link to the authenticated user.
     */
    // public function sendVerificationEmail(Request $request)
    // {
    //     if ($request->user()->hasVerifiedEmail()) {
    //         return response()->json(['message' => 'Email already verified.'], 200);
    //     }

    //     $request->user()->sendEmailVerificationNotification();

    //     return response()->json(['message' => 'Verification link sent!'], 200);
    // }


    // public function logout(Request $request)
    // {
    //     // But this deletes all tokens the user has — web, mobile, etc.
    //     // $request->user()->tokens()->delete();

    //     // Delete only the token that was used for this request (current device)
    //     $request->user()->currentAccessToken()->delete();

    //     return response()->json([
    //         'success' => true,
    //         'message' => 'Logged out successfully.',
    //     ]);
    // }
}




//  200 OK — For successful retrieval and updates.

// 201 Created — When a new user is successfully registered.

// 401 Unauthorized — For failed login attempts.

// 403 Forbidden — For unauthorized update attempts.

// 404 Not Found — When a user is not found.

// 422 Unprocessable Entity — For validation errors.
