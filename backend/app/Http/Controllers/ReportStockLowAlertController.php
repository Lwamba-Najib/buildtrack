<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\ReportStockLowAlertExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportStockLowAlertController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'pagination_size' => 'nullable|integer|min:5',
                'search' => 'nullable|string'
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
                    'stock_balances.balance',
                    DB::raw('MIN(stocks.min_stock_level) as min_stock_level'),
                    DB::raw('MIN(stocks.unit_price) as unit_price'),
                    DB::raw('stock_balances.balance * MIN(stocks.unit_price) as total_stocks')
                )
                ->leftJoin('stocks', function ($join) {
                    $join->on('stock_balances.product_id', '=', 'stocks.product_id')
                        ->on('stock_balances.brand_id', '=', 'stocks.brand_id')
                        ->on('stock_balances.measurement_id', '=', 'stocks.measurement_id')
                        ->on('stock_balances.batch_number', '=', 'stocks.batch_number');
                })
                ->where(function($query) {
                    // Only include items that are low or out of stock
                    $query->where('stock_balances.balance', '<=', DB::raw('stocks.min_stock_level'))
                          ->orWhere('stock_balances.balance', '<=', 0);
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
            if (!empty($validated['search'])) {
                $searchTerm = $validated['search'];
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
            $stocks = $query->orderBy('stock_balances.balance', 'ASC') // Sort by lowest balance first
                          ->paginate($paginationSize);

            // Calculate total stock value for all items
            $totalStockValue = $stocks->sum('total_stocks');

            return response()->json([
                'data' => $stocks,
                'total_stocks' => $totalStockValue
            ]);
        } catch (\Exception $e) {
            Log::error('Error in Listing Stock Low Alerts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function xlsx(Request $request)
    {
        $filename = 'stock_low_alert_' . now()->format('d_m_Y') . '.xlsx';
        return Excel::download(new ReportStockLowAlertExport(), $filename);
    }

    public function csv(Request $request)
    {
        $filename = 'stock_low_alert_' . now()->format('d_m_Y') . '.csv';
        return Excel::download(new ReportStockLowAlertExport(), $filename);
    }

    private function getStockStatus($balance, $minStockLevel)
    {
        if ($balance <= 0) {
            return ['text' => 'Out of Stock', 'class' => 'out_of_stock'];
        } elseif ($balance <= $minStockLevel) {
            return ['text' => 'Low Stock', 'class' => 'low_stock'];
        } else {
            return ['text' => 'In Stock', 'class' => 'in_stock'];
        }
    }
}
