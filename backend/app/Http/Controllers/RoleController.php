<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class RoleController extends Controller
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
            $query = Role::with(['users:id,name,role_id', 'user:id,name']); // Include 'users', 'user' relationships

            // Apply search filters if provided
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');

                // Join users table and search within both roles and users
                $query->where('roles.name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhereHas('users', function ($query) use ($searchTerm) {
                        $query->where('name', 'LIKE', '%' . $searchTerm . '%');
                    });
            }

            // Fetch the paginated data
            $roles = $query->orderBy('roles.id', 'DESC')->paginate($paginationSize);

            return response()->json($roles);
        } catch (\Exception $e) {
            Log::error('Error in RoleController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
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
                ],
                'environment' => 'required|in:PRODUCTION,TEST,DEVELOPMENT',
                'created_by' => 'nullable',
            ]);

            // Sanitize and normalize data
            $validated['name'] = ucwords($validated['name']);
            $validated['environment'] = strtoupper($validated['environment']);
            $validated['created_by'] = auth()->user()->id;

            // Create the new role
            $newRole = Role::create($validated);

            // Extract specific form inputs for logging
            $inputNew = $request->only(['name', 'environment']);

            // Log the creation
            (new ApplicationLogController())->storeLog(
                $request,
                'Role',
                'Create',
                'Created role with id: ' . $newRole->id . ', details: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Role created successfully!',
                'data' => [
                    'role' => $newRole,
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
                'message' => 'Failed to create role: ' . $exception->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error('Role creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during role creation. Please try again.',
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function show(Role $role)
    {
        // Customize the data to return only the necessary fields
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $role->id,
                'name' => $role->name,
                'environment' => $role->environment,
                'created_at' => $role->created_at->toDateTimeString(),
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Role $role)
    {
        try {
            Log::info('Request Data:', $request->all()); // Log the incoming data
            // Validate request data
            $validated = $request->validate([
                'name' => [
                    'required',
                    'min:3',
                    'max:255',
                ],
                'updated_by' => 'nullable',
            ]);

            // Sanitize and normalize data
            $validated['name'] = ucwords($validated['name']);
            $validated['updated_by'] = auth()->user()->id;

            // Extract specific stored data before update
            $inputStored = [
                'name' => $role->name,
                'environment' => $role->environment,
            ];

            // Update the role
            $role->update($validated);

            // Extract specific form inputs after update
            $inputNew = $request->only(['name', 'environment']);

            // Log the update
            (new ApplicationLogController())->storeLog(
                $request,
                'Role',
                'Update',
                'Updated role with id: ' . $role->id . ', from: ' . json_encode($inputStored) . ', to: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Role updated successfully!',
                'data' => [
                    'role' => $role,
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
            Log::error('Role update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the role update. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Role $role)
    {
        // Check if the role has attachments (e.g., users)
        if ($role->permissions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Role has attachments and cannot be deleted.'
            ], 400); // Bad Request
        }

        // Extract specific stored data before deletion
        $inputStored = [
            'name' => $role->name,
            'environment' => $role->environment,
        ];

        // Log the deleted role's information
        (new ApplicationLogController())->storeLog(
            $request,
            'Role',
            'SoftDelete',
            'Deleted role with id: ' . $role->id . ', details: ' . json_encode($inputStored) . '.',
            auth()->user()->id
        );

        // No attachments found, proceed with soft delete
        $role->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Role deleted successfully!'
        ], 200);
    }
    /**
     * Remove mass resource from storage.
     */
    public function massDestroy(Request $request)
    {
        // Validate that an array of role IDs is provided
        $validated = $request->validate([
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id', // Ensures each ID exists in the roles table
        ]);

        $roleIds = $validated['role_ids'];

        // Get the roles that are about to be deleted
        $roles = Role::whereIn('id', $roleIds)->get();

        foreach ($roles as $role) {
            // Check if the role has attachments (e.g., users)
            if ($role->permissions()->exists()) {
                // Skip deletion and log that this role cannot be deleted
                $undeletableRoles[] = [
                    'id' => $role->id,
                    'name' => $role->name,
                    'message' => 'Role has attachments and cannot be deleted.',
                ];
                continue;
            }

            // Log the deleted role's information
            $inputStored = [
                'name' => $role->name,
                'environment' => $role->environment,
            ];

            (new ApplicationLogController())->storeLog(
                $request,
                'Role',
                'SoftDelete',
                'Deleted role with id: ' . $role->id . ', details: ' . json_encode($inputStored) . '.',
                auth()->user()->id
            );

            // Perform soft delete
            $role->delete();
        }
        // If some roles were not deleted, include that information in the response
        if (!empty($undeletableRoles)) {
            return response()->json([
                'success' => false,
                'message' => 'Some roles could not be deleted due to attachments.',
                'undeletable_roles' => $undeletableRoles,
            ], 400); // Partial failure
        }

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Roles deleted successfully'
        ], 200);
    }
}
