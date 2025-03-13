<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class PermissionController extends Controller
{
    public function assignMenus(Request $request, Role $role)
    {
        try {
            $selectedMenuIds = $request->input('selectedMenus', []);

            // Delete existing permissions for the role
            Permission::where('role_id', $role->id)->delete();

            // Create new permissions for the selected menus
            foreach ($selectedMenuIds as $menuName) {
                Permission::create([
                    'role_id' => $role->id,
                    'menu' => $menuName,
                    'created_by' => auth()->user()->id,
                    'updated_by' => auth()->user()->id,
                ]);
            }

            // Log the action
            $applicationLogController = new ApplicationLogController(); // Instantiate or inject as needed
            $applicationLogController->storeLog($request, 'Permission', 'Create', 'Created permission: {' . $menuName . '}, for role :' . $role->name . '.', auth()->user()->id);

            return response()->json([
                'success' => true,
                'message' => 'Menus assigned successfully.',
            ], 200);
        } catch (ValidationException $e) {
            // Catch validation errors and return specific messages
            return response()->json([
                'success' => false,
                'message' => 'Validation Error: ' . $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);

        } catch (QueryException $exception) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while assigning menus: ' . $exception->getMessage(),
            ], 500);
        }
    }

    public function accessMenu(Request $request)
    {
        $menuArray = $request->input('menuArray', []); // Get the array of menu names from the request

        try {
            // Get the authenticated user's role ID and client ID
            $authenticatedUser = auth()->user();
            $activeUserRole = $authenticatedUser->role_id;
       
            // Query the permissions table to find accessible menu names for the role
            $accessibleMenus = Permission::where('role_id', $activeUserRole)
                ->whereIn('menu', $menuArray) // Filter only the menus provided in the request
                ->pluck('menu') // Get the names of the menus
                ->toArray(); // Convert to an array

            // Return the result as a JSON response
            return response()->json([
                'success' => true,
                'hasAccess' => $accessibleMenus, // Return the array of accessible menu names
            ], 200);
        } catch (\Exception $e) {
            // Handle errors gracefully
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while checking access: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function assignedMenus(Role $role)
    {
        // Get the IDs of menus already assigned to the role
        $assignedMenuNames = Permission::where('role_id', $role->id)->pluck('menu')->toArray();

        // Return a JSON response with role, menus, and assigned menu IDs data
        return response()->json([
            'role' => $role,
            'assignedMenuNames' => $assignedMenuNames,
        ]);
    }
}
