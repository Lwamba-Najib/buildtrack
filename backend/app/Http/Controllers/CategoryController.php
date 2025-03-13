<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class CategoryController extends Controller
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
            $query = Category::with(['user:id,name']); // Include 'users', 'user' relationships

            // Apply search filters if provided
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');

                // Join users table and search within both categories and users
                $query->where('categories.name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhereHas('user', function ($query) use ($searchTerm) {
                        $query->where('name', 'LIKE', '%' . $searchTerm . '%');
                    });
            }

            // Fetch the paginated data
            $categories = $query->orderBy('categories.id', 'DESC')->paginate($paginationSize);

            return response()->json($categories);
        } catch (\Exception $e) {
            Log::error('Error in CategoryController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    public function getCategories()
    {
        // Start the query to select categories
        $query = Category::select('id', 'name')
            ->distinct()
            ->orderBy('name', 'ASC');

        // Fetch categories
        $categories = $query->get(); // Execute the query to get the results

        return response()->json($categories);
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

            // Create the new category
            $newCategory = Category::create($validated);

            // Extract specific form inputs for logging
            $inputNew = $request->only(['name', 'environment']);

            // Log the creation
            (new ApplicationLogController())->storeLog(
                $request,
                'Category',
                'Create',
                'Created category with id: ' . $newCategory->id . ', details: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Category created successfully!',
                'data' => [
                    'category' => $newCategory,
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
                'message' => 'Failed to create category: ' . $exception->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error('Category creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during category creation. Please try again.',
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function show(Category $category)
    {
        // Customize the data to return only the necessary fields
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'environment' => $category->environment,
                'created_at' => $category->created_at->toDateTimeString(),
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Category $category)
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
                'name' => $category->name,
                'environment' => $category->environment,
            ];

            // Update the category
            $category->update($validated);

            // Extract specific form inputs after update
            $inputNew = $request->only(['name', 'environment']);

            // Log the update
            (new ApplicationLogController())->storeLog(
                $request,
                'Category',
                'Update',
                'Updated category with id: ' . $category->id . ', from: ' . json_encode($inputStored) . ', to: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Category updated successfully!',
                'data' => [
                    'category' => $category,
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
            Log::error('category update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the category update. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Category $category)
    {
        // Check if the category has attachments (e.g., users)
        if ($category->permissions()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Category has attachments and cannot be deleted.'
            ], 400); // Bad Request
        }

        // Extract specific stored data before deletion
        $inputStored = [
            'name' => $category->name,
            'environment' => $category->environment,
        ];

        // Log the deleted category's information
        (new ApplicationLogController())->storeLog(
            $request,
            'Category',
            'SoftDelete',
            'Deleted category with id: ' . $category->id . ', details: ' . json_encode($inputStored) . '.',
            auth()->user()->id
        );

        // No attachments found, proceed with soft delete
        $category->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully!'
        ], 200);
    }
    /**
     * Remove mass resource from storage.
     */
    public function massDestroy(Request $request)
    {
        // Validate that an array of category IDs is provided
        $validated = $request->validate([
            'category_ids' => 'required|array',
            'category_ids.*' => 'exists:categories,id', // Ensures each ID exists in the categories table
        ]);

        $categoryIds = $validated['category_ids'];

        // Get the categories that are about to be deleted
        $categories = Category::whereIn('id', $categoryIds)->get();

        foreach ($categories as $category) {
            // Check if the category has attachments (e.g., users)
            if ($category->permissions()->exists()) {
                // Skip deletion and log that this category cannot be deleted
                $undeletableCategories[] = [
                    'id' => $category->id,
                    'name' => $category->name,
                    'message' => 'Category has attachments and cannot be deleted.',
                ];
                continue;
            }

            // Log the deleted category's information
            $inputStored = [
                'name' => $category->name,
                'environment' => $category->environment,
            ];

            (new ApplicationLogController())->storeLog(
                $request,
                'Category',
                'SoftDelete',
                'Deleted category with id: ' . $category->id . ', details: ' . json_encode($inputStored) . '.',
                auth()->user()->id
            );

            // Perform soft delete
            $category->delete();
        }
        // If some categories were not deleted, include that information in the response
        if (!empty($undeletableCategories)) {
            return response()->json([
                'success' => false,
                'message' => 'Some categories could not be deleted due to attachments.',
                'undeletable_categories' => $undeletableCategories,
            ], 400); // Partial failure
        }

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Categories deleted successfully'
        ], 200);
    }
}
