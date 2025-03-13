<?php

namespace App\Http\Controllers;
use App\Models\Supplier;
use Illuminate\Support\Str;
use App\Exports\suppliersExport;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use App\Services\PhpMailerService;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class SupplierController extends Controller
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
            $query = Supplier::with('user:id,name');

            // Apply search filters
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');
                $query->where(function ($query) use ($searchTerm) {
                    $query->where('supplier_number', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('name', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('phone_number', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('tin', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
                });
            }

            // Apply country filter
            if ($request->has('country') && !empty($request->input('country'))) {
                $query->where('country', $request->input('country'));
            }

            // Fetch the paginated data
            $suppliers = $query->orderBy('id', 'DESC')->paginate($paginationSize);

            return response()->json($suppliers);
        } catch (\Exception $e) {
            Log::error('Error in Listing Suppliers: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(Request $request)
    {
        try {
            // Generate the 16-character UUID and prepend with 'UR'
            do {
                $uuid = strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 14));
            } while (str_starts_with($uuid, '0'));

            $supplier_number = 'SR' . $uuid;

            // Validate request data
            $validated = request()->validate(
                [
                    'supplier_number'=> 'nullable',
                    'name' => 'required|min:3|max:255',
                    'phone_number' => 'required|unique:suppliers,phone_number',
                    'email' => 'required|email|unique:suppliers,email',
                    'tin' => 'required|unique:suppliers,tin',
                    'country' => 'required',
                    'address' => 'required',
                    'environment' => 'required|in:PRODUCTION,TEST,DEVELOPMENT',
                    'created_by' => 'nullable',
                ]
            );

            // Sanitize and normalize data
            $validated['supplier_number'] = $supplier_number;
            $validated['name'] = ucwords($validated['name']);
            $validated['tin'] = strtoupper($validated['tin']);
            $validated['environment'] = strtoupper($validated['environment']);
            $validated['created_by'] = auth()->user()->id;

            // Create supplier
            $newSupplier = Supplier::create($validated);

            // Extract specific form inputs for logging
            $inputNew = $request->only(['supplier_number', 'name', 'phone_number', 'email', 'tin', 'country', 'address', 'environment']);

            // Log the creation
            (new ApplicationLogController())->storeLog(
                $request,
                'Supplier',
                'Create',
                'Created supplier with id: ' . $newSupplier->id . ', details: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Supplier created successfully!',
                'data' => [
                    'supplier' => $newSupplier,
                ],
            ], 200);
        } catch (ValidationException $e) {
            // Catch validation errors and return specific messages
            return response()->json([
                'success' => false,
                'message' => 'Validation Error: ' . $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);

        } catch (QueryException $exception) {
            // Handle the failure and provide feedback
            return response()->json([
                'success' => false,
                'message' => 'Failed to create supplier: ' . $exception->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error('Supplier creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during supplier creation. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Supplier $supplier)
    {
        try {
            // Validate request data
            $validated = request()->validate(
                [
                    'name' => 'required|min:3|max:255',
                    'phone_number' => [
                        'required',
                        'min:10',
                        'max:10',
                        Rule::unique('suppliers')->ignore($supplier->id),
                    ],
                    'email' => [
                        'required',
                        'email',
                        Rule::unique('suppliers')->ignore($supplier->id),
                    ],
                    'tin' => [
                        'required',
                        Rule::unique('suppliers')->ignore($supplier->id),
                    ],
                    'country' => 'required',
                    'address' => 'required',
                    'updated_by' => 'nullable',
                ]
            );

            // Sanitize and normalize data
            $validated['name'] = ucwords($validated['name']);
            $validated['tin'] = strtoupper($validated['tin']);
            $validated['updated_by'] = auth()->user()->id;

            // Extract specific stored data for logging
            $inputStored = [
                'supplier_number' => $supplier->supplier_number,
                'name' => $supplier->name,
                'phone_number' => $supplier->phone_number,
                'email' => $supplier->email,
                'tin' => $supplier->tin,
                'country' => $supplier->country,
                'address' => $supplier->address,
                'environment' => $supplier->environment,
            ];

            // Update the supplier
            $supplier->update($validated);

            // Extract specific form inputs for logging
            $inputNew = $request->only(['supplier_number', 'name', 'phone_number', 'email', 'tin', 'country', 'address', 'environment']);

            // Log the update
            (new ApplicationLogController())->storeLog(
                $request,
                'Supplier',
                'Update',
                'Updated supplier with id: ' . $supplier->id . ', from: ' . json_encode($inputStored) . ', to: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'Supplier updated successfully!',
                'data' => [
                    'supplier' => $supplier,
                ],
            ], 200);
        } catch (ValidationException $e) {
            // Catch validation errors and return specific messages
            return response()->json([
                'success' => false,
                'message' => 'Validation Error: ' . $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);

        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error('Supplier update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during supplier update. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Supplier $supplier)
    {
        // Log the deleted supplier's information
        $inputStored = [
            'supplier_number' => $supplier->supplier_number,
            'name' => $supplier->name,
            'phone_number' => $supplier->phone_number,
            'email' => $supplier->email,
            'tin' => $supplier->tin,
            'country' => $supplier->country,
            'address' => $supplier->address,
            'environment' => $supplier->environment,
        ];

        (new ApplicationLogController())->storeLog(
            $request,
            'Supplier',
            'SoftDelete',
            'Deleted supplier with id: ' . $supplier->id . ', details: ' . json_encode($inputStored) . '.',
            auth()->user()->id
        );

        $supplier->delete();

        return response()->json([
            'success' => true,
            'message' => 'Supplier deleted successfully'
        ], 200);
    }
    /**
     * Remove mass resource from storage.
     */
    public function massDestroy(Request $request)
    {
        // Validate that an array of supplier IDs is provided
        $validated = $request->validate([
            'supplier_ids' => 'required|array',
            'supplier_ids.*' => 'exists:suppliers,id', // Ensures each ID exists in the suppliers table
        ]);

        $supplierIds = $validated['supplier_ids'];

        // Get the suppliers that are about to be deleted
        $suppliers = Supplier::whereIn('id', $supplierIds)->get();

        foreach ($suppliers as $supplier) {
            // Log the deleted supplier's information
            $inputStored = [
                'supplier_number' => $supplier->supplier_number,
                'name' => $supplier->name,
                'phone_number' => $supplier->phone_number,
                'email' => $supplier->email,
                'tin' => $supplier->tin,
                'country' => $supplier->country,
                'address' => $supplier->address,
                'environment' => $supplier->environment,
            ];

            // Store the log for each supplier deleted
            (new ApplicationLogController())->storeLog(
                $request,
                'Supplier',
                'SoftDelete',
                'Deleted supplier with id: ' . $supplier->id . ', details: ' . json_encode($inputStored) . '.',
                auth()->user()->id
            );

            // Perform soft delete
            $supplier->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'suppliers deleted successfully'
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(Supplier $supplier)
    {
        // Customize the data to return only the necessary fields
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $supplier->id,
                'supplier_number' => $supplier->supplier_number,
                'name' => $supplier->name,
                'phone_number' => $supplier->phone_number,
                'email' => $supplier->email,
                'tin' => $supplier->tin,
                'country' => $supplier->country,
                'address' => $supplier->address,
                'created_at' => $supplier->created_at->toDateTimeString(),
            ]
        ], 200);
    }

    /**
     * Generate the specified supplier pdf resource.
     */
    public function pdf(Supplier $supplier)
    {
        /*
        installation : composer require barryvdh/laravel-dompdf
        optional: php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider"
        */
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; text-align:left;}
                .header { font-size: 24px; margin-bottom: 20px; }
                table { width: 100%; border-collapse: collapse; }
                th, td { text-align: left; border: 1px solid #000000; }
                th { font-weight: bold; }
            </style>
        </head>
        <body>
            <big class="header">Supplier Details</big><br>
            <strong>Printed on : ' . date('Y-m-d H:i:s') . '</strong><hr>
            <table class="table align-middle table-hover m-0">
                <tr>
                    <th>SUPPLIER NUMBER/ID</th>
                    <td>' . $supplier->supplier_number . '</td>
                </tr>
                <tr>
                    <th>NAME</th>
                    <td>' . $supplier->name . '</td>
                </tr>
                <tr>
                    <th>PHONE NUMBER</th>
                    <td>' . $supplier->phone_number . '</td>
                </tr>
                <tr>
                    <th>EMAIL</th>
                    <td>' . $supplier->email . '</td>
                </tr>
                <tr>
                    <th>TIN</th>
                    <td>' . $supplier->tin . '</td>
                </tr>
                <tr>
                    <th>COUNTRY</th>
                    <td>' . $supplier->country . '</td>
                </tr>
                <tr>
                    <th>ADDRESS</th>
                    <td>' . $supplier->address . '</td>
                </tr>
                <tr>
                    <th>DATE</th>
                    <td>' . $supplier->created_at->format('Y-m-d') . '</td>
                </tr>
            </table>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        $filename = date('d_m_Y') . '_supplier_' . $supplier->id . '.pdf';
        return $pdf->download($filename);
    }

    public function xlsx()
    {
        /*
        installation: composer require maatwebsite/excel
        */
        $filename = date('d_m_Y') . '_suppliers.xlsx';
        return Excel::download(new suppliersExport, $filename);
    }

    public function csv()
    {
        $filename = date('d_m_Y') . '_suppliers.csv';
        return Excel::download(new suppliersExport, $filename);
    }


    /**
     * Get suppliers resource.
     */
    public function getSuppliers()
    {
        // Start the query to select suppliers table
        $query = Supplier::select('id', 'name')
            ->distinct()
            ->orderBy('name', 'ASC');

        // Fetch measurements
        $suppliers = $query->get(); // Execute the query to get the results

        return response()->json($suppliers);
    }
}
