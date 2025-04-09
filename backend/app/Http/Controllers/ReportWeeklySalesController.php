<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use App\Models\SalesItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\Log;
use App\Exports\ReportWeeklySalesExport;
use Carbon\Carbon;

class ReportWeeklySalesController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'pagination_size' => 'nullable|integer|min:5',
                'week_start' => 'nullable|date', // Start date of the week
            ]);

            $paginationSize = $validated['pagination_size'] ?? PaginationSize::SMALL->value;

            // Check if the user provided a week filter
            $weekStart = $validated['week_start'] ?? null;

            // If no week provided, use current week
            if (!$weekStart) {
                $weekStart = now()->startOfWeek()->toDateString();
            }

            $weekEnd = Carbon::parse($weekStart)->endOfWeek()->toDateString();

            // Calculate total sales for the selected week
            $totalSales = Sales::whereBetween('created_at', [$weekStart, $weekEnd])
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

            // Apply week filter
            $query->whereBetween('created_at', [$weekStart, $weekEnd]);

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
            $reportWeeklySales = $query->orderBy('id', 'DESC')->paginate($paginationSize);

            // Return response
            return response()->json([
                'data' => $reportWeeklySales,
                'week_start' => $weekStart,
                'week_end' => $weekEnd,
                'total_sales' => $totalSales,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in Listing Weekly Sales Report: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function xlsx(Request $request)
    {
        $weekStart = $request->input('week_start');

        if (!$weekStart) {
            $weekStart = now()->startOfWeek()->toDateString();
        }

        $weekEnd = Carbon::parse($weekStart)->endOfWeek()->toDateString();

        $filename = 'weekly_sales_' . now()->format('d_m_Y') . '.xlsx';
        return Excel::download(new ReportWeeklySalesExport($weekStart), $filename);
    }

    public function csv(Request $request)
    {
        $weekStart = $request->input('week_start');

        if (!$weekStart) {
            $weekStart = now()->startOfWeek()->toDateString();
        }

        $weekEnd = Carbon::parse($weekStart)->endOfWeek()->toDateString();

        $filename = 'weekly_sales_' . now()->format('d_m_Y') . '.csv';
        return Excel::download(new ReportWeeklySalesExport($weekStart), $filename);
    }
}
