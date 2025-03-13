<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class StockBalanceController extends Controller
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
                'page' => 'nullable|integer|min:1',
                'search' => 'nullable|string',
                'startDate' => 'nullable|date',
                'endDate' => 'nullable|date',
            ]);

            // Use the provided pagination_size or default to PaginationSize::SMALL if not provided
            $paginationSize = $validated['pagination_size'] ?? PaginationSize::SMALL->value;

            // Start the query
            $query = Stock::with(['product', 'brand', 'measurement', 'supplier'])
                ->select(
                    'product_id',
                    'brand_id',
                    'measurement_id',
                    DB::raw('SUM(quantity) as quantity'),
                    DB::raw('MIN(min_stock_level) as min_stock_level') // Assuming min_stock_level is the same for the same product
                )
                ->groupBy('product_id', 'brand_id', 'measurement_id');

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
                    ->orWhereHas('measurement', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', '%' . $searchTerm . '%');
                    })
                    ->orWhereHas('supplier', function ($q) use ($searchTerm) {
                        $q->where('name', 'LIKE', '%' . $searchTerm . '%');
                    })
                    ->orWhere('quantity', 'LIKE', '%' . $searchTerm . '%');
                });
            }

            // Fetch the paginated data
            $stocks = $query->orderBy('product_id', 'DESC')->paginate($paginationSize);

            return response()->json($stocks);
        } catch (\Exception $e) {
            Log::error('Error in Listing Stocks: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }
}
