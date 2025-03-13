<?php

namespace App\Http\Controllers;

use App\Models\PasswordPolicy;
use App\Models\SecuritySettings;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

class PasswordPolicyController extends Controller
{
    // Fetch security settings from the database
    public function getSecuritySettings(): JsonResponse
    {
        try {
            $setting = SecuritySettings::first();

            if (!$setting) {
                return response()->json(['error' => 'Security settings not found'], 404);
            }

            return response()->json($setting);
        } catch (\Exception $e) {
            Log::error("Error fetching security settings: " . $e->getMessage());
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

    // Fetch and return the security settings as JSON data
    public function securitySettingsJsonData(): JsonResponse
    {
        try {
            $setting = SecuritySettings::first();

            if (!$setting) {
                return response()->json([], 404);
            }

            $response = [
                'security_settings_2fa' => $setting->security_settings_2fa ?? 'Yes',
                'security_settings_lowercase' => $setting->security_settings_lowercase ?? 'Yes',
                'security_settings_uppercase' => $setting->security_settings_uppercase ?? 'Yes',
                'security_settings_numbers' => $setting->security_settings_numbers ?? 'Yes',
                'security_settings_symbols' => $setting->security_settings_symbols ?? 'Yes',
                'security_settings_length' => $setting->security_settings_length ?? 8,
                'security_settings_expiry' => $setting->security_settings_expiry ?? 6,
                'dormant_account_expiry' => $setting->dormant_account_expiry ?? 90,
                'security_settings_login_attempt' => $setting->security_settings_login_attempt ?? 5,
                'security_settings_history_counts' => $setting->security_settings_history_counts ?? 5,
            ];

            return response()->json($response);
        } catch (\Exception $e) {
            Log::error("Error in securitySettingsJsonData: " . $e->getMessage());
            return response()->json(['error' => 'An error occurred'], 500);
        }
    }

    // Get character sets for password validation
    public function getCharacterSets(): array
    {
        return [
            'lowercase' => 'abcdefghijklmnopqrstuvwxyz',
            'uppercase' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'numbers' => '0123456789',
            'symbols' => '!@#$%^&*()'
        ];
    }

    // Get descriptions for character sets
    public function getCharacterSetDescriptions(): array
    {
        return [
            'lowercase' => 'Lowercase Letters',
            'uppercase' => 'Uppercase Letters',
            'numbers' => 'Numbers',
            'symbols' => 'Special Characters',
        ];
    }

    // Validate password based on security settings
    public function validatePassword(string $password, string $validChars, array $securitySettings): mixed
    {
        $messages = [];
        $sets = $this->getCharacterSets();
        $charSetDescriptions = $this->getCharacterSetDescriptions();

        $requiredSets = [];
        if (strpos($validChars, $sets['lowercase']) !== false) {
            $requiredSets[] = ['set' => $sets['lowercase'], 'description' => $charSetDescriptions['lowercase']];
        }
        if (strpos($validChars, $sets['uppercase']) !== false) {
            $requiredSets[] = ['set' => $sets['uppercase'], 'description' => $charSetDescriptions['uppercase']];
        }
        if (strpos($validChars, $sets['numbers']) !== false) {
            $requiredSets[] = ['set' => $sets['numbers'], 'description' => $charSetDescriptions['numbers']];
        }
        if (strpos($validChars, $sets['symbols']) !== false) {
            $requiredSets[] = ['set' => $sets['symbols'], 'description' => $charSetDescriptions['symbols']];
        }

        // Check if the password meets the length requirement
        if (strlen($password) < $securitySettings['security_settings_length']) {
            $messages[] = "The password must be at least {$securitySettings['security_settings_length']} characters long.";
        }

        // Check if the password contains only allowed characters
        if (!preg_match('/^[' . preg_quote($validChars, '/') . ']+$/', $password)) {
            $messages[] = "The password contains invalid characters. It should only include: " . implode(' & ', array_column($requiredSets, 'description')) . ".";
        }

        // Check if the password contains at least one character from each required set
        foreach ($requiredSets as $set) {
            if (!preg_match('/[' . preg_quote($set['set'], '/') . ']/', $password)) {
                $messages[] = "The password must contain at least one {$set['description']}.";
            }
        }

        return empty($messages) ? true : $messages;
    }

    // Helper function to generate a password based on a provided character set
    public function generatePassword(int $length, string $characters): string
    {
        try {
            $password = '';
            $charactersLength = strlen($characters);
            for ($i = 0; $i < $length; $i++) {
                $password .= $characters[rand(0, $charactersLength - 1)];
            }
            return $password;
        } catch (\Exception $e) {
            Log::error("Error generating password: " . $e->getMessage());
            return '';
        }
    }

    // Generate a password based on security settings
    public function passwordSetting(): ?string
    {
        try {
            $setting = SecuritySettings::first();

            if (!$setting) {
                return null;
            }

            $sets = $this->getCharacterSets();
            $lowercaseSet = $sets['lowercase'];
            $uppercaseSet = $sets['uppercase'];
            $numbersSet = $sets['numbers'];
            $symbolsSet = $sets['symbols'];

            $lowercaseSettings = trim($setting->security_settings_lowercase ?? '');
            $uppercaseSettings = trim($setting->security_settings_uppercase ?? '');
            $numbersSettings = trim($setting->security_settings_numbers ?? '');
            $symbolsSettings = trim($setting->security_settings_symbols ?? '');
            $lengthSettings = trim($setting->security_settings_length ?? 3);

            $charSet = '';

            if ($lowercaseSettings === 'Yes') {
                $charSet .= $lowercaseSet;
            }
            if ($uppercaseSettings === 'Yes') {
                $charSet .= $uppercaseSet;
            }
            if ($numbersSettings === 'Yes') {
                $charSet .= $numbersSet;
            }
            if ($symbolsSettings === 'Yes') {
                $charSet .= $symbolsSet;
            }

            if (empty($charSet)) {
                $charSet = $lowercaseSet . $uppercaseSet . $numbersSet . $symbolsSet;
            }

            return $this->generatePassword($lengthSettings, $charSet);
        } catch (\Exception $e) {
            Log::error("Error in passwordSetting: " . $e->getMessage());
            return null;
        }
    }

    // Calculate password expiry date
    public function passwordExpiry(): ?string
    {
        try {
            $setting = SecuritySettings::first();

            if (!$setting) {
                return null;
            }

            $monthNum = trim($setting->security_settings_expiry ?? 6);
            return now()->addMonths($monthNum)->toDateString();
        } catch (\Exception $e) {
            Log::error("Error in passwordExpiry: " . $e->getMessage());
            return null;
        }
    }

    // Fetch password length
    public function passwordLength(): int
    {
        try {
            $setting = SecuritySettings::first();
            return $setting ? trim($setting->security_settings_length ?? 8) : 8;
        } catch (\Exception $e) {
            Log::error("Error in passwordLength: " . $e->getMessage());
            return 3;
        }
    }

    // Fetch login attempts limit
    public function loginAttemps(): int
    {
        try {
            $setting = SecuritySettings::first();
            return $setting ? trim($setting->security_settings_login_attempt ?? 5) : 5;
        } catch (\Exception $e) {
            Log::error("Error in loginAttemps: " . $e->getMessage());
            return 5;
        }
    }

    // Fetch account expiry duration
    public function accountExpiry(): int
    {
        try {
            $setting = SecuritySettings::first();
            return $setting ? trim($setting->dormant_account_expiry ?? 90) : 90;
        } catch (\Exception $e) {
            Log::error("Error in accountExpiry: " . $e->getMessage());
            return 30;
        }
    }

    // Fetch password history count
    public function passwordHistoryCounts(): int
    {
        try {
            $setting = SecuritySettings::first();
            return $setting ? trim($setting->security_settings_history_counts ?? 5) : 5;
        } catch (\Exception $e) {
            Log::error("Error in passwordHistoryCounts: " . $e->getMessage());
            return 3;
        }
    }

    // Insert password policy data
    public function passwordPolicyIn(string $user_password, string $user_password_due,int $user_id): void
    {
        try {
            PasswordPolicy::create([
                'password_policy_pwd' => $user_password,
                'password_policy_due' => $user_password_due,
                'user_id' => $user_id
            ]);
        } catch (\Exception $e) {
            Log::error("Error in passwordPolicyIn: " . $e->getMessage());
        }
    }

    // Validate password re-use against history
    public function passwordPolicyV(int $user_id, string $new_password, int $limit): bool
    {
        try {
            $recentPasswords = PasswordPolicy::where('user_id', $user_id)
                ->orderByDesc('id')
                ->limit($limit)
                ->pluck('password_policy_pwd');

            // Check if the new password matches any of the recent passwords
            foreach ($recentPasswords as $hashedPassword) {
                if (Hash::check($new_password, $hashedPassword)) {
                    return false; // Password is a match with a recent one
                }
            }

            // No match found, return true (it's a new password)
            return true;
        } catch (\Exception $e) {
            Log::error("Error in passwordPolicyV: " . $e->getMessage());
            return false;
        }
    }

    // Check if password exists in policy history
    public function passwirdPolicyExists(int $user_id, string $password_policy_pwd): bool
    {
        try {
            return PasswordPolicy::where('user_id', $user_id)
                ->where('password_policy_pwd', $password_policy_pwd)
                ->exists();
        } catch (\Exception $e) {
            Log::error("Error in passwordPolicyExists: " . $e->getMessage());
            return false;
        }
    }
}
