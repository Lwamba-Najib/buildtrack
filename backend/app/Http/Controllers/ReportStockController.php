<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use Illuminate\Support\Str;
use App\Models\StockBalance;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Exports\ReportStockExport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class ReportStockController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'pagination_size' => 'nullable|integer|min:5',
                'date_range' => 'nullable|string', // Comma separated start and end dates
                'report_type' => 'nullable|string|in:daily,weekly,monthly,quarterly,yearly,custom'
            ]);

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

            // Calculate total stocks for the selected period
            $totalStocks = Stock::whereBetween('created_at', [$startDate, $endDate])
                               ->sum('total_cost');

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
                    });
                });
            }

            // Apply date filter
            $query->whereBetween('created_at', [$startDate, $endDate]);

            // Fetch the paginated data
            $stocks = $query->orderBy('id', 'DESC')->paginate($paginationSize);

            // Return response
            return response()->json([
                'data' => $stocks,
                'report_start' => $startDate->toDateString(),
                'report_end' => $endDate->toDateString(),
                'total_stocks' => $totalStocks,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in Listing Stocks Report: ' . $e->getMessage());
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

        $filename = 'report_stock_balance_' . now()->format('d_m_Y') . '.xlsx';
        return Excel::download(new ReportStockExport($dateRange, $reportType), $filename);
    }

    public function csv(Request $request)
    {
        $dateRange = $request->input('date_range');
        $reportType = $request->input('report_type', 'daily');

        $filename = 'report_stock_balance_' . now()->format('d_m_Y') . '.csv';
        return Excel::download(new ReportStockExport($dateRange, $reportType), $filename);
    }

    private function getStartDateByReportType($reportType)
    {
        switch ($reportType) {
            case 'weekly':
                return Carbon::now()->startOfWeek();
            case 'monthly':
                return Carbon::now()->startOfMonth();
            case 'quarterly':
                return Carbon::now()->firstOfQuarter();
            case 'yearly':
                return Carbon::now()->startOfYear();
            default: // daily
                return Carbon::now()->startOfDay();
        }
    }

    private function getEndDateByReportType($reportType)
    {
        switch ($reportType) {
            case 'weekly':
                return Carbon::now()->endOfWeek();
            case 'monthly':
                return Carbon::now()->endOfMonth();
            case 'quarterly':
                return Carbon::now()->lastOfQuarter();
            case 'yearly':
                return Carbon::now()->endOfYear();
            default: // daily
                return Carbon::now()->endOfDay();
        }
    }
}
