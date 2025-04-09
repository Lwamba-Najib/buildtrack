<?php

namespace App\Exports;

use App\Models\Sales;
use App\Models\SalesItem;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use Carbon\Carbon;

class ReportConsolidatedSalesExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents
{
    protected $dateRange;
    protected $reportType;
    protected $startDate;
    protected $endDate;
    protected $totalSales;

    public function __construct($dateRange = null, $reportType = 'daily')
    {
        $this->reportType = $reportType;

        if ($reportType === 'custom' && !empty($dateRange)) {
            $dates = explode(',', $dateRange);
            $this->startDate = Carbon::parse($dates[0])->startOfDay();
            $this->endDate = isset($dates[1]) ? Carbon::parse($dates[1])->endOfDay() : $this->startDate->copy()->endOfDay();
        } else {
            $this->startDate = $this->getStartDateByReportType($reportType);
            $this->endDate = $this->getEndDateByReportType($reportType);
        }

        $this->totalSales = Sales::whereBetween('created_at', [$this->startDate, $this->endDate])->sum('total_amount');
    }

    public function collection()
    {
        return SalesItem::with([
                'product:id,name',
                'brand:id,name',
                'measurement:id,name',
                'sale:id,discount',
                'user:id,name'
            ])
            ->whereBetween('created_at', [$this->startDate, $this->endDate])
            ->select([
                'sales_items.id',
                'sales_items.product_id',
                'sales_items.brand_id',
                'sales_items.measurement_id',
                'sales_items.batch_number',
                'sales_items.quantity',
                'sales_items.unit_price',
                'sales_items.sale_id',
                'sales_items.created_by',
                'sales_items.created_at'
            ])
            ->orderBy('created_at', 'ASC')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->created_at->format('Y-m-d'),
                    'product' => $item->product->name,
                    'brand' => $item->brand->name,
                    'measurement' => $item->measurement->name,
                    'batch_number' => $item->batch_number,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->sale->discount,
                    'total_amount' => ($item->quantity * $item->unit_price) - $item->sale->discount,
                    'issued_by' => $item->user->name,
                ];
            });
    }

    public function headings(): array
    {
        return [
            'DATE',
            'PRODUCT',
            'BRAND',
            'MEASUREMENT',
            'BATCH NO',
            'QTY',
            'UNIT PRICE',
            'DISCOUNT',
            'TOTAL AMOUNT',
            'ISSUED BY',
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
                $event->sheet->setCellValue('A1', ucfirst($this->reportType) . ' Sales Report');
                $event->sheet->setCellValue('A2', 'Period: ' . $this->startDate->format('M d, Y') . ' - ' . $this->endDate->format('M d, Y'));
                $event->sheet->setCellValue('A3', 'Total Sales: ' . number_format($this->totalSales));
                $event->sheet->setCellValue('B3', 'Printed on: ' . now()->format('Y-m-d h:i:s A'));

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
                        'color' => ['rgb' => 'B22222']
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
                        'color' => ['rgb' => 'B22222']
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER
                    ]
                ]);

                // Style the info row (A2:B3)
                $event->sheet->getStyle('A2:B3')->applyFromArray([
                    'font' => [
                        'bold' => true
                    ]
                ]);

                // Auto-size columns for better readability
                foreach (range('A', 'J') as $column) {
                    $event->sheet->getColumnDimension($column)->setAutoSize(true);
                }
            },
        ];
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
