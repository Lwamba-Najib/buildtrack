<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AppearanceSettings;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class AppearanceSettingsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Fetch the appearance settings (assuming only one record exists)
            $appearanceSettings = AppearanceSettings::first();

            if (!$appearanceSettings) {
                // Return a default structure with placeholders if no settings are found
                return response()->json([
                    'success' => true,
                    'data' => [
                        'name' => '',
                        'logo' => null,
                        'favicon' => null,
                        'wallpaper' => null,
                    ],
                    'message' => 'No appearance settings found, returning default structure.'
                ], 200);
            }

            // Return the existing appearance settings in JSON format
            return response()->json([
                'success' => true,
                'data' => $appearanceSettings
            ], 200);

        } catch (\Exception $e) {
            // Log and return error message on failure
            Log::error('Error in AppearanceSettingsController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store or update appearance settings in storage.
     */
    public function store(Request $request)
    {
        try {
            // Check if appearance settings record already exists
            $existingSettings = AppearanceSettings::first();

            // Define validation rules
            $validationRules = [
                'name' => 'required|min:3|max:255',
                'created_by' => 'nullable',
            ];

            // Validate the request data
            $validated = $request->validate($validationRules);

            // Process fields
            $validated['name'] = ucwords($validated['name']);
            $validated['created_by'] = auth()->user()->id;

            // Handle logo file upload if present
            if ($request->hasFile('logo')) {
                if ($existingSettings && $existingSettings->logo) {
                    Storage::disk('public')->delete($existingSettings->logo);
                }
                $validated['logo'] = $request->file('logo')->store('logos', 'public');
                $validationRules['logo'] = 'image|mimes:jpg,jpeg,png|max:2048';
            }

            // Handle favicon file upload if present
            if ($request->hasFile('favicon')) {
                if ($existingSettings && $existingSettings->favicon) {
                    Storage::disk('public')->delete($existingSettings->favicon);
                }
                $validated['favicon'] = $request->file('favicon')->store('favicons', 'public');
                $validationRules['favicon'] = 'image|mimes:jpg,jpeg,png,ico|max:2048';
            }

            // Handle wallpaper file upload if present
            if ($request->hasFile('wallpaper')) {
                if ($existingSettings && $existingSettings->wallpaper) {
                    Storage::disk('public')->delete($existingSettings->wallpaper);
                }
                $validated['wallpaper'] = $request->file('wallpaper')->store('wallpapers', 'public');
                $validationRules['wallpaper'] = 'image|mimes:jpg,jpeg,png,ico|max:2048';
            }

            // Determine if we are updating or creating the record
            if ($existingSettings) {
                // Update existing settings
                $existingSettings->update($validated);
                $action = 'Update';
                $appearanceSetting = $existingSettings;
                $message = 'Settings updated successfully!';
            } else {
                // Create new appearance settings record
                $appearanceSetting = AppearanceSettings::create($validated);
                $action = 'Create';
                $message = 'Settings created successfully!';
            }

            // Log action (creation or update)
            (new ApplicationLogController())->storeLog(
                $request,
                'AppearanceSettings',
                $action,
                "$action appearance settings with details: " . json_encode($validated) . '.',
                auth()->user()->id
            );

            // Return success response with the created or updated settings
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'appearancesetting' => $appearanceSetting,
                ],
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
                'message' => 'Failed to create or update appearance settings: ' . $exception->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            // Log and handle other exceptions
            Log::error('Appearance settings error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during appearance settings processing. Please try again.',
            ], 500);
        }
    }
}
