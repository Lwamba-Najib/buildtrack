<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockBalance;
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
        // Validate request
        $validated = $request->validate([
            'pagination_size' => 'nullable|integer|min:5',
            'page' => 'nullable|integer|min:1',
            'search' => 'nullable|string',
            'startDate' => 'nullable|date',
            'endDate' => 'nullable|date',
        ]);

        // Default pagination size
        $paginationSize = $validated['pagination_size'] ?? PaginationSize::SMALL->value;

        // Start query with stock balance
        $query = StockBalance::with(['product', 'brand', 'measurement'])
            ->select(
                'stock_balances.id',
                'stock_balances.product_id',
                'stock_balances.brand_id',
                'stock_balances.measurement_id',
                'stock_balances.batch_number',
                'stock_balances.balance', // Available stock
                DB::raw('COALESCE(SUM(sales_items.quantity), 0) as total_sold'), // Total sold quantity
                DB::raw('MIN(stocks.min_stock_level) as min_stock_level') // Get the lowest min_stock_level
            )
            ->leftJoin('stocks', function ($join) {
                $join->on('stock_balances.product_id', '=', 'stocks.product_id')
                    ->on('stock_balances.brand_id', '=', 'stocks.brand_id')
                    ->on('stock_balances.measurement_id', '=', 'stocks.measurement_id')
                    ->on('stock_balances.batch_number', '=', 'stocks.batch_number'); // Ensures correct batch mapping
            })
            ->leftJoin('sales_items', function ($join) {
                $join->on('stock_balances.product_id', '=', 'sales_items.product_id')
                    ->on('stock_balances.brand_id', '=', 'sales_items.brand_id')
                    ->on('stock_balances.measurement_id', '=', 'sales_items.measurement_id');
            })
            ->groupBy(
                'stock_balances.id',
                'stock_balances.product_id',
                'stock_balances.brand_id',
                'stock_balances.measurement_id',
                'stock_balances.batch_number',
                'stock_balances.balance'
            );

        // Apply search filters
        if (!empty($request->input('search'))) {
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
                ->orWhere('stock_balances.batch_number', 'LIKE', '%' . $searchTerm . '%');
            });
        }

        // Fetch paginated data
        $stocks = $query->orderBy('stock_balances.product_id', 'DESC')->paginate($paginationSize);

        return response()->json($stocks);
    } catch (\Exception $e) {
        Log::error('Error in Listing Stock Balances: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Internal server error',
        ], 500);
    }
}

}
