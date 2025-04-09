<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use App\Models\SalesItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\Log;
use App\Exports\ReportConsolidatedSalesExport;
use Carbon\Carbon;

class ReportConsolidatedSalesController extends Controller
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

            // Calculate total sales for the selected period
            $totalSales = Sales::whereBetween('created_at', [$startDate, $endDate])
                              ->sum('total_amount');

            // Start query with required relationships for sales items
            $query = SalesItem::with([
                'product:id,name',
                'brand:id,name',
                'measurement:id,name',
                'sale:id,batch_number,total_amount,discount,created_by,created_at',
                'user:id,name'
            ])->select([
                'sales_items.id',
                'sales_items.sale_id',
                'sales_items.product_id',
                'sales_items.brand_id',
                'sales_items.measurement_id',
                'sales_items.batch_number',
                'sales_items.quantity',
                'sales_items.unit_price',
                'sales_items.total_price',
                'sales_items.created_by',
                'sales_items.created_at'
            ]);

            // Apply date filter
            $query->whereBetween('created_at', [$startDate, $endDate]);

            // Search filter
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');
                $query->where(function ($query) use ($searchTerm) {
                    $query->where('batch_number', 'LIKE', "%$searchTerm%")
                        ->orWhereHas('sale', function ($q) use ($searchTerm) {
                            $q->where('batch_number', 'LIKE', "%$searchTerm%");
                        });
                });
            }

            // Fetch paginated data
            $reportData = $query->orderBy('created_at', 'DESC')->paginate($paginationSize);

            // Return response
            return response()->json([
                'data' => $reportData,
                'report_start' => $startDate->toDateString(),
                'report_end' => $endDate->toDateString(),
                'total_sales' => $totalSales,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in Listing Interim Report: ' . $e->getMessage());
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

        $filename = 'consolidated_sales_' . now()->format('d_m_Y') . '.xlsx';
        return Excel::download(new ReportConsolidatedSalesExport($dateRange, $reportType), $filename);
    }

    public function csv(Request $request)
    {
        $dateRange = $request->input('date_range');
        $reportType = $request->input('report_type', 'daily');

        $filename = 'consolidated_sales_' . now()->format('d_m_Y') . '.csv';
        return Excel::download(new ReportConsolidatedSalesExport($dateRange, $reportType), $filename);
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
