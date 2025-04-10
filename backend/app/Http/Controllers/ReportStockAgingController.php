<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\ReportStockAgingExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportStockAgingController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'pagination_size' => 'nullable|integer|min:5',
                'search' => 'nullable|string',
                'aging_period' => 'nullable|string|in:7,30,60,90,180,365,custom',
                'custom_days' => 'nullable|integer|min:1|required_if:aging_period,custom'
            ]);

            // Default pagination size
            $paginationSize = $validated['pagination_size'] ?? PaginationSize::SMALL->value;
            $agingPeriod = $validated['aging_period'] ?? '30';
            $customDays = $validated['custom_days'] ?? null;

            // Determine aging days
            $days = $this->getAgingDays($agingPeriod, $customDays);
            $thresholdDate = Carbon::now()->subDays($days);

            // Start query with stock balance
            $query = StockBalance::with(['product', 'brand', 'measurement'])
                ->select(
                    'stock_balances.id',
                    'stock_balances.product_id',
                    'stock_balances.brand_id',
                    'stock_balances.measurement_id',
                    'stock_balances.batch_number',
                    'stock_balances.balance',
                    'stock_balances.created_at', // Using created_at as the inventory date
                    DB::raw('MIN(stocks.min_stock_level) as min_stock_level'),
                    DB::raw('MIN(stocks.unit_price) as unit_price'),
                    DB::raw('stock_balances.balance * MIN(stocks.unit_price) as total_value'),
                    DB::raw('DATEDIFF(NOW(), stock_balances.created_at) as days_in_stock')
                )
                ->leftJoin('stocks', function ($join) {
                    $join->on('stock_balances.product_id', '=', 'stocks.product_id')
                        ->on('stock_balances.brand_id', '=', 'stocks.brand_id')
                        ->on('stock_balances.measurement_id', '=', 'stocks.measurement_id')
                        ->on('stock_balances.batch_number', '=', 'stocks.batch_number');
                })
                ->where('stock_balances.balance', '>', 0) // Only items with stock
                ->whereNotNull('stock_balances.batch_number') // Ensure we have batch numbers
                ->whereDate('stock_balances.created_at', '<=', $thresholdDate) // Filter by aging threshold
                ->groupBy(
                    'stock_balances.id',
                    'stock_balances.product_id',
                    'stock_balances.brand_id',
                    'stock_balances.measurement_id',
                    'stock_balances.batch_number',
                    'stock_balances.balance',
                    'stock_balances.created_at'
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
            $stocks = $query->orderBy('stock_balances.created_at', 'ASC') // Oldest first
                          ->paginate($paginationSize);

            // Calculate total stock value and add aging info
            $totalStockValue = 0;

            $stocks->getCollection()->transform(function ($item) use (&$totalStockValue) {
                $item->first_received_date = Carbon::parse($item->created_at)->format('Y-m-d');
                $item->aging_period = $this->getAgingPeriodLabel($item->days_in_stock);
                $totalStockValue += $item->total_value;
                return $item;
            });

            return response()->json([
                'data' => $stocks,
                'total_value' => $totalStockValue,
                'aging_threshold' => $days . ' days',
                'threshold_date' => $thresholdDate->format('Y-m-d')
            ]);
        } catch (\Exception $e) {
            Log::error('Error in Stock Aging Report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function xlsx(Request $request)
    {
        $agingPeriod = $request->input('aging_period', '30');
        $customDays = $request->input('custom_days');

        $filename = 'stock_aging_' . now()->format('d_m_Y') . '.xlsx';
        return Excel::download(new ReportStockAgingExport($agingPeriod, $customDays), $filename);
    }

    public function csv(Request $request)
    {
        $agingPeriod = $request->input('aging_period', '30');
        $customDays = $request->input('custom_days');

        $filename = 'stock_aging_' . now()->format('d_m_Y') . '.csv';
        return Excel::download(new ReportStockAgingExport($agingPeriod, $customDays), $filename);
    }

    private function getAgingDays($period, $customDays = null)
    {
        switch ($period) {
            case '7': return 7;
            case '30': return 30;
            case '60': return 60;
            case '90': return 90;
            case '180': return 180;
            case '365': return 365;
            case 'custom': return $customDays;
            default: return 30;
        }
    }

    private function getAgingPeriodLabel($days)
    {
        if ($days <= 7) return '0-7 days';
        if ($days <= 30) return '8-30 days';
        if ($days <= 60) return '31-60 days';
        if ($days <= 90) return '61-90 days';
        if ($days <= 180) return '91-180 days';
        if ($days <= 365) return '181-365 days';
        return 'Over 1 year';
    }
}
