<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use App\Models\SalesItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\Log;
use App\Exports\ReportMonthlySalesExport;
use Carbon\Carbon;

class ReportMonthlySalesController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'pagination_size' => 'nullable|integer|min:5',
                'month' => 'nullable|date_format:Y-m', // Expect format like 2023-05
            ]);

            $paginationSize = $validated['pagination_size'] ?? PaginationSize::SMALL->value;

            // Check if the user provided a month filter
            $selectedMonth = $validated['month'] ?? null;

            // If no month selected, use current month
            if (!$selectedMonth) {
                $selectedMonth = now()->format('Y-m');
            }

            // Parse the month and get start and end dates
            $startDate = Carbon::parse($selectedMonth)->startOfMonth();
            $endDate = Carbon::parse($selectedMonth)->endOfMonth();

            // Calculate total sales for the selected month
            $totalSales = Sales::whereBetween('created_at', [$startDate, $endDate])->sum('total_amount');

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

            // Apply month filter
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
            $reportMonthlySales = $query->orderBy('created_at', 'DESC')->paginate($paginationSize);

            // Return response
            return response()->json([
                'data' => $reportMonthlySales,
                'selected_month' => $selectedMonth,
                'total_sales' => $totalSales,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in Listing Report monthly Sales: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function xlsx(Request $request)
    {
        $selectedMonth = $request->input('month');

        // If no month provided, use current month
        if (!$selectedMonth) {
            $selectedMonth = now()->format('Y-m');
        }

        $filename = 'monthly_sales_' . $selectedMonth . '.xlsx';
        return Excel::download(new ReportMonthlySalesExport($selectedMonth), $filename);
    }

    public function csv(Request $request)
    {
        $selectedMonth = $request->input('month');

        // If no month provided, use current month
        if (!$selectedMonth) {
            $selectedMonth = now()->format('Y-m');
        }

        $filename = 'monthly_sales_' . $selectedMonth . '.csv';
        return Excel::download(new ReportMonthlySalesExport($selectedMonth), $filename);
    }
}
