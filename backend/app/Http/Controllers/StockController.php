<?php

namespace App\Http\Controllers;


use App\Models\Stock;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
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
            $query = Stock::with(['product', 'brand', 'measurement', 'supplier', 'createdBy', 'updatedBy']);

            // Apply search filters
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');
                $query->where(function ($query) use ($searchTerm) {
                    $query->whereHas('product', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('brand', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('supplier', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', '%' . $searchTerm . '%');
                    })
                    ->orWhere('quantity', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('unit_price', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('total_cost', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('sale_price', 'LIKE', '%' . $searchTerm . '%')
                    ->orWhere('min_stock_level', 'LIKE', '%' . $searchTerm . '%');
                });
            }

            // Apply date filter
            if (
                $request->has('startDate') && !empty($request->input('startDate')) &&
                $request->has('endDate') && !empty($request->input('endDate'))
            ) {

                $startDate = $request->input('startDate');
                $endDate = $request->input('endDate');

                // Use whereDate with whereBetween for date-only filtering
                $query->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);

            } elseif ($request->has('startDate') && !empty($request->input('startDate'))) {
                // If only startDate is provided, filter records from that date onward (date only)
                $query->whereDate('created_at', '>=', $request->input('startDate'));

            } elseif ($request->has('endDate') && !empty($request->input('endDate'))) {
                // If only endDate is provided, filter records up to that date (date only)
                $query->whereDate('created_at', '<=', $request->input('endDate'));
            }

            // Fetch the paginated data
            $stocks = $query->orderBy('id', 'DESC')->paginate($paginationSize);

            return response()->json($stocks);
        } catch (\Exception $e) {
            Log::error('Error in Listing Stocks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
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
            $validated = request()->validate([
                'product_id' => 'required|exists:products,id',
                'brand_id' => 'required|exists:brands,id',
                'measurement_id' => 'required|exists:measurements,id',
                'quantity' => 'required|integer|min:1',
                'unit_price' => 'required|integer|min:1',
                'sale_price' => 'required|integer|min:1',
                'min_stock_level' => 'required|integer|min:1',
                'supplier_id' => 'required|exists:suppliers,id',
                'stock_date' => 'required|date',
                'environment' => 'required|in:PRODUCTION,TEST,DEVELOPMENT',
                'created_by' => 'nullable',
            ]);

            // Calculate total_cost
            $validated['total_cost'] = $validated['quantity'] * $validated['unit_price'];

            // Set created_by to the current user
            $validated['created_by'] = auth()->user()->id;

            // Start a database transaction
            DB::beginTransaction();

            // Create stock
            $newStock = Stock::create($validated);

            // Update stock balance
            $stockBalance = StockBalance::firstOrCreate(
                [
                    'product_id' => $validated['product_id'],
                    'brand_id' => $validated['brand_id'],
                    'measurement_id' => $validated['measurement_id'],
                ],
                [
                    'balance' => 0,
                ]
            );

            $stockBalance->increment('balance', $validated['quantity']);

            // Commit the transaction
            DB::commit();

            // Log the creation
            (new ApplicationLogController())->storeLog(
                $request,
                'Stock',
                'Create',
                'Created stock with id: ' . $newStock->id . ', details: ' . json_encode($validated) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Stock created successfully!',
                'data' => [
                    'stock' => $newStock,
                ],
            ], 200);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            Log::error('Stock creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during stock creation. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Stock $stock)
    {
        try {
            // Validate request data
            $validated = request()->validate([
                'product_id' => 'required|exists:products,id',
                'brand_id' => 'required|exists:brands,id',
                'measurement_id' => 'required|exists:measurements,id',
                'quantity' => 'required|integer|min:1',
                'unit_price' => 'required|integer|min:1',
                'sale_price' => 'required|integer|min:1',
                'min_stock_level' => 'required|integer|min:1',
                'supplier_id' => 'required|exists:suppliers,id',
                'stock_date' => 'required|date',
                'updated_by' => 'nullable',
            ]);

            // Calculate total_cost
            $validated['total_cost'] = $validated['quantity'] * $validated['unit_price'];

            // Set updated_by to the current user
            $validated['updated_by'] = auth()->user()->id;

            // Start a database transaction
            DB::beginTransaction();

            // Update stock balance
            $stockBalance = StockBalance::firstOrCreate(
                [
                    'product_id' => $validated['product_id'],
                    'brand_id' => $validated['brand_id'],
                    'measurement_id' => $validated['measurement_id'],
                ],
                [
                    'balance' => 0,
                ]
            );

            // Adjust balance based on the difference in quantity
            $quantityDifference = $validated['quantity'] - $stock->quantity;
            $stockBalance->increment('balance', $quantityDifference);

            // Update the stock
            $stock->update($validated);

            // Commit the transaction
            DB::commit();

            // Log the update
            (new ApplicationLogController())->storeLog(
                $request,
                'Stock',
                'Update',
                'Updated stock with id: ' . $stock->id . ', from: ' . json_encode($stock->toArray()) . ', to: ' . json_encode($validated) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Stock updated successfully!',
                'data' => [
                    'stock' => $stock,
                ],
            ], 200);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            Log::error('Stock update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during stock update. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Stock $stock)
    {
        try {
            // Start a database transaction
            DB::beginTransaction();

            // Update stock balance
            $stockBalance = StockBalance::where('product_id', $stock->product_id)
                ->where('brand_id', $stock->brand_id)
                ->where('measurement_id', $stock->measurement_id)
                ->first();

            if ($stockBalance) {
                $stockBalance->decrement('balance', $stock->quantity);
            }

            // Log the deleted stock's information
            $inputStored = [
                'product_id' => $stock->product_id,
                'brand_id' => $stock->brand_id,
                'measurement_id' => $stock->measurement_id,
                'quantity' => $stock->quantity,
                'unit_price' => $stock->unit_price,
                'total_cost' => $stock->total_cost,
                'sale_price' => $stock->sale_price,
                'min_stock_level' => $stock->min_stock_level,
                'supplier_id' => $stock->supplier_id,
                'stock_date' => $stock->stock_date,
                'environment' => $stock->environment,
            ];

            (new ApplicationLogController())->storeLog(
                $request,
                'Stock',
                'SoftDelete',
                'Deleted stock with id: ' . $stock->id . ', details: ' . json_encode($inputStored) . '.',
                auth()->user()->id
            );

            // Delete the stock
            $stock->delete();

            // Commit the transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stock deleted successfully'
            ], 200);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();

            Log::error('Stock deletion error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during stock deletion. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove mass resources from storage.
     */
    public function massDelete(Request $request)
    {
        try {
            // Validate that an array of stock IDs is provided
            $validated = $request->validate([
                'stock_ids' => 'required|array',
                'stock_ids.*' => 'exists:stocks,id', // Ensures each ID exists in the stocks table
            ]);

            $stockIds = $validated['stock_ids'];

            // Start a database transaction
            DB::beginTransaction();

            // Get the stocks that are about to be deleted
            $stocks = Stock::whereIn('id', $stockIds)->get();

            foreach ($stocks as $stock) {
                // Update stock balance
                $stockBalance = StockBalance::where('product_id', $stock->product_id)
                    ->where('brand_id', $stock->brand_id)
                    ->where('measurement_id', $stock->measurement_id)
                    ->first();

                if ($stockBalance) {
                    $stockBalance->decrement('balance', $stock->quantity);
                }

                // Log the deleted stock's information
                $inputStored = [
                    'product_id' => $stock->product_id,
                    'brand_id' => $stock->brand_id,
                    'measurement_id' => $stock->measurement_id,
                    'quantity' => $stock->quantity,
                    'unit_price' => $stock->unit_price,
                    'total_cost' => $stock->total_cost,
                    'sale_price' => $stock->sale_price,
                    'min_stock_level' => $stock->min_stock_level,
                    'supplier_id' => $stock->supplier_id,
                    'stock_date' => $stock->stock_date,
                    'environment' => $stock->environment,
                ];

                // Store the log for each stock deleted
                (new ApplicationLogController())->storeLog(
                    $request,
                    'Stock',
                    'SoftDelete',
                    'Deleted stock with id: ' . $stock->id . ', details: ' . json_encode($inputStored) . '.',
                    auth()->user()->id
                );

                // Perform soft delete
                $stock->delete();
            }

            // Commit the transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stocks deleted successfully',
            ], 200);
        } catch (ValidationException $e) {
            // Catch validation errors and return specific messages
            return response()->json([
                'success' => false,
                'message' => 'Validation Error: ' . $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);
        } catch (QueryException $e) {
            // Rollback the transaction on database errors
            DB::rollBack();

            Log::error('Mass delete stocks error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to delete stocks: ' . $e->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            // Rollback the transaction on other errors
            DB::rollBack();

            Log::error('Mass delete stocks error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during mass deletion of stocks. Please try again.',
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Stock $stock)
    {
        // Customize the data to return only the necessary fields
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $stock->id,
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->name,
                'brand_id' => $stock->brand_id,
                'brand_name' => $stock->brand->name,
                'measurement_id' => $stock->measurement_id,
                'measurement_name' => $stock->measurement->name,
                'quantity' => $stock->quantity,
                'unit_price' => $stock->unit_price,
                'total_cost' => $stock->total_cost,
                'sale_price' => $stock->sale_price,
                'min_stock_level' => $stock->min_stock_level,
                'supplier_id' => $stock->supplier_id,
                'supplier_name' => $stock->supplier->name,
                'stock_date' => $stock->stock_date->format('Y-m-d'),
                'environment' => $stock->environment,
                'created_at' => $stock->created_at->toDateTimeString(),
            ]
        ], 200);
    }

    /**
     * Generate the specified stock pdf resource.
     */
    public function pdf(Stock $stock)
    {
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; text-align:left;}
                .header { font-size: 24px; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { text-align: left; border: 1px solid #000000; }
                th { font-weight: bold; }
            </style>
        </head>
        <body>
            <big class="header">Stock Details</big><br>
            <strong>Printed on : ' . date('Y-m-d H:i:s') . '</strong><hr>
            <table class="table align-middle table-hover m-0">
                <tr>
                    <th>Product</th>
                    <td>' . $stock->product->name . '</td>
                </tr>
                <tr>
                    <th>Brand</th>
                    <td>' . $stock->brand->name . '</td>
                </tr>
                <tr>
                    <th>Measurement</th>
                    <td>' . $stock->measurement->name . '</td>
                </tr>
                <tr>
                    <th>Quantity</th>
                    <td>' . $stock->quantity . '</td>
                </tr>
                <tr>
                    <th>Unit Price</th>
                    <td>' . $stock->unit_price . '</td>
                </tr>
                <tr>
                    <th>Total Cost</th>
                    <td>' . $stock->total_cost . '</td>
                </tr>
                <tr>
                    <th>Sale Price</th>
                    <td>' . $stock->sale_price . '</td>
                </tr>
                <tr>
                    <th>Min Stock Level</th>
                    <td>' . $stock->min_stock_level . '</td>
                </tr>
                <tr>
                    <th>Supplier</th>
                    <td>' . $stock->supplier->name . '</td>
                </tr>
                <tr>
                    <th>Stock Date</th>
                    <td>' . $stock->stock_date . '</td>
                </tr>
                <tr>
                    <th>Environment</th>
                    <td>' . $stock->environment . '</td>
                </tr>
            </table>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        $filename = date('d_m_Y') . '_stock_' . $stock->id . '.pdf';
        return $pdf->download($filename);
    }
}
