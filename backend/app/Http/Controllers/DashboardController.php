<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Stock;
use App\Models\Sales;
use App\Models\SalesItem;
use App\Models\StockBalance;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        try {
            // Get date range (default to current month)
            $startDate = $request->input('startDate', Carbon::now()->startOfMonth()->toDateString());
            $endDate = $request->input('endDate', Carbon::now()->endOfMonth()->toDateString());

            return response()->json([
                'success' => true,
                'data' => [
                    'stock_value' => $this->calculateStockValue(),
                    'total_revenue' => $this->calculateTotalRevenue($startDate, $endDate),
                    'gross_profit' => $this->calculateGrossProfit($startDate, $endDate),
                    'net_profit' => $this->calculateNetProfit($startDate, $endDate),
                    'total_loss' => 0, // Set to 0 since you don't have actual losses
                    'chart_data' => $this->generateGraphData($startDate, $endDate), // Graph data
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load dashboard data: ' . $e->getMessage()
            ], 500);
        }
    }

    private function calculateStockValue()
    {
        return StockBalance::with(['product', 'brand', 'measurement'])
            ->get()
            ->sum(function ($balance) {
                $stock = Stock::where('product_id', $balance->product_id)
                    ->where('brand_id', $balance->brand_id)
                    ->where('measurement_id', $balance->measurement_id)
                    ->where('batch_number', $balance->batch_number)
                    ->orderBy('created_at', 'desc')
                    ->first();

                return $balance->balance * ($stock->unit_price ?? 0);
            });
    }

    private function calculateTotalRevenue($startDate, $endDate)
    {
        return Sales::whereDate('created_at', '>=', $startDate)
            ->whereDate('created_at', '<=', $endDate)
            ->sum('total_amount');
    }

    private function calculateGrossProfit($startDate, $endDate)
    {
        $revenue = $this->calculateTotalRevenue($startDate, $endDate);
        $cogs = $this->calculateCostOfGoodsSold($startDate, $endDate);
        return $revenue - $cogs;
    }

    private function calculateNetProfit($startDate, $endDate)
    {
        // Assuming 20% expenses for this example
        return $this->calculateGrossProfit($startDate, $endDate) * 0.8;
    }

    private function calculateCostOfGoodsSold($startDate, $endDate)
    {
        return SalesItem::whereHas('sale', function ($query) use ($startDate, $endDate) {
                $query->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            })
            ->get()
            ->sum(function ($item) {
                $stock = Stock::where('product_id', $item->product_id)
                    ->where('brand_id', $item->brand_id)
                    ->where('measurement_id', $item->measurement_id)
                    ->where('batch_number', $item->batch_number)
                    ->orderBy('created_at', 'desc')
                    ->first();

                return $item->quantity * ($stock->unit_price ?? 0);
            });
    }

    private function generateGraphData($startDate, $endDate)
    {
        return SalesItem::with('product')
            ->select('product_id', DB::raw('SUM(quantity) as total_quantity'))
            ->whereHas('sale', function ($query) use ($startDate, $endDate) {
                $query->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);
            })
            ->groupBy('product_id')
            ->orderByDesc('total_quantity')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                return [
                    'label' => $item->product->name,
                    'y' => (int) $item->total_quantity, // Ensure 'y' is an integer
                ];
            });
    }
}
