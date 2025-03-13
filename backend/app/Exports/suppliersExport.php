<?php

namespace App\Exports;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class SuppliersExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Supplier::select(
            'suppliers.supplier_number',
            'suppliers.name',
            'suppliers.email',
            'suppliers.phone_number',
            'suppliers.tin',
            'suppliers.country',
            'suppliers.address',
            'suppliers.created_at'
        )->get();
    }

    /**
     * Define the column headings
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'USER NUMBER/ID',
            'NAME',
            'PHONE NUMBER',
            'EMAIL',
            'TIN',
            'COUNTRY',
            'ADDRESS',
            'DATE CREATED',
        ];
    }

    /**
     * Define the starting cell for the headings
     *
     * @return string
     */
    public function startCell(): string
    {
        return 'A4';  // Start the headings from row 4, for example
    }

    /**
     * Handle the AfterSheet event to add title and printed date
     *
     * @return array
     */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function(AfterSheet $event) {
                $event->sheet->setCellValue('A1', 'List of Suppliers');
                $event->sheet->setCellValue('A2', 'Printed on: ' . date('Y-m-d H:i:s'));

                // Optionally style the title and printed date
                $event->sheet->getStyle('A1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'size' => 16,
                    ],
                ]);
            },
        ];
    }
}
