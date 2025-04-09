<?php

namespace App\Http\Controllers;

use App\Models\Sales;
use App\Models\SalesItem;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Enums\PaginationSize;
use Illuminate\Support\Facades\Log;
use App\Exports\ReportDailySalesExport;

class ReportDailySalesController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'pagination_size' => 'nullable|integer|min:5',
                'Date' => 'nullable|date', // Ensure date input is valid
            ]);

            $paginationSize = $validated['pagination_size'] ?? PaginationSize::SMALL->value;

            // Check if the user provided a date filter
            $selectedDate = $validated['Date'] ?? null;

            // Fetch the latest sold date and total sales if no date is selected
            if (!$selectedDate) {
                $latestSale = Sales::orderBy('created_at', 'DESC')->first();

                if (!$latestSale) {
                    return response()->json([
                        'data' => [],
                        'last_sold_date' => date('Y-m-d'),
                        'total_sales' => "0",
                    ]);
                }

                $selectedDate = $latestSale->created_at->toDateString();
            }

            // Calculate total sales for the selected date
            $totalSales = Sales::whereDate('created_at', $selectedDate)->sum('total_amount');

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

            // Apply date filter (either user-selected or latest sale date)
            $query->whereDate('created_at', $selectedDate);

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
            $reportdailysales = $query->orderBy('id', 'DESC')->paginate($paginationSize);

            // Return response
            return response()->json([
                'data' => $reportdailysales,
                'last_sold_date' => $selectedDate, // This now reflects either the latest sale or user selection
                'total_sales' => $totalSales,
            ]);
        } catch (\Exception $e) {
            Log::error('Error in Listing Report daily Sales: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    public function xlsx(Request $request)
    {
        $selectedDate = $request->input('Date');

        // If no date provided, get the latest sales date - same logic as index
        if (!$selectedDate) {
            $latestSale = Sales::orderBy('created_at', 'DESC')->first();
            $selectedDate = $latestSale ? $latestSale->created_at->toDateString() : now()->toDateString();
        }

        $filename = 'daily_sales_' . now()->format('d_m_Y') . '.xlsx';
        return Excel::download(new ReportDailySalesExport($selectedDate), $filename);
    }

    public function csv(Request $request)
    {
        $selectedDate = $request->input('Date');

        // If no date provided, get the latest sales date - same logic as index
        if (!$selectedDate) {
            $latestSale = Sales::orderBy('created_at', 'DESC')->first();
            $selectedDate = $latestSale ? $latestSale->created_at->toDateString() : now()->toDateString();
        }

        $filename = 'daily_sales_' . now()->format('d_m_Y') . '.csv';
        return Excel::download(new ReportDailySalesExport($selectedDate), $filename);
    }
}
