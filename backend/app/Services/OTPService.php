<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class OTPService
{
    /**
     * Verify the OTP for the authenticated user.
     *
     * @param string $otp
     * @return array
     */
    public function verifyOTP($otp)
    {
        $user = Auth::user();

        // Check if the user is valid
        if (!$user instanceof User) {
            return [
                'success' => false,
                'message' => 'Invalid user instance.',
                'status' => 400,
            ];
        }

        // Check if the OTP has expired
        if ($user->otp_expires_at && Carbon::now()->gt($user->otp_expires_at)) {
            return [
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
                'status' => 401,
            ];
        }

        // Verify the OTP
        if (Hash::check($otp, $user->otp)) {
            // Clear the OTP and its expiry time after successful verification
            $user->update([
                'otp' => null,
                'otp_expires_at' => null,
            ]);

            return [
                'success' => true,
                'message' => 'OTP verified successfully.',
                'status' => 200,
            ];
        }

        return [
            'success' => false,
            'message' => 'Invalid OTP.',
            'status' => 401,
        ];
    }
}
