<?php

namespace App\Http\Controllers;

use App\Models\EmailSettings;  // Use EmailSettings model
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;

class EmailSettingsController extends Controller
{
    /**
     * Display the email settings.
     */
    public function index(Request $request)
    {
        try {
            // Fetch the email settings (returning null if no records exist)
            $emailSettings = EmailSettings::first();

            // Check if the settings exist, and return a default structure if not
            if (!$emailSettings) {
                // Optionally, create a default setting here or return a 200 response with empty data
                return response()->json([
                    'success' => true,
                    'data' => [
                        'sender_name' => '',
                        'sender_email' => '',
                        'smtp_auth' => '',
                        'smtp_host' => '',
                        'smtp_username' => '',
                        'smtp_password' => '',
                        'smtp_encryption' => '',
                        'smtp_port' => '',
                    ],
                    'message' => 'No email settings found, returning default structure.'
                ], 200);
            }
            // Hide smtp_password for this response
            $emailSettings->makeHidden(['smtp_password']);
            // Return email settings in JSON format if they exist
            return response()->json([
                'success' => true,
                'data' => $emailSettings
            ], 200);

        } catch (\Exception $e) {
            // Log and return error message on failure
            Log::error('Error in EmailSettingsController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store or update email settings.
     */
    public function store(Request $request)
    {
        try {
            // Check if email settings record already exists
            $existingSettings = EmailSettings::first();

            // Define validation rules for email settings
            $validationRules = [
                'sender_name' => 'required|string|max:255',
                'sender_email' => 'required|email|max:255',
                'smtp_auth' => 'required|string|max:255',
                'smtp_host' => 'required|string|max:255',
                'smtp_username' => 'required|string|max:255',
                'smtp_password' => 'required|string|max:255',
                'smtp_encryption' => 'required|string|max:255',
                'smtp_port' => 'required|integer',
                'created_by' => 'nullable',
                'updated_by' => 'nullable',
            ];

            // Validate the incoming request
            $validated = $request->validate($validationRules);

            // Sanitize and normalize data
            $validated['smtp_encryption'] = strtoupper($validated['smtp_encryption']);

            // Check if we are updating or creating the settings
            if ($existingSettings) {
                // Update existing settings
                $validated['updated_by'] = auth()->user()->id;
                $existingSettings->update($validated);
                $action = 'Update';
                $message = 'Email settings updated successfully!';
                $emailSettings = $existingSettings;
            } else {
                // Create new email settings
                $validated['created_by'] = auth()->user()->id;
                $emailSettings = EmailSettings::create($validated);
                $action = 'Create';
                $message = 'Email settings created successfully!';
            }

            // Log action (creation or update)
            (new ApplicationLogController())->storeLog(
                $request,
                'EmailSettings',
                $action,
                "$action email settings with details: " . json_encode($validated),
                auth()->user()->id
            );

            // Return success response with the created or updated email settings
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $emailSettings,
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
                'message' => 'Failed to create or update email settings: ' . $exception->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            // Log and handle other exceptions
            Log::error('Email settings error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during email settings processing. Please try again.',
            ], 500);
        }
    }
}
