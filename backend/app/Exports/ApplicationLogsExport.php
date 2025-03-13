<?php

namespace App\Exports;

use App\Models\ApplicationLog;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithCustomStartCell;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;

class ApplicationLogsExport implements FromCollection, WithHeadings, WithCustomStartCell, WithEvents
{
    protected $applicationlog_id;

    // Constructor to accept the applicationlog_id
    public function __construct($applicationlog_id)
    {
        $this->applicationlog_id = $applicationlog_id;
    }

    // Implement the collection method without parameters
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        return ApplicationLog::select(
            'application_logs.type',
            'application_logs.activity',
            'application_logs.details',
            'application_logs.browser',
            'application_logs.platform',
            'application_logs.ip',
            'users.name as user_name',
            'application_logs.created_at'
        )
        ->join('users', 'application_logs.created_by', '=', 'users.id')
        ->where('application_logs.id', '=', $this->applicationlog_id) // Use the applicationlog_id to filter
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
            'TYPE',
            'ACTIVITY',
            'DETAILS',
            'BROWSER',
            'PLATFORM',
            'IP',
            'USER',
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
