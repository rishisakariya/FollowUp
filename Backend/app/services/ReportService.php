<?php

namespace App\Services;

use App\Models\FollowUp;

class ReportService
{
    // Fetch all follow-ups and add 'followup_status' for each
    public function getAllFollowUpsWithStatus()
    {
        $missed = FollowUp::where('status', 'missed')->get()->map(function ($item) {
            $item->followup_status = 'Missed';
            return $item;
        });

        $completed = FollowUp::where('status', 'completed')->get()->map(function ($item) {
            $item->followup_status = 'Completed';
            return $item;
        });

        // Add other statuses if you want
        $pending = FollowUp::where('status', 'pending')->get()->map(function ($item) {
            $item->followup_status = 'Pending';
            return $item;
        });

        return $missed->concat($completed)->concat($pending);
    }

    // Get count of follow-ups grouped by status
    public function getFollowUpStatusCounts()
    {
        return FollowUp::selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [ucfirst($item->status) => $item->total];
            });
    }
}
