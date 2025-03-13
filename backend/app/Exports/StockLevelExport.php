<?php

namespace App\Exports;

use App\Models\Client;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Events\AfterSheet;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;

class ClientsExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return Client::select(
            'client_number',
            'business_name',
            'registration_number',
            'tin',
            'country',
            DB::raw("CONCAT(code, '', CASE WHEN LEFT(primary_contact_number, 1) = '0' THEN SUBSTRING(primary_contact_number, 2) ELSE primary_contact_number END) AS phone_with_code"),
            'email_address',
            'registered_address',
            'created_at'
        )
        ->get();
    }

    /**
     * Define the column headings
     *
     * @return array
     */
    public function headings(): array
    {
        return [
            'CLIENT NUMBER/ID',
            'BUSINESS NAME',
            'REGISTRATION NO',
            'TIN',
            'COUNTRY',
            'PRIMARY CONTACT',
            'EMAIL ADDRESS',
            'REGISTRATION ADDRESS',
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
                $event->sheet->setCellValue('A1', 'List of Clients');
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
