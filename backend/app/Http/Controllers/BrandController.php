<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class BrandController extends Controller
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
            $query = Brand::with(['user:id,name','product:id,name']); // Include 'users', 'user' relationships

            // Apply search filters if provided
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');

                // Join users table and search within both products and users
                $query->where('brands.name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhereHas('user', function ($query) use ($searchTerm) {
                        $query->where('name', 'LIKE', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('product', function ($query) use ($searchTerm) {
                        $query->where('name', 'LIKE', '%' . $searchTerm . '%');
                    });
            }

            // Fetch the paginated data
            $brands = $query->orderBy('brands.id', 'DESC')->paginate($paginationSize);

            return response()->json($brands);
        } catch (\Exception $e) {
            Log::error('Error in BrandController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    public function getBrands()
    {
        // Start the query to select brands
        $query = Brand::select('id', 'name')
            ->distinct()
            ->orderBy('name', 'ASC');

        // Fetch brands
        $brands = $query->get(); // Execute the query to get the results

        return response()->json($brands);
    }

    public function getBrandsByProductId($productId)
    {
        $brands = Brand::where('product_id', $productId)->get();
        return response()->json($brands);
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
                'product_id' => 'required',
                'environment' => 'required|in:PRODUCTION,TEST,DEVELOPMENT',
                'created_by' => 'nullable',
            ],
            [
                'product_id.required' => 'The product  field is required.',
            ]);

            // Sanitize and normalize data
            $validated['name'] = ucwords($validated['name']);
            $validated['environment'] = strtoupper($validated['environment']);
            $validated['created_by'] = auth()->user()->id;

            // Create the new product
            $newBrand = Brand::create($validated);

            // Extract specific form inputs for logging
            $inputNew = $request->only(['name', 'product_id', 'environment']);

            // Log the creation
            (new ApplicationLogController())->storeLog(
                $request,
                'Brand',
                'Create',
                'Created brand with id: ' . $newBrand->id . ', details: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Brand created successfully!',
                'data' => [
                    'brand' => $newBrand,
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
                'message' => 'Failed to create brand: ' . $exception->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error('Brand creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during brand creation. Please try again.',
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function show(Brand $brand)
    {
        // Customize the data to return only the necessary fields
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $brand->id,
                'product_id' => $brand->product_id,
                'name' => $brand->name,
                'environment' => $brand->environment,
                'created_at' => $brand->created_at->toDateTimeString(),
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Brand $brand)
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
                'product_id' => 'required',
                'updated_by' => 'nullable',
            ],
            [
                'product_id.required' => 'The product  field is required.',
            ]);

            // Sanitize and normalize data
            $validated['name'] = ucwords($validated['name']);
            $validated['updated_by'] = auth()->user()->id;

            // Extract specific stored data before update
            $inputStored = [
                'name' => $brand->name,
                'product_id' => $brand->product_id,
                'environment' => $brand->environment,
            ];

            // Update the brand
            $brand->update($validated);

            // Extract specific form inputs after update
            $inputNew = $request->only(['name', 'product_id', 'environment']);

            // Log the update
            (new ApplicationLogController())->storeLog(
                $request,
                'Brand',
                'Update',
                'Updated brand with id: ' . $brand->id . ', from: ' . json_encode($inputStored) . ', to: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Brand updated successfully!',
                'data' => [
                    'brand' => $brand,
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
            Log::error('Brand update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the brand update. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Brand $brand)
    {
        // Extract specific stored data before deletion
        $inputStored = [
            'name' => $brand->name,
            'product_id' => $brand->product_id,
            'environment' => $brand->environment,
        ];

        // Log the deleted product's information
        (new ApplicationLogController())->storeLog(
            $request,
            'Brand',
            'SoftDelete',
            'Deleted brand with id: ' . $brand->id . ', details: ' . json_encode($inputStored) . '.',
            auth()->user()->id
        );

        // No attachments found, proceed with soft delete
        $brand->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Brand deleted successfully!'
        ], 200);
    }
    /**
     * Remove mass resource from storage.
     */
    public function massDestroy(Request $request)
    {
        // Validate that an array of product IDs is provided
        $validated = $request->validate([
            'brand_ids' => 'required|array',
            'brand_ids.*' => 'exists:brands,id', // Ensures each ID exists in the brands table
        ]);

        $brandIds = $validated['brand_ids'];

        // Get the brands that are about to be deleted
        $brands = Brand::whereIn('id', $brandIds)->get();

        foreach ($brands as $brand) {
            $undeletableBrands[] = [
                'id' => $brand->id,
                'name' => $brand->name,
                'message' => 'Brand has attachments and cannot be deleted.',
            ];

            // Log the deleted product_'s information
            $inputStored = [
                'name' => $brand->name,
                'product_id' => $brand->product_id,
                'environment' => $brand->environment,
            ];

            (new ApplicationLogController())->storeLog(
                $request,
                'Brand',
                'SoftDelete',
                'Deleted brand with id: ' . $brand->id . ', details: ' . json_encode($inputStored) . '.',
                auth()->user()->id
            );

            // Perform soft delete
            $brand->delete();
        }
        // If some brands were not deleted, include that information in the response
        if (!empty($undeletableBrands)) {
            return response()->json([
                'success' => false,
                'message' => 'Some brands could not be deleted due to attachments.',
                'undeletable_brands' => $undeletableBrands,
            ], 400); // Partial failure
        }

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Brands deleted successfully'
        ], 200);
    }
}
