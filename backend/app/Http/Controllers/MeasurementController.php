<?php

namespace App\Http\Controllers;

use App\Models\Measurement;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class MeasurementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Validate the request, allowing pagination_size to be null or an integer with a minimum value of 5
            $validated = $request->validate([
                'pagination_size' => 'nullable|integer|min:5',
            ]);

            // Use the provided pagination_size or default to PaginationSize::SMALL if not provided
            $paginationSize = $validated['pagination_size'] ?? PaginationSize::SMALL->value;

            // Start the query
            $query = Measurement::with(['user:id,name']); // Include 'users', 'user' relationships

            // Apply search filters if provided
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');

                // Join users table and search within both measurements and users
                $query->where('measurements.name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhereHas('user', function ($query) use ($searchTerm) {
                        $query->where('name', 'LIKE', '%' . $searchTerm . '%');
                    });
            }

            // Fetch the paginated data
            $measurements = $query->orderBy('measurements.id', 'DESC')->paginate($paginationSize);

            return response()->json($measurements);
        } catch (\Exception $e) {
            Log::error('Error in MeasurementController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    public function getMeasurements()
    {
        // Start the query to select measurements
        $query = Measurement::select('id', 'name')
            ->distinct()
            ->orderBy('name', 'ASC');

        // Fetch measurements
        $measurements = $query->get(); // Execute the query to get the results

        return response()->json($measurements);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate request data
            $validated = $request->validate([
                'name' => [
                    'required',
                    'min:3',
                    'max:255',
                    'unique:measurements',
                ],
                'environment' => 'required|in:PRODUCTION,TEST,DEVELOPMENT',
                'created_by' => 'nullable',
            ]);

            // Sanitize and normalize data
            $validated['name'] = ucwords($validated['name']);
            $validated['environment'] = strtoupper($validated['environment']);
            $validated['created_by'] = auth()->user()->id;

            // Create the new measurement
            $newMeasurement = Measurement::create($validated);

            // Extract specific form inputs for logging
            $inputNew = $request->only(['name', 'environment']);

            // Log the creation
            (new ApplicationLogController())->storeLog(
                $request,
                'Measurement',
                'Create',
                'Created measurement with id: ' . $newMeasurement->id . ', details: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Measurement created successfully!',
                'data' => [
                    'measurement' => $newMeasurement,
                ],
            ], 200);

        } catch (ValidationException $e) {
            // Catch validation errors and return specific messages
            return response()->json([
                'success' => false,
                'message' => 'Validation Error: ' . $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);

        } catch (QueryException $exception) {
            // Handle database query exception
            return response()->json([
                'success' => false,
                'message' => 'Failed to create measurement: ' . $exception->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error('Measurement creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during measurement creation. Please try again.',
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function show(Measurement $measurement)
    {
        // Customize the data to return only the necessary fields
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $measurement->id,
                'name' => $measurement->name,
                'environment' => $measurement->environment,
                'created_at' => $measurement->created_at->toDateTimeString(),
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Measurement $measurement)
    {
        try {
            //Log::info('Request Data:', $request->all()); // Log the incoming data
            // Validate request data
            $validated = $request->validate([
                'name' => [
                    'required',
                    'min:3',
                    'max:255',
                    Rule::unique('measurements')->ignore($measurement->id), // Ignore current record
                ],
                'updated_by' => 'nullable',
            ]);

            // Sanitize and normalize data
            $validated['name'] = ucwords($validated['name']);
            $validated['updated_by'] = auth()->user()->id;

            // Extract specific stored data before update
            $inputStored = [
                'name' => $measurement->name,
                'environment' => $measurement->environment,
            ];

            // Update the measurement
            $measurement->update($validated);

            // Extract specific form inputs after update
            $inputNew = $request->only(['name', 'environment']);

            // Log the update
            (new ApplicationLogController())->storeLog(
                $request,
                'Measurement',
                'Update',
                'Updated measurement with id: ' . $measurement->id . ', from: ' . json_encode($inputStored) . ', to: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Measurement updated successfully!',
                'data' => [
                    'measurement' => $measurement,
                ],
            ], 200);
        } catch (ValidationException $e) {
            // Catch validation errors and return specific messages
            return response()->json([
                'success' => false,
                'message' => 'Validation Error: ' . $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error('measurement update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the measurement update. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Measurement $measurement)
    {
        // Check if the measurement has attachments (e.g., users)
        if ($measurement->permissions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Measurement has attachments and cannot be deleted.'
            ], 400); // Bad Request
        }

        // Extract specific stored data before deletion
        $inputStored = [
            'name' => $measurement->name,
            'environment' => $measurement->environment,
        ];

        // Log the deleted measurement's information
        (new ApplicationLogController())->storeLog(
            $request,
            'Measurement',
            'SoftDelete',
            'Deleted measurement with id: ' . $measurement->id . ', details: ' . json_encode($inputStored) . '.',
            auth()->user()->id
        );

        // No attachments found, proceed with soft delete
        $measurement->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Measurement deleted successfully!'
        ], 200);
    }
    /**
     * Remove mass resource from storage.
     */
    public function massDestroy(Request $request)
    {
        // Validate that an array of measurement IDs is provided
        $validated = $request->validate([
            'measurement_ids' => 'required|array',
            'measurement_ids.*' => 'exists:measurements,id', // Ensures each ID exists in the measurements table
        ]);

        $measurementIds = $validated['measurement_ids'];

        // Get the measurements that are about to be deleted
        $measurements = Measurement::whereIn('id', $measurementIds)->get();

        foreach ($measurements as $measurement) {
            // Check if the measurement has attachments (e.g., users)
            if ($measurement->permissions()->exists()) {
                // Skip deletion and log that this measurement cannot be deleted
                $undeletableMeasurements[] = [
                    'id' => $measurement->id,
                    'name' => $measurement->name,
                    'message' => 'Measurement has attachments and cannot be deleted.',
                ];
                continue;
            }

            // Log the deleted measurement's information
            $inputStored = [
                'name' => $measurement->name,
                'environment' => $measurement->environment,
            ];

            (new ApplicationLogController())->storeLog(
                $request,
                'Measurement',
                'SoftDelete',
                'Deleted measurement with id: ' . $measurement->id . ', details: ' . json_encode($inputStored) . '.',
                auth()->user()->id
            );

            // Perform soft delete
            $measurement->delete();
        }
        // If some measurements were not deleted, include that information in the response
        if (!empty($undeletableMeasurements)) {
            return response()->json([
                'success' => false,
                'message' => 'Some measurements could not be deleted due to attachments.',
                'undeletable_measurements' => $undeletableMeasurements,
            ], 400); // Partial failure
        }

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Measurements deleted successfully'
        ], 200);
    }
}
