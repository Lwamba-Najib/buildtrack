<?php

namespace App\Exports;

use App\Models\Stock;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ReportStockExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents
{
    protected $dateRange;
    protected $reportType;
    protected $startDate;
    protected $endDate;
    protected $totalStocks;

    public function __construct($dateRange = null, $reportType = 'daily')
    {
        $this->dateRange = $dateRange;
        $this->reportType = $reportType;

        // Determine date range based on report type
        if ($this->reportType === 'custom' && !empty($this->dateRange)) {
            $dates = explode(',', $this->dateRange);
            $this->startDate = Carbon::parse($dates[0])->startOfDay();
            $this->endDate = isset($dates[1]) ? Carbon::parse($dates[1])->endOfDay() : $this->startDate->copy()->endOfDay();
        } else {
            $this->startDate = $this->getStartDateByReportType($this->reportType);
            $this->endDate = $this->getEndDateByReportType($this->reportType);
        }

        $this->totalStocks = Stock::whereBetween('created_at', [$this->startDate, $this->endDate])
                                 ->sum('total_cost');
    }

    public function collection()
    {
        return Stock::with(['product', 'brand', 'measurement', 'supplier', 'createdBy', 'updatedBy'])
                     ->whereBetween('created_at', [$this->startDate, $this->endDate])
                     ->orderBy('id', 'DESC')
                     ->get()
                     ->map(function ($stock) {
            return [
                'DATE' => $stock->stock_date ? Carbon::parse($stock->stock_date)->format('Y-m-d') : 'N/A',
                'PRODUCT' => $stock->product->name ?? 'N/A',
                'BRAND' => $stock->brand->name ?? 'N/A',
                'MEASUREMENT' => $stock->measurement->name ?? 'N/A',
                'QTY' => $stock->quantity ?? 0,
                'UNIT PRICE' => $stock->unit_price ?? 0,
                'TOTAL' => $stock->total_cost ?? 0,
                'SUPPLIER' => $stock->supplier->name ?? 'N/A',
                'RECORDED BY' => $stock->createdBy->name ?? 'N/A',
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
            'QTY',
            'UNIT PRICE',
            'TOTAL COST',
            'SUPPLIER',
            'RECORDED BY',
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
                $event->sheet->setCellValue('A1', 'Stock Report');
                $event->sheet->setCellValue('A2', 'Period: ' .  $this->startDate->format('M d, Y') . ' - ' . $this->endDate->format('M d, Y'));
                $event->sheet->setCellValue('A3', 'Total Stock: ' . number_format($this->totalStocks));
                $event->sheet->setCellValue('B3', 'Printed on: ' . now()->format('Y-m-d h:i:s A'));

                // Merge cells for the main title (A1 to I1)
                $event->sheet->mergeCells('A1:I1');

                // Style the main title (centered)
                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                        'color' => ['rgb' => 'FFFFFF'] // White text
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => 'B22222'] // Brick red background
                    ],
                    'alignment' => [
                        'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
                        'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
                    ]
                ]);

                // Style the column headers (A4:I4)
                $event->sheet->getStyle('A4:I4')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'] // White text
                    ],
                    'fill' => [
                        'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                        'color' => ['rgb' => 'B22222'] // Brick red background
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
