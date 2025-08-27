<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Sanctum\PersonalAccessToken;
use Carbon\Carbon;

class DeleteExpiredTokens extends Command
{
    protected $signature = 'tokens:delete-expired';
    protected $description = 'Delete Sanctum tokens that have expired (last_used_at or created_at > 15 days ago)';

    public function handle()
    {
        $expirationDate = Carbon::now()->subDays(15);

        // Delete tokens where last_used_at or created_at is older than expirationDate
        $deleted = PersonalAccessToken::where(function ($query) use ($expirationDate) {
            $query->where('last_used_at', '<', $expirationDate)
                ->orWhereNull('last_used_at'); // If last_used_at is null, check created_at
        })->where('created_at', '<', $expirationDate)
            ->delete();

        $this->info("Deleted {$deleted} expired tokens.");

        return 0;
    }
}
