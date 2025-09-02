<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Followup;
use App\Models\User;
use App\Models\Receiver;
use Carbon\Carbon;
use App\Traits\LogsFollowUpActivity;

class FollowupController extends Controller
{
    public function AddFollowUp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title'        => 'required|string|max:50|min:5',
            'description'  => 'nullable|string',
            'status'       => 'nullable|string|in:Pending,InProgress,Completed,Cancelled',
            'date'         => 'required|date|after_or_equal:today',
            'set_reminder' => 'nullable|string|in:true,false',
            'time'         => 'nullable|date_format:H:i:s',
            'receiver_name' => 'required|string|exists:receiver,name',
        ], [
            'title.required'        => 'Title is required.',
            'title.max'             => 'Title must not exceed 50 characters.',
            'time.date_format'      => 'Time must be in the format HH:MM:SS.',
            'receiver_name.required' => 'Receiver name is required.',
            'receiver_name.exists'  => 'The selected receiver does not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = auth()->user();

        $receiver = Receiver::where('name', $request->receiver_name)->first();

        $status = $request->status ?? 'Pending';
        $setReminder = $request->has('set_reminder') ? $request->set_reminder === 'true' : false;

        $followup = Followup::create([
            'title'               => $request->title,
            'creator_receiver_id' => $receiver->receiver_id,
            'description'         => $request->description,
            'status'              => $status,
            'date'                => $request->date,
            'set_reminder'        => $setReminder,
            'time'                => $request->time ?? '08:00:00',
            'created_by'          => $user->user_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Follow-up created successfully.',
            'data' => [
                'task_id'    => $followup->task_id,
                'title'      => $followup->title,
                'description' => $followup->description,
                'status'     => $followup->status,
                'date'       => $followup->date,
                'set_reminder' => $followup->set_reminder,
                'time'       => $followup->time,
                'creator'    => $user->name,
                'receiver'   => $receiver->name
            ],
        ], 201);
    }

    public function UpdateFollowUp(Request $request, $task_id)
    {
        $validator = Validator::make($request->all(), [
            'title'          => 'required|string|max:50|min:5',
            'description'    => 'nullable|string',
            'status'         => 'nullable|string|in:Pending,InProgress,Completed,Cancelled',
            'date'           => 'required|date|after_or_equal:today',
            'set_reminder'   => 'nullable|string|in:true,false',
            'time'           => 'nullable|date_format:H:i:s',
            'receiver_name'  => 'required|string|exists:receiver,name',
        ], [
            'title.required'         => 'Title is required.',
            'title.max'              => 'Title must not exceed 50 characters.',
            'title.min'              => 'Title must be at least 5 characters.',
            'time.date_format'       => 'Time must be in the format HH:MM:SS.',
            'receiver_name.required' => 'Receiver name is required.',
            'receiver_name.exists'   => 'The selected receiver does not exist.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $followup = Followup::find($task_id);

        if (!$followup) {
            return response()->json([
                'success' => false,
                'message' => 'Follow-up not found.',
            ], 404);
        }

        $user = auth()->user();

        // Authorization check: only creator can update
        if ($followup->created_by !== $user->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to update this follow-up.',
            ], 403);
        }

        $receiver = Receiver::where('name', $request->receiver_name)->first();

        $status = $request->status ?? $followup->status;
        $setReminder = $request->has('set_reminder') ? $request->set_reminder === 'true' : false;

        $followup->update([
            'title'               => $request->title,
            'description'         => $request->description,
            'status'              => $status,
            'date'                => $request->date,
            'set_reminder'        => $setReminder,
            'time'                => $request->time ?? '08:00:00',
            'creator_receiver_id' => $receiver->receiver_id,
            'updated_by'          => $user->user_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Follow-up updated successfully.',
            'data' => [
                'task_id'    => $followup->task_id,
                'title'      => $followup->title,
                'description' => $followup->description,
                'status'     => $followup->status,
                'date'       => $followup->date,
                'set_reminder' => $followup->set_reminder,
                'time'       => $followup->time,
                'creator'    => $user->name,
                'receiver'   => $receiver->name,
            ],
        ], 200);
    }

    public function getFollowUpsByUserId($user_id)
    {
        $user = User::find($user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.'
            ], 404);
        }

        $followups = Followup::where('created_by', $user_id)
            ->with('receiver')
            ->get();

        if ($followups->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No follow-ups found for this user.',
                'data'    => []
            ], 200);
        }

        $data = $followups->map(function ($followup) use ($user) {
            return [
                'task_id'      => $followup->task_id,
                'title'        => $followup->title,
                'description'  => $followup->description,
                'status'       => $followup->status,
                'date'         => $followup->date,
                'set_reminder' => $followup->set_reminder,
                'time'         => $followup->time,
                'creator'      => $user->name,
                'receiver'     => optional($followup->receiver)->name ?? 'Unknown',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Follow-ups retrieved successfully.',
            'data'    => $data
        ], 200);
    }

    public function Destroy($id)
    {
        $followup = Followup::find($id);

        if (!$followup) {
            return response()->json([
                'message' => 'Follow-up not found.'
            ], 404);
        }

        if ($followup->created_by !== auth()->user()->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized to delete this follow-up.'
            ], 403);
        }

        $followup->delete();

        return response()->json([
            'success' => true,
            'message' => 'Follow-up deleted successfully.'
        ], 200);
    }

    public function getFollowUpsByReceiverId($receiver_id)
    {
        $receiver = Receiver::find($receiver_id);

        if (!$receiver) {
            return response()->json([
                'success' => false,
                'message' => 'Receiver not found.'
            ], 404);
        }

        $followups = Followup::where('creator_receiver_id', $receiver_id)
            ->with(['receiver', 'createdBy'])
            ->get();

        if ($followups->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No follow-ups found for this receiver.',
                'data'    => []
            ], 200);
        }

        $data = $followups->map(function ($followup) {
            return [
                'task_id'      => $followup->task_id,
                'title'        => $followup->title,
                'description'  => $followup->description,
                'status'       => $followup->status,
                'date'         => $followup->date,
                'set_reminder' => $followup->set_reminder,
                'time'         => $followup->time,
                'creator'      => optional($followup->createdBy)->name ?? 'Unknown',
                'receiver'     => optional($followup->receiver)->name ?? 'Unknown',
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Follow-ups retrieved successfully.',
            'data'    => $data
        ], 200);
    }
    public function getSurroundingMonthlyFollowups(Request $request)
    {
        $user = auth()->user();

        // Validate inputs
        $validator = Validator::make($request->all(), [
            'month' => 'required|integer|between:1,12',
            'year'  => 'required|integer|min:2000|max:' . (now()->year + 5),
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid input.',
                'errors'  => $validator->errors()
            ], 422);
        }

        $selectedMonth = (int) $request->input('month');
        $selectedYear  = (int) $request->input('year');

        // Create previous, current, next months
        $months = collect([
            Carbon::createFromDate($selectedYear, $selectedMonth, 1)->subMonth(), // Previous
            Carbon::createFromDate($selectedYear, $selectedMonth, 1),             // Current
            Carbon::createFromDate($selectedYear, $selectedMonth, 1)->addMonth(), // Next
        ]);

        $finalData = [];

        foreach ($months as $monthDate) {
            $startDate = $monthDate->copy()->startOfMonth()->toDateString();
            $endDate   = $monthDate->copy()->endOfMonth()->toDateString();

            // Get followups in this range
            $followups = Followup::where('created_by', $user->user_id)
                ->whereBetween('date', [$startDate, $endDate])
                ->with('receiver')
                ->get()
                ->groupBy('date');

            // Skip months with no data
            if ($followups->isEmpty()) {
                continue;
            }

            foreach ($followups as $date => $items) {
                $finalData[] = [
                    'date' => $date,
                    'count' => $items->count(),
                    'followups' => $items->map(function ($followup) {
                        return [
                            'task_id'     => $followup->task_id,
                            'title'       => $followup->title,
                            'description' => $followup->description,
                            'status'      => $followup->status,
                            'time'        => $followup->time,
                            'receiver'    => optional($followup->receiver)->name ?? 'Unknown',
                        ];
                    })->values()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Monthly follow-up data retrieved.',
            'data'    => $finalData
        ]);
    }
}



// namespace App\Http\Controllers\Api;

// use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
// use Illuminate\Support\Facades\Validator;
// use App\Models\Followup;
// use App\Models\User;
// use App\Models\Receiver;
// use Carbon\Carbon;
// use Illuminate\Support\Collection;
// use App\Traits\LogsFollowUpActivity;

// class FollowupController extends Controller
// {
//     use LogsFollowUpActivity;
//     public function AddFollowUp(Request $request)
//     {
//         $validator = Validator::make($request->all(), [
//             'title'        => 'required|string|max:50|min:5',
//             'description'  => 'nullable|string',
//             'status' => 'nullable|string|in:Pending,InProgress,Completed,Cancelled',
//             'date' => 'required|date|after_or_equal:today',
//             'set_reminder' => 'nullable|string|in:true,false',
//             'time'         => 'nullable|date_format:H:i:s',
//             'receiver_name' => 'required|string|exists:receiver,name',
//         ], [
//             'title.required'   => 'Title is required.',
//             'title.max'        => 'Title must not exceed 50 characters.',
//             'time.date_format' => 'Time must be in the format HH:MM:SS.',
//             'receiver_name.required' => 'Receiver name is required.',
//             'receiver_name.exists'  => 'The selected receiver does not exist.',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Validation failed.',
//                 'errors'  => $validator->errors()
//             ], 422);
//         }
//         //Get authenticated user
//         $user = auth()->user();

//         // Get the receiver by name
//         $receiver = Receiver::where('name', $request->receiver_name)->first();

//         // Convert string to booleans with default false
//         $status = $request->status ?? 'Pending'; // Default to Pending if not provided
//         $setReminder = $request->has('set_reminder') ? $request->set_reminder === 'true' : false;

//         // Create followup
//         $followup = Followup::create([
//             'title'        => $request->title,
//             'creator_user_id' => auth()->user()->user_id, // Authenticated user ID
//             'creator_receiver_id' => $receiver->receiver_id,
//             'description'  => $request->description,
//             'status'       => $status,
//             'date'         => $request->date,
//             'set_reminder' => $setReminder,
//             'time'         => $request->time ?? '08:00:00',
//             'created_by'          => $user->user_id,
//         ]);
//         return response()->json([
//             'success' => true,
//             'message' => 'Follow-up created successfully.',
//             // 'data'=>$followup,
//             'data' => [
//                 'task_id' => $followup->task_id,
//                 'title' => $followup->title,
//                 'description' => $followup->description,
//                 'status' => $followup->status,
//                 'date'   => $followup->date,
//                 'set_reminder' => $followup->set_reminder,
//                 'time' => $followup->time,
//                 'creator' => $user->name,
//                 'receiver' => $receiver->name
//             ],
//             // 'creator' => [
//             //     'user_id' => $user->user_id,
//             //     'name'    => $user->name
//             // ],
//             // 'receiver' => [
//             //     'receiver_id' => $receiver->receiver_id,
//             //     'name'        => $receiver->name
//             // ]
//         ], 201);
//     }

//     //Update FollowUP
//     // public function UpdateFollowUp(Request $request, $task_id)
//     // {
//     //     $validator = Validator::make($request->all(), [
//     //         'title'        => 'required|string|max:50|min:5',
//     //         'description'  => 'nullable|string',
//     //         'status' => 'nullable|string|in:true,false',
//     //         'set_reminder' => 'nullable|string|in:true,false',
//     //         'time'         => 'nullable|date_format:H:i:s',
//     //     ], [
//     //         'title.required'   => 'Title is required.',
//     //         'title.max'        => 'Title must not exceed 50 characters.',
//     //         'title.min'        => 'Title must be at least 5 characters.',
//     //         'time.date_format' => 'Time must be in the format HH:MM:SS.',
//     //     ]);

//     //     if ($validator->fails()) {
//     //         return response()->json([
//     //             'success' => false,
//     //             'message' => 'Validation failed.',
//     //             'errors'  => $validator->errors()
//     //         ], 422);
//     //     }

//     //     // Find the followup by task_id
//     //     $followup = Followup::find($task_id);

//     //     if (!$followup) {
//     //         return response()->json([
//     //             'success' => false,
//     //             'message' => 'Follow-up not found.',
//     //         ], 404);
//     //     }

//     //     // Check if the authenticated user is the creator
//     //     if ($followup->creator !== auth()->user()->user_id) {
//     //         return response()->json([
//     //             'success' => false,
//     //             'message' => 'Unauthorized to update this follow-up.',
//     //         ], 403);
//     //     }
//     //     // Convert string to booleans with default false
//     //     $status = $request->has('status') ? $request->status === 'true' : false;
//     //     $setReminder = $request->has('set_reminder') ? $request->set_reminder === 'true' : false;

//     //     // Update the follow-up
//     //     $followup->update([
//     //         'title'        => $request->title,
//     //         'description'  => $request->description,
//     //         'status'       => $status,
//     //         'set_reminder' => $setReminder,
//     //         'time'         => $request->time ?? '08:00:00',
//     //     ]);

//     //     return response()->json([
//     //         'success' => true,
//     //         'message' => 'Follow-up updated successfully.',
//     //         'data'    => $followup,
//     //     ]);
//     // }

//     public function UpdateFollowUp(Request $request, $task_id)
//     {
//         $validator = Validator::make($request->all(), [
//             'title'          => 'required|string|max:50|min:5',
//             'description'    => 'nullable|string',
//             'status' => 'nullable|string|in:Pending,InProgress,Completed,Cancelled',
//             'date' => 'required|date|after_or_equal:today',
//             'set_reminder'   => 'nullable|string|in:true,false',
//             'time'           => 'nullable|date_format:H:i:s',
//             'receiver_name'  => 'required|string|exists:receiver,name',
//         ], [
//             'title.required'         => 'Title is required.',
//             'title.max'              => 'Title must not exceed 50 characters.',
//             'title.min'              => 'Title must be at least 5 characters.',
//             'time.date_format'       => 'Time must be in the format HH:MM:SS.',
//             'receiver_name.required' => 'Receiver name is required.',
//             'receiver_name.exists'   => 'The selected receiver does not exist.',
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Validation failed.',
//                 'errors'  => $validator->errors()
//             ], 422);
//         }

//         // Find the follow-up by task_id
//         $followup = Followup::find($task_id);

//         if (!$followup) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Follow-up not found.',
//             ], 404);
//         }

//         // Get the authenticated user
//         $user = auth()->user();

//         // // Check if the authenticated user is the update

//         // User A created followup:
//         // If User A tries to update it → allowed.

//         // User B tries to update same followup:
//         // If User B logs in and tries → 403 Unauthorized.

//         if ($followup->creator_user_id !== auth()->user()->user_id) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Unauthorized to update this follow-up.',
//             ], 403);
//         }

//         // Get the new receiver by name
//         $receiver = Receiver::where('name', $request->receiver_name)->first();

//         // Convert strings to booleans with default false
//         $status = $request->status ?? $followup->status; // Keep old status if not changed
//         $setReminder = $request->has('set_reminder') ? $request->set_reminder === 'true' : false;

//         // Update the follow-up
//         $followup->update([
//             'title'               => $request->title,
//             'description'         => $request->description,
//             'status'              => $status,
//             'date'                => $request->date,
//             'set_reminder'        => $setReminder,
//             'time'                => $request->time ?? '08:00:00',
//             'creator_receiver_id' => $receiver->receiver_id,
//             'updated_by'          => $user->user_id,
//         ]);

//         return response()->json([
//             'success' => true,
//             'message' => 'Follow-up updated successfully.',
//             'data' => [
//                 'task_id' => $followup->task_id,
//                 'title' => $followup->title,
//                 'description' => $followup->description,
//                 'status' => $followup->status,
//                 'date' => $followup->date,
//                 'set_reminder' => $followup->set_reminder,
//                 'time' => $followup->time,
//                 'creator' => $user->name,
//                 'receiver' => $receiver->name
//             ],
//             // 'data'    => $followup,
//             // 'creator' => [
//             //     // 'user_id' => $user->user_id,
//             //     'name'    => $user->name
//             // ],
//             // 'receiver' => [
//             //     // 'receiver_id' => $receiver->receiver_id,
//             //     'name'        => $receiver->name
//             // ]
//         ], 200);
//     }
//     public function getFollowUpsByUserId($user_id)
//     {
//         // Check if user exists
//         $user = User::find($user_id);
//         if (!$user) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'User not found.'
//             ], 404);
//         }

//         // Retrieve follow-ups with related receiver data
//         $followups = Followup::where('creator_user_id', $user_id)
//             ->with('receiver') // eager load the receiver relationship
//             ->get();

//         if ($followups->isEmpty()) {
//             return response()->json([
//                 'success' => true,
//                 'message' => 'No follow-ups found for this user.',
//                 'data' => []
//             ], 200); //Empty But SuccessFully.
//         }

//         // Transform followups to include receiver name
//         $data = $followups->map(function ($followup) use ($user) {
//             return [
//                 'task_id'       => $followup->task_id,
//                 'title'         => $followup->title,
//                 'description'   => $followup->description,
//                 'status'        => $followup->status,
//                 'date'          => $followup->date,
//                 'set_reminder'  => $followup->set_reminder,
//                 'time'          => $followup->time,
//                 'creator'       => $user->name,
//                 'receiver'      => optional($followup->receiver)->name ?? 'Unknown',
//             ];
//         });

//         return response()->json([
//             'success' => true,
//             'message' => 'Follow-ups retrieved successfully.',
//             'data'    => $data
//         ], 200);
//     }
//     public function Destroy($id)
//     {
//         $followup = Followup::find($id);

//         if (!$followup) {
//             return response()->json([
//                 'message' => 'Follow-up not found.'
//             ], 404);
//         }

//         // // Check if the authenticated user is the creator
//         if ($followup->creator_user_id !== auth()->user()->user_id) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Unauthorized to delete this follow-up.'
//             ], 403);
//         }

//         $followup->delete();

//         return response()->json([
//             'success' => true,
//             'message' => 'Follow-up deleted successfully.'
//         ], 200);
//     }
//     public function getFollowUpsByReceiverId($receiver_id)
//     {
//         // Check if receiver exists
//         $receiver = Receiver::find($receiver_id);
//         if (!$receiver) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Receiver not found.'
//             ], 404);
//         }

//         // Get all follow-ups by this receiver ID
//         $followups = Followup::where('creator_receiver_id', $receiver_id)->with('receiver', 'user')->get();

//         if ($followups->isEmpty()) {
//             return response()->json([
//                 'success' => true,
//                 'message' => 'No follow-ups found for this receiver.',
//                 'data' => []
//             ], 200); //Empty But Successfully
//         }

//         $data = $followups->map(function ($followup) {
//             return [
//                 'task_id'      => $followup->task_id,
//                 'title'        => $followup->title,
//                 'description'  => $followup->description,
//                 'status'       => $followup->status,
//                 'set_reminder' => $followup->set_reminder,
//                 'time'         => $followup->time,
//                 'creator'      => optional($followup->creatorUser)->name, // null-safe
//                 'receiver'     => optional($followup->receiver)->name,    // from relation
//             ];
//         });

//         // Return follow-ups with receiver name
//         return response()->json([
//             'success' => true,
//             'message' => 'Follow-ups retrieved successfully.',
//             'receiver' => $receiver->name,
//             'data' => $data
//         ], 200);
//     }



//     public function getMonthlyFollowups(Request $request)
//     {
//         $user = auth()->user();

//         // Validate request inputs
//         $validator = Validator::make($request->all(), [
//             'month' => 'required|integer|between:1,12', //1 to 12
//             'year'  => 'required|integer|min:2000|max:' . now()->year + 5, //2000 to now(2025)+5
//         ]);

//         if ($validator->fails()) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Invalid input.',
//                 'errors'  => $validator->errors()
//             ], 422);
//         }

//         $month = $request->input('month'); // Example: 08
//         $year = $request->input('year');   // Example: 2025

//         if (!$month || !$year) {
//             return response()->json([
//                 'success' => false,
//                 'message' => 'Month and year are required.'
//             ], 400);
//         }

//         $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
//         $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

//         // Fetch all followups in month
//         $followups = Followup::where('creator_user_id', $user->user_id)
//             ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
//             ->get()
//             ->groupBy('date');

//         $daysInMonth = $startDate->daysInMonth;
//         $calendar = [];

//         for ($day = 1; $day <= $daysInMonth; $day++) {
//             $date = Carbon::createFromDate($year, $month, $day)->toDateString();

//             $calendar[] = [
//                 'date' => $date,
//                 'count' => isset($followups[$date]) ? $followups[$date]->count() : 0
//             ];
//         }

//         return response()->json([
//             'success' => true,
//             'message' => 'Monthly follow-up data retrieved.',
//             // 'month' => $month,
//             // 'year' => $year,
//             'data' => $calendar
//         ]);
//     }
// }

// // 200 OK — For successful retrieval, updates, and deletions.

// // 201 Created — When a new follow-up is successfully created.

// // 404 Not Found — When a user, receiver, or follow-up is not found.

// // 422 Unprocessable Entity — For validation errors on creation or update.
