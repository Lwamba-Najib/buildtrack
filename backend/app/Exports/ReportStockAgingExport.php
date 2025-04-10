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

class ReportStockAgingExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents, WithColumnFormatting
{
    protected $agingPeriod;
    protected $customDays;
    protected $thresholdDate;
    protected $totalValue;
    protected $agingDays;

    public function __construct($agingPeriod = '30', $customDays = null)
    {
        $this->agingPeriod = $agingPeriod;
        $this->customDays = $customDays;
        $this->agingDays = $this->getAgingDays($agingPeriod, $customDays);
        $this->thresholdDate = Carbon::now()->subDays($this->agingDays);
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
                'stock_balances.created_at',
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
            ->where('stock_balances.balance', '>', 0)
            ->whereNotNull('stock_balances.batch_number')
            ->whereDate('stock_balances.created_at', '<=', $this->thresholdDate)
            ->groupBy(
                'stock_balances.id',
                'stock_balances.product_id',
                'stock_balances.brand_id',
                'stock_balances.measurement_id',
                'stock_balances.batch_number',
                'stock_balances.balance',
                'stock_balances.created_at'
            )
            ->orderBy('stock_balances.created_at', 'ASC')
            ->get();

        $this->totalValue = $query->sum('total_value');

        return $query->map(function ($item) {
            $firstReceivedDate = Carbon::parse($item->created_at);
            $daysInStock = $item->days_in_stock;
            $agingPeriod = $this->getAgingPeriodLabel($daysInStock);

            return [
                'PRODUCT' => $item->product->name ?? 'N/A',
                'BATCH NUMBER' => $item->batch_number ?? 'N/A',
                'BRAND' => $item->brand->name ?? 'N/A',
                'UNIT OF MEASUREMENT' => $item->measurement->name ?? 'N/A',
                'FIRST RECEIVED DATE' => $firstReceivedDate->format('Y-m-d'),
                'DAYS IN STOCK' => $daysInStock,
                'AGING PERIOD' => $agingPeriod,
                'QTY IN STOCK' => $item->balance ?? 0,
                'UNIT PRICE' => $item->unit_price ?? 0,
                'TOTAL VALUE' => $item->total_value ?? 0,
                '_AGING_CLASS' => $this->getAgingClass($daysInStock)
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
            'FIRST RECEIVED DATE',
            'DAYS IN STOCK',
            'AGING PERIOD',
            'QTY IN STOCK',
            'UNIT PRICE',
            'TOTAL VALUE'
        ];
    }

    public function columnFormats(): array
    {
        return [
            'F' => NumberFormat::FORMAT_NUMBER, // DAYS IN STOCK
            'H' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // QTY IN STOCK
            'I' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1, // UNIT PRICE
            'J' => NumberFormat::FORMAT_NUMBER_COMMA_SEPARATED1  // TOTAL VALUE
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
                $event->sheet->setCellValue('A1', 'Stock Aging Report');
                $event->sheet->setCellValue('A2', 'Aging Threshold: ' . $this->agingDays . ' days (Before ' . $this->thresholdDate->format('M d, Y') . ')');
                $event->sheet->setCellValue('A3', 'Total Stock Value: ' . number_format($this->totalValue, 2));
                $event->sheet->setCellValue('B3', 'Generated on: ' . now()->format('Y-m-d h:i A'));

                // Merge cells for the main title (A1 to J1)
                $event->sheet->mergeCells('A1:J1');

                // Style the main title (centered)
                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => 'B22222'] // Red
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Style the column headers (A4:J4)
                $event->sheet->getStyle('A4:J4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF']
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => 'B22222'] // Red
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                    ]
                ]);

                // Style number columns to right-align (columns F, H-J)
                $event->sheet->getStyle('F4:F' . $event->sheet->getHighestRow())
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                $event->sheet->getStyle('H4:J' . $event->sheet->getHighestRow())
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_RIGHT);

                // Style aging period column (G) based on hidden _AGING_CLASS column (K)
                $highestRow = $event->sheet->getHighestRow();
                for ($row = 5; $row <= $highestRow; $row++) {
                    $agingClass = $event->sheet->getCell('K' . $row)->getValue();
                    $style = $this->getAgingStyle($agingClass);
                    if ($style) {
                        $event->sheet->getStyle('G' . $row)->applyFromArray($style);
                    }
                }

                // Hide the _AGING_CLASS column (K)
                $event->sheet->getColumnDimension('K')->setVisible(false);

                // Auto-size visible columns (A-J)
                foreach (range('A', 'J') as $column) {
                    $event->sheet->getColumnDimension($column)
                        ->setAutoSize(true);
                }

                // Center-align the aging period column (G)
                $event->sheet->getStyle('G5:G' . $highestRow)
                    ->getAlignment()
                    ->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            },
        ];
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

    private function getAgingClass($days)
    {
        if ($days <= 7) return 'new';
        if ($days <= 30) return 'recent';
        if ($days <= 90) return 'moderate';
        if ($days <= 180) return 'aging';
        return 'old';
    }

    private function getAgingStyle($agingClass)
    {
        $styles = [
            'new' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'C6EFCE'] // Light green
                ],
                'font' => [
                    'color' => ['rgb' => '006100'] // Dark green text
                ]
            ],
            'recent' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'FFEB9C'] // Light yellow
                ],
                'font' => [
                    'color' => ['rgb' => '9C6500'] // Dark yellow text
                ]
            ],
            'moderate' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'FFC7CE'] // Light red
                ],
                'font' => [
                    'color' => ['rgb' => '9C0006'] // Dark red text
                ]
            ],
            'aging' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'F4B084'] // Light orange
                ],
                'font' => [
                    'color' => ['rgb' => '974806'] // Dark orange text
                ]
            ],
            'old' => [
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'color' => ['rgb' => 'B4C6E7'] // Light Red
                ],
                'font' => [
                    'color' => ['rgb' => '1F4E78'] // Dark Red text
                ]
            ]
        ];

        return $styles[$agingClass] ?? null;
    }
}
