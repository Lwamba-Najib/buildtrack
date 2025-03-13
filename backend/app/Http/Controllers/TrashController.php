<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\Stock;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;

class TrashController extends Controller
{
    /**
     * Display a listing of the deleted resources.
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
            $search = $request->input('search', '');

            // Fetch trashed Roles
            $deletedRoles = Role::onlyTrashed()
                ->when($search, function ($query, $search) {
                    return $query->where('name', 'like', "%{$search}%");
                })
                ->get()
                ->map(function ($item) {
                    $item->model = 'Role';
                    $item->controller = 'RoleController';
                    return $item;
                });

            // Fetch trashed Users
            $deletedUsers = User::onlyTrashed()
                ->when($search, function ($query, $search) {
                    return $query->where('name', 'like', "%{$search}%");
                })
                ->get()
                ->map(function ($item) {
                    $item->model = 'User';
                    $item->controller = 'UserController';
                    return $item;
                });

            // Fetch trashed Stocks
            $deletedStocks = Stock::onlyTrashed()
                ->when($search, function ($query, $search) {
                    return $query->where('name', 'like', "%{$search}%");
                })
                ->get()
                ->map(function ($item) {
                    $item->model = 'Stock';
                    $item->controller = 'StockController';
                    return $item;
                });

            // Combine all deleted records into a single collection
            $allDeletedRecords = $deletedRoles
                ->merge($deletedUsers)
                ->merge($deletedStocks);

            // Paginate the combined collection
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $perPage = $paginationSize;
            $currentPageItems = $allDeletedRecords->slice(($currentPage - 1) * $perPage, $perPage)->all();
            $paginatedItems = new LengthAwarePaginator($currentPageItems, $allDeletedRecords->count(), $perPage);
            $paginatedItems->setPath($request->url());
            $paginatedItems->appends($request->query());

            return response()->json($paginatedItems);
        } catch (\Exception $e) {
            Log::error('Error in TrashController index: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Restore a deleted resource.
     */
    public function restore(Request $request, $model, $id)
    {
        try {
            // Determine which model to restore based on $model parameter
            switch ($model) {
                case 'Role':
                    $restoredModel = Role::withTrashed()->findOrFail($id);
                    break;
                case 'User':
                    $restoredModel = User::withTrashed()->findOrFail($id);
                    break;
                case 'Stock':
                    $restoredModel = Stock::withTrashed()->findOrFail($id);
                    break;
                default:
                    return response()->json([
                        'success' => false,
                        'message' => 'Model not found'
                    ], 404);
            }

            // Extract specific stored data and exclude certain columns
            $inputStored = $restoredModel->toArray();
            $inputStored = Arr::except($inputStored, ['created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at']);

            // Log Extracted data
            (new ApplicationLogController())->storeLog(
                $request,
                ucfirst($model),
                'Restore',
                'Restored ' . $model . ' with id: ' . $id . ', details: ' . json_encode($inputStored) . '.',
                auth()->user()->id
            );

            // Restore the model
            $restoredModel->restore();

            return response()->json([
                'success' => true,
                'message' => ucfirst($model) . ' restored successfully!'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in TrashController restore: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Permanently delete a resource.
     */
    public function destroy(Request $request, $model, $id)
    {
        try {
            $modelClass = 'App\\Models\\' . ucfirst($model);
            $record = $modelClass::withTrashed()->findOrFail($id);

            // Extract specific stored data
            $inputStored = $record->toArray();

            // Log Extracted data
            (new ApplicationLogController())->storeLog(
                $request,
                ucfirst($model),
                'HardDelete',
                'Permanently deleted ' . $model . ' with id: ' . $id . ', details: ' . json_encode($inputStored) . '.',
                auth()->user()->id
            );

            // Permanently delete the record
            $record->forceDelete();

            return response()->json([
                'success' => true,
                'message' => ucfirst($model) . ' permanently deleted.'
            ], 200);
        } catch (\Exception $e) {
            Log::error('Error in TrashController destroy: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }
}
