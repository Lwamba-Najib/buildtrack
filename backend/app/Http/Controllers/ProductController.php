<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
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
            $query = Product::with(['user:id,name','category:id,name']); // Include 'users', 'user' relationships

            // Apply search filters if provided
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');

                // Join users table and search within both products and users
                $query->where('products.name', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhereHas('user', function ($query) use ($searchTerm) {
                        $query->where('name', 'LIKE', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('category', function ($query) use ($searchTerm) {
                        $query->where('name', 'LIKE', '%' . $searchTerm . '%');
                    });
            }

            // Fetch the paginated data
            $products = $query->orderBy('products.id', 'DESC')->paginate($paginationSize);

            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Error in ProductController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    public function getProducts()
    {
        // Start the query to select products
        $query = Product::select('id', 'name')
            ->distinct()
            ->orderBy('name', 'ASC');

        // Fetch products
        $products = $query->get(); // Execute the query to get the results

        return response()->json($products);
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
                    // Custom rule to check for duplicate product name and category ID
                    function ($attribute, $value, $fail) use ($request) {
                        $existingProduct = Product::where('name', $value)
                            ->where('category_id', $request->category_id)
                            ->first();

                        if ($existingProduct) {
                            $fail('The combination of product name and category ID already exists.');
                        }
                    },
                ],
                'category_id' => 'required',
                'environment' => 'required|in:PRODUCTION,TEST,DEVELOPMENT',
                'created_by' => 'nullable',
            ],
            [
                'category_id.required' => 'The category field is required.',
            ]);

            // Sanitize and normalize data
            $validated['name'] = ucwords($validated['name']);
            $validated['environment'] = strtoupper($validated['environment']);
            $validated['created_by'] = auth()->user()->id;

            // Create the new category
            $newProduct = Product::create($validated);

            // Extract specific form inputs for logging
            $inputNew = $request->only(['name', 'category_id', 'environment']);

            // Log the creation
            (new ApplicationLogController())->storeLog(
                $request,
                'Product ',
                'Create',
                'Created product  with id: ' . $newProduct->id . ', details: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Product  created successfully!',
                'data' => [
                    'product' => $newProduct,
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
                'message' => 'Failed to create product : ' . $exception->getMessage(),
            ], 500);

        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error('Product  creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during product  creation. Please try again.',
            ], 500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function show(Product $product)
    {
        // Customize the data to return only the necessary fields
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $product->id,
                'category_id' => $product->category_id,
                'name' => $product->name,
                'environment' => $product->environment,
                'created_at' => $product->created_at->toDateTimeString(),
            ]
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        try {
            //Log::info('Request Data:', $request->all()); // Log the incoming data
            // Validate request data
            $validated = $request->validate([
                'name' => [
                    'required',
                    'min:3',
                    'max:255',
                    // Custom rule to check for duplicate product name and category ID
                    function ($attribute, $value, $fail) use ($request, $product) {
                        $existingProduct = Product::where('name', $value)
                            ->where('category_id', $request->category_id)
                            ->where('id', '!=', $product->id) // Exclude the current product
                            ->first();

                        if ($existingProduct) {
                            $fail('The combination of product name and category ID already exists.');
                        }
                    },
                ],
                'category_id' => 'required',
                'updated_by' => 'nullable',
            ],
            [
                'category_id.required' => 'The category field is required.',
            ]);

            // Sanitize and normalize data
            $validated['name'] = ucwords($validated['name']);
            $validated['updated_by'] = auth()->user()->id;

            // Extract specific stored data before update
            $inputStored = [
                'name' => $product->name,
                'category_id' => $product->category_id,
                'environment' => $product->environment,
            ];

            // Update the product
            $product->update($validated);

            // Extract specific form inputs after update
            $inputNew = $request->only(['name', 'category_id', 'environment']);

            // Log the update
            (new ApplicationLogController())->storeLog(
                $request,
                'Product ',
                'Update',
                'Updated product  with id: ' . $product->id . ', from: ' . json_encode($inputStored) . ', to: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Product  updated successfully!',
                'data' => [
                    'product' => $product,
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
            Log::error('product  update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during the product  update. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Product $product)
    {
        // Extract specific stored data before deletion
        $inputStored = [
            'name' => $product->name,
            'environment' => $product->environment,
        ];

        // Log the deleted product's information
        (new ApplicationLogController())->storeLog(
            $request,
            'Product ',
            'SoftDelete',
            'Deleted product  with id: ' . $product->id . ', details: ' . json_encode($inputStored) . '.',
            auth()->user()->id
        );

        // No attachments found, proceed with soft delete
        $product->delete();

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'Product  deleted successfully!'
        ], 200);
    }
    /**
     * Remove mass resource from storage.
     */
    public function massDestroy(Request $request)
    {
        // Validate that an array of product IDs is provided
        $validated = $request->validate([
            'product_ids' => 'required|array',
            'product_ids.*' => 'exists:products,id', // Ensures each ID exists in the products table
        ]);

        $productIds = $validated['product_ids'];

        // Get the products that are about to be deleted
        $products = Product::whereIn('id', $productIds)->get();

        foreach ($products as $product) {
            $undeletableProductPatalogs[] = [
                'id' => $product->id,
                'name' => $product->name,
                'message' => 'Product  has attachments and cannot be deleted.',
            ];

            // Log the deleted product's information
            $inputStored = [
                'name' => $product->name,
                'category_id' => $product->category_id,
                'environment' => $product->environment,
            ];

            (new ApplicationLogController())->storeLog(
                $request,
                'Product ',
                'SoftDelete',
                'Deleted product  with id: ' . $product->id . ', details: ' . json_encode($inputStored) . '.',
                auth()->user()->id
            );

            // Perform soft delete
            $product->delete();
        }
        // If some products were not deleted, include that information in the response
        if (!empty($undeletableProductPatalogs)) {
            return response()->json([
                'success' => false,
                'message' => 'Some products could not be deleted due to attachments.',
                'undeletable_products' => $undeletableProductPatalogs,
            ], 400); // Partial failure
        }

        // Return success response
        return response()->json([
            'success' => true,
            'message' => 'products deleted successfully'
        ], 200);
    }
}
