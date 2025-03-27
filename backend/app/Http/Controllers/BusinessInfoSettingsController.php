<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Models\BusinessInfoSettings;  // Use BusinessInfoSettings model

class BusinessInfoSettingsController extends Controller
{
    /**
     * Display the business info settings.
     */
    public function index(Request $request)
    {
        try {
            // Fetch the business info settings (returning null if no records exist)
            $businessInfoSettings = BusinessInfoSettings::first();

            // Check if the settings exist, and return a default structure if not
            if (!$businessInfoSettings) {
                // Optionally, create a default setting here or return a 200 response with empty data
                return response()->json([
                    'success' => true,
                    'data' => [
                        'business_name' => '',
                        'business_reg_number' => '',
                        'business_tin' => '',
                        'business_slogan' => '',
                        'business_address' => '',
                        'business_email' => '',
                        'business_contact' => '',
                        'business_website' => '',
                        'business_legal_disclaimer' => '',
                    ],
                    'message' => 'No business info settings found, returning default structure.'
                ], 200);
            }
            // Return business info settings in JSON format if they exist
            return response()->json([
                'success' => true,
                'data' => $businessInfoSettings
            ], 200);

        } catch (\Exception $e) {
            // Log and return error message on failure
            Log::error('Error in BusinessInfoSettingsController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store or update business info settings.
     */
    public function store(Request $request)
    {
        try {
            // Check if business info settings record already exists
            $existingSettings = BusinessInfoSettings::first();

            // Define validation rules for business info settings
            $validationRules = [
                'business_name' => 'required|string|max:255',
                'business_reg_number' => ['required', Rule::unique('business_info_settings')->ignore(optional($existingSettings)->id)],
                'business_tin' => ['required', Rule::unique('business_info_settings')->ignore(optional($existingSettings)->id)],
                'business_slogan' => 'nullable',
                'business_address' => 'required|string|max:255',
                'business_email' => ['required', 'email', Rule::unique('business_info_settings')->ignore(optional($existingSettings)->id)],
                'business_contact' => ['required', Rule::unique('business_info_settings')->ignore(optional($existingSettings)->id)],
                'business_website' => 'nullable',
                'business_legal_disclaimer' => 'nullable',
                'created_by' => 'nullable',
                'updated_by' => 'nullable',
            ];

            // Validate the incoming request
            $validated = $request->validate($validationRules);

            // convert to uppercase
            $validated['business_name'] = strtoupper($validated['business_name']);

            // Check if we are updating or creating the settings
            if ($existingSettings) {
                // Update existing settings
                $validated['updated_by'] = auth()->user()->id;
                $existingSettings->update($validated);
                $action = 'Update';
                $message = 'Business info settings updated successfully!';
                $businessInfoSettings = $existingSettings;
            } else {
                // Create new business info settings
                $validated['created_by'] = auth()->user()->id;
                $businessInfoSettings = BusinessInfoSettings::create($validated);
                $action = 'Create';
                $message = 'Business nfo settings created successfully!';
            }

            // Log action (creation or update)
            (new ApplicationLogController())->storeLog(
                $request,
                'BusinessInfoSettings',
                $action,
                "$action business info settings with details: " . json_encode($validated),
                auth()->user()->id
            );

            // Return success response with the created or updated business nfo settings
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => $businessInfoSettings,
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
                'message' => 'Failed to create or update business info settings: ' . $exception->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            // Log and handle other exceptions
            Log::error('Business nfo settings error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during business info settings processing. Please try again.',
            ], 500);
        }
    }

    public function getBusinessInfoSettings()
    {
        try {
            $businessInfoSettings = BusinessInfoSettings::select([
                'business_name',
                'business_reg_number',
                'business_tin',
                'business_slogan',
                'business_address',
                'business_email',
                'business_contact',
                'business_website',
                'business_legal_disclaimer'
            ])->first(); // Fetch the first record

            return response()->json([
                'success' => true,
                'data' => $businessInfoSettings  // Return the object directly
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching BusinessInfoSettings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch business info settings'
            ], 500);
        }
    }
}
