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
    $menuArray = $request->input('menuArray', []);

    try {
        // Get the authenticated user
        $authenticatedUser = auth()->user();
        
        // Check if user is authenticated
        if (!$authenticatedUser) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }
        
        $activeUserRole = $authenticatedUser->role_id;
        
        // Check if role_id exists
        if (!$activeUserRole) {
            return response()->json([
                'success' => false,
                'message' => 'User has no role assigned',
            ], 400);
        }

        // Query the permissions table
        $accessibleMenus = Permission::where('role_id', $activeUserRole)
            ->whereIn('menu', $menuArray)
            ->pluck('menu')
            ->toArray();

        return response()->json([
            'success' => true,
            'hasAccess' => $accessibleMenus,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'An error occurred while checking access: ' . $e->getMessage(),
            'trace' => $e->getTraceAsString(),
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
