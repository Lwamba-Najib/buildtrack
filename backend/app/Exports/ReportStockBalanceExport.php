<?php

namespace App\Exports;

use App\Models\StockBalance;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Events\AfterSheet;
use Illuminate\Support\Facades\DB;

class ReportStockBalanceExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents, WithColumnFormatting
{
    protected $dateRange;
    protected $reportType;
    protected $startDate;
    protected $endDate;
    protected $totalStockValue;

    public function __construct($dateRange = null, $reportType = 'daily')
    {
        $this->dateRange = $dateRange;
        $this->reportType = $reportType;

        if ($this->reportType === 'custom' && !empty($this->dateRange)) {
            $dates = explode(',', $this->dateRange);
            $this->startDate = Carbon::parse($dates[0])->startOfDay();
            $this->endDate = isset($dates[1]) ? Carbon::parse($dates[1])->endOfDay() : $this->startDate->copy()->endOfDay();
        } else {
            $this->startDate = $this->getStartDateByReportType($this->reportType);
            $this->endDate = $this->getEndDateByReportType($this->reportType);
        }
    }

    public function collection()
    {
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
                DB::raw('MIN(stocks.unit_price) as unit_price'),
                DB::raw('stock_balances.balance * MIN(stocks.unit_price) as total_stocks')
            )
            ->leftJoin('stocks', function ($join) {
                $join->on('stock_balances.product_id', '=', 'stocks.product_id')
                    ->on('stock_balances.brand_id', '=', 'stocks.brand_id')
                    ->on('stock_balances.measurement_id', '=', 'stocks.measurement_id')
                    ->on('stock_balances.batch_number', '=', 'stocks.batch_number');
            })
            ->leftJoin('sales_items', function ($join) {
                $join->on('stock_balances.product_id', '=', 'sales_items.product_id')
                    ->on('stock_balances.brand_id', '=', 'sales_items.brand_id')
                    ->on('stock_balances.measurement_id', '=', 'sales_items.measurement_id')
                    ->whereBetween('sales_items.created_at', [$this->startDate, $this->endDate]);
            })
            ->groupBy(
                'stock_balances.id',
                'stock_balances.product_id',
                'stock_balances.brand_id',
                'stock_balances.measurement_id',
                'stock_balances.batch_number',
                'stock_balances.balance'
            )
            ->orderBy('stock_balances.product_id', 'DESC')
            ->get();

        $this->totalStockValue = $query->sum('total_stocks');

        return $query->map(function ($item) {
            $status = $this->getStockStatus($item->balance, $item->min_stock_level);

            return [
                'PRODUCT' => $item->product->name ?? 'N/A',
                'BATCH NUMBER' => $item->batch_number ?? 'N/A',
                'BRAND' => $item->brand->name ?? 'N/A',
                'UNIT OF MEASUREMENT' => $item->measurement->name ?? 'N/A',
                'QTY IN STOCK' => $item->balance ?? 0,
                'UNIT PRICE' => $item->unit_price ?? 0,
                'TOTAL VALUE' => $item->total_stocks ?? 0,
                'QTY SOLD' => $item->total_sold ?? 0,
                'STATUS' => $status['text'],
                '_STATUS_CLASS' => $status['class']
            ];
        });
    }

    public function headings(): array
    {
        return [
            'PRODUCT',
            'BATCH NUMBER',
            'BRAND',
            'UNIT OF MEASUREMENT',
            'QTY IN STOCK',
            'UNIT PRICE',
            'TOTAL VALUE',
            'QTY SOLD',
            'STATUS'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'E' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // QTY IN STOCK (now column E)
            'F' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // UNIT PRICE (now column F)
            'G' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // TOTAL VALUE (now column G)
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1  // QTY SOLD (now column H)
        ];
    }

    public function startCell(): string
    {
        return 'A4';
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                // Set report header values
                $event->sheet->setCellValue('A1', 'Stock Balance Report');
                $event->sheet->setCellValue('A2', 'Period: ' . $this->startDate->format('M d, Y') . ' - ' . $this->endDate->format('M d, Y'));
                $event->sheet->setCellValue('A3', 'Total Stock Value: ' . number_format($this->totalStockValue, 2));
                $event->sheet->setCellValue('B3', 'Printed on: ' . now()->format('Y-m-d h:i:s A'));

                // Merge cells for the main title (A1 to I1) - now 9 columns
                $event->sheet->mergeCells('A1:I1');

                // Style the main title (centered)
                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => 'B22222']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Style the column headers (A4:I4) - now 9 columns
                $event->sheet->getStyle('A4:I4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => 'B22222']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                    ]
                ]);

                // Style number columns to right-align (now columns E-H)
                $event->sheet->getStyle('E4:H' . ($event->sheet->getHighestRow()))
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                // Style status column (I) based on hidden _STATUS_CLASS column (J)
                $highestRow = $event->sheet->getHighestRow();
                for ($row = 5; $row <= $highestRow; $row++) {
                    $statusClass = $event->sheet->getCell('J' . $row)->getValue();
                    $style = $this->getStatusStyle($statusClass);
                    if ($style) {
                        $event->sheet->getStyle('I' . $row)->applyFromArray($style);
                    }
                }

                // Hide the _STATUS_CLASS column (J)
                $event->sheet->getColumnDimension('J')->setVisible(false);

                // Auto-size visible columns (A-I)
                foreach (range('A', 'I') as $column) {
                    $event->sheet->getColumnDimension($column)
                        ->setAutoSize(true);
                }

                // Center-align the status column (I)
                $event->sheet->getStyle('I5:I' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            },
        ];
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

    private function getStatusStyle($statusClass)
    {
        $styles = [
            'out_of_stock' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'FF0000'] // Red
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'] // White text
                ]
            ],
            'low_stock' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'FFFF00'] // Yellow
                ],
                'font' => [
                    'color' => ['rgb' => '000000'] // Black text
                ]
            ],
            'in_stock' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => '03835D'] // Deep Sea Green
                ],
                'font' => [
                    'color' => ['rgb' => 'FFFFFF'] // White text
                ]
            ]
        ];

        return $styles[$statusClass] ?? null;
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
