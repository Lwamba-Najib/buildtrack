<?php

namespace App\Http\Controllers;

use App\Models\AddressBook;
use Illuminate\Http\Request;

class AddressBookController extends Controller
{
    /**
     * Display the specified resource.
     */
    public function show($phone_number) // Accept phone number as a parameter
    {
        // Fetch the data from the address book
        $data = AddressBook::where('phone_number', $phone_number)->first();

        if ($data) {
            return response()->json([
                'success' => true,
                'data' => $data,
            ],200);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Number not found in the address book',
            ], 404);
        }
    }
}
