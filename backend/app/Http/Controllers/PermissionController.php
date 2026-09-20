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
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
            }

            // Log the action
            $applicationLogController = new ApplicationLogController();
            $applicationLogController->storeLog($request, 'Permission', 'Create', 'Created permission: {' . $menuName . '}, for role :' . $role->name . '.', auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Menus assigned successfully.',
            ], 200);
        } catch (ValidationException $e) {
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
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An unexpected error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function accessMenu(Request $request)
    {
        $menuArray = $request->input('menuArray', []);

        try {
            // 1. Safely get the user
            $authenticatedUser = auth()->user();
            
            if (!$authenticatedUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not authenticated',
                ], 401);
            }
            
            // 2. Safely get the role ID (default to 1 if missing to prevent crashes)
            $activeUserRole = $authenticatedUser->role_id ?? 1;

            // 3. Query the permissions table
            $accessibleMenus = Permission::where('role_id', $activeUserRole)
                ->whereIn('menu', $menuArray)
                ->pluck('menu')
                ->toArray();

            return response()->json([
                'success' => true,
                'hasAccess' => $accessibleMenus,
            ], 200);
            
        } catch (\Throwable $e) { // Changed to Throwable to catch Fatal Errors too
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
        try {
            // Get the IDs of menus already assigned to the role
            $assignedMenuNames = Permission::where('role_id', $role->id)->pluck('menu')->toArray();

            // Return a JSON response with role, menus, and assigned menu IDs data
            return response()->json([
                'success' => true,
                'role' => $role,
                'assignedMenuNames' => $assignedMenuNames,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred: ' . $e->getMessage(),
            ], 500);
        }
    }
}