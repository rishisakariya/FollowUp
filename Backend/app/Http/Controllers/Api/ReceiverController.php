<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Receiver;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class ReceiverController extends Controller
{
    //
    public function AddReceiver(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:50|min:2|unique:receiver,name',
            // 'creator' => 'required|exists:users,user_id',
            'color' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        //Get authenticated user
        $user = auth()->user();

        $receiver = Receiver::create([
            'name' => $request->name,
            'color' => $request->color,
            'creator' => auth()->user()->user_id, // Authenticated user ID
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Receiver registered successfully.',
            'data' => [
                'name' => $receiver->name,
                'color' => $receiver->color,
                'creator' => $user->name
            ],
            // 'creator' => [
            //     'user_id' => $user->user_id,
            //     'name'    => $user->name
            // ]
        ], 201);
    }






    public function updateReceiver(Request $request, $receiver_id)
    {
        $validator = Validator::make($request->all(), [
            'name'  => 'required|string|max:50|min:2|unique:receiver,name',
            'color' => 'required|string',
        ], [
            'name.required'  => 'Receiver name is required.',
            'name.max'       => 'Receiver name must not exceed 50 characters.',
            'color.required' => 'Color is required.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $receiver = Receiver::find($receiver_id);

        if (!$receiver) {
            return response()->json([
                'success' => false,
                'message' => 'Receiver not found.'
            ], 404);
        }

        // Optional: Restrict updates to the creator

        // User A created receiver:
        // If User A tries to update it → allowed.

        // User B tries to update same receiver:
        // If User B logs in and tries → 403 Unauthorized.

        if ($receiver->creator !== auth()->user()->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this receiver.'
            ], 403);
        }

        // Update receiver
        $receiver->update([
            'name'  => $request->name,
            'color' => $request->color,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Receiver updated successfully.',
            'data'    => [
                'receiver_id' => $receiver->receiver_id,
                'name'        => $receiver->name,
                'color'       => $receiver->color,
            ]
        ], 200);
    }




    public function getReceiversByUserId($user_id)
    {
        // Check if user exists
        $user = User::find($user_id);
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        // Get receivers created by this user
        $receivers = Receiver::where('creator', $user_id)->get();

        if ($receivers->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No receivers found for this user.',
                'data' => []
            ], 200); // OK (empty but successful)
        }

        return response()->json([
            'success' => true,
            'message' => 'Receivers retrieved successfully.',
            'data' => $receivers
        ], 200);
    }




    public function getAllReceivers()
    {
        $receivers = Receiver::all();

        if ($receivers->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No receivers found.',
                'data' => []
            ], 200); // OK (empty but successful)
        }

        return response()->json([
            'success' => true,
            'total' => $receivers->count(),
            'message' => 'All receivers retrieved successfully.',
            'data' => $receivers
        ], 200);
    }



    //
    public function destroy($id)
    {
        $receiver = Receiver::find($id);

        if (!$receiver) {
            return response()->json([
                'message' => 'Receiver not found'
            ], 404);
        }

        $receiver->delete();

        return response()->json([
            'success' => true,
            'message' => 'Receiver deleted successfully'
        ], 200);
    }
}

// 200 OK (successful retrieval, update, or delete)

// 201	Created (new receiver added)

// 403	Forbidden (unauthorized update attempt)

// 404	Not Found (receiver or user doesn't exist)

// 422	Unprocessable Entity (validation errors)
