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

class ReportDailySalesExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents
{
    protected $selectedDate;
    protected $totalSales;

    public function __construct($selectedDate = null)
    {
        // If no date provided, get the latest sales date
        if (!$selectedDate) {
            $latestSale = Sales::orderBy('created_at', 'DESC')->first();
            $selectedDate = $latestSale ? $latestSale->created_at->toDateString() : now()->toDateString();
        }

        $this->selectedDate = $selectedDate;
        $this->totalSales = Sales::whereDate('created_at', $this->selectedDate)->sum('total_amount');
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
            ->whereDate('created_at', $this->selectedDate)
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
            ->orderBy('id', 'DESC')
            ->get()
            ->map(function ($item) {
                return [
                    'product' => $item->product->name,
                    'brand' => $item->brand->name,
                    'measurement' => $item->measurement->name,
                    'batch_number' => $item->batch_number,
                    'quantity' => $item->quantity,
                    'unit_price' => $item->unit_price,
                    'discount' => $item->sale->discount,
                    'total_amount' => ($item->quantity * $item->unit_price) - $item->sale->discount,
                    'issued_by' => $item->user->name,
                    'created_at' => $item->created_at->format('Y-m-d')
                ];
            });
    }

    public function headings(): array
    {
        return [
            'PRODUCT',
            'BRAND',
            'MEASUREMENT',
            'BATCH NO',
            'QTY',
            'UNIT PRICE',
            'DISCOUNT',
            'TOTAL AMOUNT',
            'ISSUED BY',
            'DATE',
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
                $event->sheet->setCellValue('A1', 'Daily Sales Report');
                $event->sheet->setCellValue('A2', 'Report Date: ' . $this->selectedDate);
                $event->sheet->setCellValue('A3', 'Total Sales: ' . number_format($this->totalSales));
                $event->sheet->setCellValue('B3', 'Printed on: ' . now()->format('Y-m-d h:i:s A'));

                // Merge cells for the main title (A1 to J1)
                $event->sheet->mergeCells('A1:J1');

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

                // Style the column headers (A4:J4)
                $event->sheet->getStyle('A4:J4')->applyFromArray([
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
}
