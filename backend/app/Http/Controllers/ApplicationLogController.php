<?php

namespace App\Http\Controllers;

use App\Models\ApplicationLog;
use WhichBrowser\Parser;
use Jenssegers\Agent\Agent;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use App\Exports\ApplicationLogsExport;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\QueryException;

class ApplicationLogController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            // Validate the request, allowing pagination_size to be null or an integer with a minimum value of 5
            $validated = $request->validate([
                'pagination_size' => 'nullable|integer|min:5',
            ]);

            // Use the provided pagination_size or default to PaginationSize::SMALL if not provided
            $paginationSize = $validated['pagination_size'] ?? PaginationSize::SMALL->value;

            // Start the query
            $query = ApplicationLog::with('user');

            // Apply search filters
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');
                $query->where(function ($query) use ($searchTerm) {
                    $query->where('type', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('activity', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('browser', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('platform', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhereHas('user', function ($userQuery) use ($searchTerm) {
                            $userQuery->where('name', 'LIKE', '%' . $searchTerm . '%');
                        });
                });
            }

            // Apply type filter
            if ($request->has('type') && !empty($request->input('type'))) {
                $query->where('type', $request->input('type'));
            }

            // Apply activity filter
            if ($request->has('activity') && !empty($request->input('activity'))) {
                $query->where('activity', $request->input('activity'));
            }

            // Apply date filter
            if (
                $request->has('startDate') && !empty($request->input('startDate')) &&
                $request->has('endDate') && !empty($request->input('endDate'))
            ) {

                $startDate = $request->input('startDate');
                $endDate = $request->input('endDate');

                // Use whereDate with whereBetween for date-only filtering
                $query->whereDate('created_at', '>=', $startDate)
                    ->whereDate('created_at', '<=', $endDate);

            } elseif ($request->has('startDate') && !empty($request->input('startDate'))) {
                // If only startDate is provided, filter records from that date onward (date only)
                $query->whereDate('created_at', '>=', $request->input('startDate'));

            } elseif ($request->has('endDate') && !empty($request->input('endDate'))) {
                // If only endDate is provided, filter records up to that date (date only)
                $query->whereDate('created_at', '<=', $request->input('endDate'));
            }

            // Fetch the paginated data
            $applicationlogs = $query->orderBy('id', 'DESC')->paginate($paginationSize);

            return response()->json($applicationlogs);
        } catch (\Exception $e) {
            Log::error('Error in applicationLogController: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error'
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function storeLog(Request $request, $type, $activity, $details, $created_by)
    {
        try {
            $user_agent = $request->header('User-Agent');
            // composer require jenssegers/agent
            // Create an instance of the Agent class
            $agent = new Agent();
            // Get the browser name using the Agent class
            $browser = $agent->browser();
            $os = php_uname('s') . ", " . php_uname('r') . ", " . php_uname('v') . ", " . php_uname('m');
            $ip = $request->ip();
            $mac_address = $this->getMacAddress();
            $data = [
                'type' => $type,
                'activity' => $activity,
                'details' => $details,
                'user_agent' => $user_agent,
                'browser' => $browser,
                'platform' => $os,
                'ip' => $ip,
                'mac_address' => $mac_address,
                'created_by' => $created_by,
            ];
            //dd($data);
            ApplicationLog::create($data);
        } catch (QueryException $exception) {
            // Handle the failure or log the error
            return $exception->getMessage();
        }
    }

    private function getBrowserName($userAgent)
    {
        //Requirements
        //1. composer require whichbrowser/parser
        //2. use WhichBrowser\Parser;
        $parser = new Parser($userAgent);
        $browser = $parser->browser->toString();
        return $browser;
    }

    private function getMacAddress()
    {
        // Check if exec() is disabled
        if (function_exists('exec') === false) {
            // Log a warning or provide a default response
            return 'EXEC_DISABLED';
        }

        $output = [];

        // Windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            exec('getmac', $output);
            if (!empty($output)) {
                return strtok($output[0], ' ');
            }

            // Alternative using ipconfig
            exec('ipconfig /all', $output);
            foreach ($output as $line) {
                if (preg_match('/Physical Address[ .]*: ([0-9A-Fa-f:-]+)/', $line, $matches)) {
                    return $matches[1];
                }
            }
        } else {
            // Unix-based systems (Linux, macOS)
            exec('ifconfig -a', $output);
            foreach ($output as $line) {
                if (preg_match('/ether ([0-9a-fA-F:]{17})/', $line, $matches)) {
                    return $matches[1];
                }
            }
        }

        return null; // If MAC address could not be found
    }

    /**
     * Display the specified resource.
     */
    public function show(ApplicationLog $applicationlog)
    {
        $applicationlog->load('user');

        // Customize the data to return only the necessary fields
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $applicationlog->id,
                'type' => $applicationlog->type,
                'activity' => $applicationlog->activity,
                'details' => $applicationlog->details,
                'browser' => $applicationlog->browser,
                'platform' => $applicationlog->platform,
                'ip' => $applicationlog->ip,
                'created_at' => $applicationlog->created_at->toDateTimeString(),
                'user' => [
                    'id' => $applicationlog->user->id,
                    'name' => $applicationlog->user->name,
                ],
            ]
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function applicationLogColumns(Request $request)
    {
        try {
            // Fetch distinct types
            $types = ApplicationLog::select('type')
                ->distinct()
                ->orderBy('type', 'ASC')
                ->get();

            // Fetch distinct activities
            $activities = ApplicationLog::select('activity')
                ->distinct()
                ->orderBy('activity', 'ASC')
                ->get();

            return response()->json([
                'types' => $types,
                'activities' => $activities
            ]);
        } catch (\Exception $e) {
            Log::error('Error in applicationLogColumns: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Internal server error'], 500);
        }
    }
    public function xlsx($applicationlog_id)
    {
        /*
        installation: composer require maatwebsite/excel
        */
        $filename = date('d_m_Y') . '_applicationlogs_' . $applicationlog_id . '.xlsx';
        return Excel::download(new ApplicationLogsExport($applicationlog_id), $filename);
    }

    public function csv($applicationlog_id)
    {
        $filename = date('d_m_Y') . '_applicationlogs_' . $applicationlog_id . '.csv';
        return Excel::download(new ApplicationLogsExport($applicationlog_id), $filename);
    }
}
