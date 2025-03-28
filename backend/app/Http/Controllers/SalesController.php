<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use App\Models\Stock;
use App\Models\Product;
use App\Models\SalesItem;
use Illuminate\Support\Str;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalesController extends Controller
{
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
            $query = Sales::with(['user:id,name']);

            // Apply search filters
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');
                $query->where(function ($query) use ($searchTerm) {
                    $query->where('batch_number', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('customer_name', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('customer_name', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('customer_email', 'LIKE', '%' . $searchTerm . '%');
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
            $sales = $query->orderBy('id', 'DESC')->paginate($paginationSize);

            return response()->json($sales);
        } catch (\Exception $e) {
            Log::error('Error in Listing Sales: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function show($id)
    {
        // Fetch the sale with its items, product, brand, and measurement
        $sale = Sales::with(['items.product', 'items.brand', 'items.measurement'])->find($id);

        if (!$sale) {
            return response()->json([
                'success' => false,
                'message' => 'Sale not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $sale,
        ]);
    }
    /**
     * Fetch products for the POS system.
     */
    public function getProductsInStock()
    {
        try {
            $products = Product::whereHas('stockBalances', function ($query) {
                    $query->where('balance', '>', 0);
                }) // Ensure only products with stock balance > 0
                ->select('id', 'name') // Select only required fields
                ->distinct() // Ensure unique products
                ->get();

            return response()->json($products);
        } catch (\Exception $e) {
            Log::error('Error fetching products: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch products'], 500);
        }
    }

    /**
     * Fetch batches by product ID.
     */
    public function getBatchNumbersByProductInStock($productId)
    {
        try {
            $batchNumbers = StockBalance::where('product_id', $productId)
                ->where('balance', '>', 0)
                ->get(['batch_number', 'balance']) // Fetch only needed fields
                ->toArray(); // Convert to array for a cleaner response

            return response()->json($batchNumbers);
        } catch (\Exception $e) {
            Log::error('Error fetching batch numbers', ['error' => $e]);

            return response()->json(['error' => 'Failed to fetch batch numbers'], 500);
        }
    }

    /**
     * Fetch brands by batchNumber.
     */
    public function getBrandsByBatchNumberInStock($batchNumber)
    {
        try {
            $brands = StockBalance::where('batch_number', $batchNumber) // Use StockBalance instead of Stock
                ->where('balance', '>', 0) // Ensure the product is in stock
                ->whereHas('brand') // Ensure there is an associated brand
                ->with('brand:id,name') // Load only 'id' and 'name' fields from the brand
                ->get()
                ->pluck('brand') // Extract the brand objects
                ->unique('id') // Ensure unique brands
                ->values(); // Reset the array keys

            return response()->json($brands);
        } catch (\Exception $e) {
            Log::error('Error fetching brands: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch brands'], 500);
        }
    }
    public function getMeasurementsByBrandInStock($brandId)
    {
        try {
            // Fetch stock balances where brand_id matches and stock is available
            $measurements = StockBalance::where('brand_id', $brandId) // Use StockBalance instead of Stock
                ->where('balance', '>', 0) // Ensure stock is available
                ->whereHas('measurement') // Ensure the stock has an associated measurement
                ->with('measurement:id,name') // Load only necessary fields
                ->get()
                ->pluck('measurement') // Extract the measurement objects
                ->unique('id') // Ensure unique measurements
                ->values(); // Reset array keys

            return response()->json($measurements);
        } catch (\Exception $e) {
            Log::error('Error fetching measurements: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch measurements'], 500);
        }
    }

    /**
     * Fetch sale price for a product and brand.
     */
    public function getSalePriceInstock(Request $request)
    {
        try {
            $productId = $request->query('productId');
            $brandId = $request->query('brandId');
            $measurementId = $request->query('measurementId');
            $batchNumber = $request->query('batchNumber');

            $stock = Stock::where('product_id', $productId)
                ->where('batch_number', $batchNumber)
                ->where('brand_id', $brandId)
                ->where('measurement_id', $measurementId)
                ->first();

            if (!$stock) {
                return response()->json(['error' => 'Stock not found'], 404);
            }

            return response()->json(['salePrice' => $stock->sale_price]);
        } catch (\Exception $e) {
            Log::error('Error fetching sale price: ' . $e->getMessage());
            return response()->json(['error' => 'Failed to fetch sale price'], 500);
        }
    }

    /**
     * Process a sale.
     */
    public function store(Request $request)
    {
        try {
            // Generate the 16-character UUID and prepend with 'UR'
            do {
                $uuid = strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 14));
            } while (str_starts_with($uuid, '0'));

            $batchNumber = 'SN' . $uuid;
            // Validate request data
            $validated = $request->validate([
                'customerName' => 'nullable|string|max:255',
                'customerPhone' => 'nullable|string|max:20',
                'customerEmail' => 'nullable|string|max:255',
                'customerAddress' => 'nullable|string|max:255',
                'items' => 'required|array',
                'items.*.product.id' => 'required|exists:products,id',
                'items.*.batchNumber.batch_number' => 'required',
                'items.*.brand.id' => 'required|exists:brands,id',
                'items.*.measurement.id' => 'required|exists:measurements,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.salePrice' => 'required|numeric|min:0',
                'discount' => 'required|numeric|min:0|max:100',
                'paymentMethod' => 'required|in:CASH,CARD,ONLINE',
                'notice' => 'nullable|string|max:255',
            ]);

            // Start a database transaction
            DB::beginTransaction();

            // Calculate total amount
            $totalAmount = collect($validated['items'])->reduce(function ($total, $item) {
                return $total + ($item['quantity'] * $item['salePrice']);
            }, 0);

            // Apply discount
            $totalAmount -= $totalAmount * ($validated['discount'] / 100);

            // Create the sale
            $sale = Sales::create([
                'customer_name' => ucwords($validated['customerName']),
                'customer_phone' => $validated['customerPhone'],
                'customer_email' => $validated['customerEmail'],
                'customer_address' => $validated['customerAddress'],
                'batch_number' => $batchNumber,
                'total_amount' => $totalAmount,
                'discount' => $validated['discount'],
                'payment_method' => $validated['paymentMethod'],
                'notice' => $validated['notice'],
                'created_by' => $validated['created_by'] = auth()->user()->id,
            ]);

            // Create sale items
            foreach ($validated['items'] as $item) {
                SalesItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $item['product']['id'],
                    'brand_id' => $item['brand']['id'],
                    'measurement_id' => $item['measurement']['id'],
                    'batch_number' => $item['batchNumber']['batch_number'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['salePrice'],
                    'total_price' => $item['quantity'] * $item['salePrice'],
                    'created_by' => $validated['created_by'] = auth()->user()->id,
                ]);

                // Update stock balance
                $stock = StockBalance::where('product_id', $item['product']['id'])
                    ->where('brand_id', $item['brand']['id'])
                    ->where('measurement_id', $item['measurement']['id'])
                    ->where('batch_number', $item['batchNumber']['batch_number'])
                    ->first();

                if ($stock) {
                    $stock->decrement('balance', $item['quantity']);
                }
            }

            // Commit the transaction
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sale processed successfully!',
                'data' => $sale,
            ]);
        } catch (\Exception $e) {
            // Rollback the transaction on error
            DB::rollBack();
            Log::error('Error processing sale: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the sale.',
            ], 500);
        }
    }
}
