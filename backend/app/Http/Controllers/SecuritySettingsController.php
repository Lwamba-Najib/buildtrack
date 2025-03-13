<?php

namespace App\Http\Controllers;

use App\Models\SecuritySettings;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class SecuritySettingsController extends Controller
{
    /**
     * Display the security settings.
     */
    public function index(Request $request)
    {
        try {
            // Fetch the security settings (assuming only one record exists)
            $securitySettings = SecuritySettings::first();

            if (!$securitySettings) {
                // Return a default structure if no settings are found
                return response()->json([
                    'success' => true,
                    'data' => [
                        'security_settings_2fa' => '',
                        'security_settings_lowercase' => '',
                        'security_settings_uppercase' => '',
                        'security_settings_numbers' => '',
                        'security_settings_symbols' => '',
                        'security_settings_length' => null,
                        'security_settings_expiry' => null,
                        'dormant_account_expiry' => null,
                        'security_settings_login_attempt' => null,
                        'security_settings_history_counts' => null,
                    ],
                    'message' => 'No security settings found, returning default structure.'
                ], 200);
            }

            // Return existing security settings in JSON format
            return response()->json([
                'success' => true,
                'data' => $securitySettings
            ], 200);

        } catch (\Exception $e) {
            // Log and return error message on failure
            Log::error('Error in SecuritySettingsController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
    /**
     * Store or update security settings.
     */
    public function store(Request $request)
    {
        try {
            // Check if security settings record already exists
            $existingSetting = SecuritySettings::first();

            // Define validation rules for security settings
            $validationRules = [
                'security_settings_2fa' => 'string',
                'security_settings_lowercase' => 'string',
                'security_settings_uppercase' => 'string',
                'security_settings_numbers' => 'string',
                'security_settings_symbols' => 'string',
                'security_settings_length' => 'integer|min:3',
                'security_settings_expiry' => 'integer',
                'dormant_account_expiry' => 'integer',
                'security_settings_login_attempt' => 'integer',
                'security_settings_history_counts' => 'integer',
                'created_by' => 'nullable',
                'updated_by' => 'nullable',
            ];

            // Validate the incoming request
            $validated = $request->validate($validationRules);

            // Check if we are updating or creating the settings
            if ($existingSetting) {
                // Update existing settings
                $validated['updated_by'] = auth()->user()->id;
                $existingSetting->update($validated);
                $action = 'Update';
                $message = 'Security settings updated successfully!';
                $securitySettings = $existingSetting;
            } else {
                // Create new security settings
                $validated['created_by'] = auth()->user()->id;
                $securitySettings = SecuritySettings::create($validated);
                $action = 'Create';
                $message = 'Security settings created successfully!';
            }

            // Log action (creation or update)
            (new ApplicationLogController())->storeLog(
                $request,
                'SecuritySettings',
                $action,
                "$action Security settings with details: " . json_encode($validated),
                auth()->user()->id
            );

            // Return success response with the created or updated security settings
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $securitySettings,
            ], 200);

        } catch (ValidationException $e) {
            // Handle validation errors
            return response()->json([
                'success' => false,
                'message' => 'Validation Error: ' . $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);

        } catch (QueryException $exception) {
            // Handle database query errors
            return response()->json([
                'success' => false,
                'message' => 'Failed to create or update security settings: ' . $exception->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            // Log and handle other exceptions
            Log::error('Security settings error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during security settings processing. Please try again.',
            ], 500);
        }
    }
}
