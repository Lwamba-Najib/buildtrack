<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Console\Command;
use App\Models\SecuritySettings;
use Illuminate\Support\Facades\Log;

class LockDormantAccounts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:lock-dormant';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Locks accounts that have been dormant for the specified duration';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        try {
            // Fetch the account expiry setting (default to 90 days)
            $expiryDays = SecuritySettings::first()?->dormant_account_expiry ?? 90;

            $expiryDate = Carbon::now()->subDays($expiryDays);

            // Find users who haven't logged in within the expiry period and are not already locked
            $dormantUsers = User::where('last_login_at', '<', $expiryDate)
                                ->orWhereNull('last_login_at') // If last login is NULL, consider dormant
                                ->where('is_locked', false)
                                ->get();

            foreach ($dormantUsers as $user) {
                $user->update(['is_locked' => true]); // Lock the account

                Log::info("User {$user->id} locked due to inactivity.");
            }

            $this->info("Dormant accounts locked successfully.");
        } catch (\Exception $e) {
            Log::error("Error locking dormant accounts: " . $e->getMessage());
        }
    }
}
