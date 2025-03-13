<?php

namespace App\Exports;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class UsersExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return User::select(
            'users.user_number',
            'users.name',
            'users.gender',
            'users.nin',
            'users.country',
            DB::raw("CONCAT(users.code, '', CASE WHEN LEFT(users.phone_number, 1) = '0' THEN SUBSTRING(users.phone_number, 2) ELSE users.phone_number END) AS phone_with_code"),
            'users.email',
            'roles.name as role_name',
            'clients.business_name as client_business_name',
            'users.user_type',
            'users.created_at'
        )
        ->join('roles', 'users.role_id', '=', 'roles.id')
        ->join('clients', 'users.client_id', '=', 'clients.id')
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
            'USER NUMBER/ID',
            'NAME',
            'GENDER',
            'NIN',
            'COUNTRY',
            'PHONE NUMBER',
            'EMAIL',
            'ROLE',
            'CLIENT',
            'ACCOUNT TYPE',
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
                $event->sheet->setCellValue('A1', 'List of Users');
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
