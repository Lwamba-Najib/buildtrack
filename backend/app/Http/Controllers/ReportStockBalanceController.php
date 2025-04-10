<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Exports\ReportStockBalanceExport;
use Maatwebsite\Excel\Facades\Excel;

class ReportStockBalanceController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Validate request
            $validated = $request->validate([
                'pagination_size' => 'nullable|integer|min:5',
                'date_range' => 'nullable|string',
                'report_type' => 'nullable|string|in:daily,weekly,monthly,quarterly,yearly,custom',
                'search' => 'nullable|string'
            ]);

            // Default pagination size
            $paginationSize = $validated['pagination_size'] ?? PaginationSize::SMALL->value;
            $reportType = $validated['report_type'] ?? 'daily';

            // Determine date range based on report type
            if ($reportType === 'custom' && !empty($validated['date_range'])) {
                $dates = explode(',', $validated['date_range']);
                $startDate = Carbon::parse($dates[0])->startOfDay();
                $endDate = isset($dates[1]) ? Carbon::parse($dates[1])->endOfDay() : $startDate->copy()->endOfDay();
            } else {
                $startDate = $this->getStartDateByReportType($reportType);
                $endDate = $this->getEndDateByReportType($reportType);
            }

            // Start query with stock balance
            $query = StockBalance::with(['product', 'brand', 'measurement'])
                ->select(
                    'stock_balances.id',
                    'stock_balances.product_id',
                    'stock_balances.brand_id',
                    'stock_balances.measurement_id',
                    'stock_balances.batch_number',
                    'stock_balances.balance',
                    DB::raw('COALESCE(SUM(sales_items.quantity), 0) as total_sold'),
                    DB::raw('MIN(stocks.min_stock_level) as min_stock_level'),
                    DB::raw('MIN(stocks.unit_price) as unit_price'), // Get unit price
                    DB::raw('stock_balances.balance * MIN(stocks.unit_price) as total_stocks') // Calculate total value
                )
                ->leftJoin('stocks', function ($join) {
                    $join->on('stock_balances.product_id', '=', 'stocks.product_id')
                        ->on('stock_balances.brand_id', '=', 'stocks.brand_id')
                        ->on('stock_balances.measurement_id', '=', 'stocks.measurement_id')
                        ->on('stock_balances.batch_number', '=', 'stocks.batch_number');
                })
                ->leftJoin('sales_items', function ($join) use ($startDate, $endDate) {
                    $join->on('stock_balances.product_id', '=', 'sales_items.product_id')
                        ->on('stock_balances.brand_id', '=', 'sales_items.brand_id')
                        ->on('stock_balances.measurement_id', '=', 'sales_items.measurement_id')
                        ->whereBetween('sales_items.created_at', [$startDate, $endDate]);
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
            $stocks = $query->orderBy('stock_balances.product_id', 'DESC')
                          ->paginate($paginationSize);

            // Calculate total stock value for all items
            $totalStockValue = $stocks->sum('total_stocks');

            return response()->json([
                'data' => $stocks,
                'report_start' => $startDate->toDateString(),
                'report_end' => $endDate->toDateString(),
                'total_stocks' => $totalStockValue
            ]);
        } catch (\Exception $e) {
            Log::error('Error in Listing Stock Balances: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function xlsx(Request $request)
    {
        $dateRange = $request->input('date_range');
        $reportType = $request->input('report_type', 'daily');

        $filename = 'stock_balance_' . now()->format('d_m_Y') . '.xlsx';
        return Excel::download(new ReportStockBalanceExport($dateRange, $reportType), $filename);
    }

    public function csv(Request $request)
    {
        $dateRange = $request->input('date_range');
        $reportType = $request->input('report_type', 'daily');

        $filename = 'stock_balance_' . now()->format('d_m_Y') . '.csv';
        return Excel::download(new ReportStockBalanceExport($dateRange, $reportType), $filename);
    }

    private function getStartDateByReportType($reportType)
    {
        switch ($reportType) {
            case 'weekly': return Carbon::now()->startOfWeek();
            case 'monthly': return Carbon::now()->startOfMonth();
            case 'quarterly': return Carbon::now()->firstOfQuarter();
            case 'yearly': return Carbon::now()->startOfYear();
            default: return Carbon::now()->startOfDay(); // daily
        }
    }

    private function getEndDateByReportType($reportType)
    {
        switch ($reportType) {
            case 'weekly': return Carbon::now()->endOfWeek();
            case 'monthly': return Carbon::now()->endOfMonth();
            case 'quarterly': return Carbon::now()->lastOfQuarter();
            case 'yearly': return Carbon::now()->endOfYear();
            default: return Carbon::now()->endOfDay(); // daily
        }
    }
}
