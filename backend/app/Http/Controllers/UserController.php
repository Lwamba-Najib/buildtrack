<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Str;
use App\Exports\UsersExport;
use Illuminate\Http\Request;
use App\Enums\PaginationSize;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\Rule;
use App\Models\SecuritySettings;
use App\Services\PhpMailerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use App\Http\Controllers\PasswordPolicyController;

class UserController extends Controller
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
            $query = User::with('role:id,name');

            // Apply search filters
            if ($request->has('search') && !empty($request->input('search'))) {
                $searchTerm = $request->input('search');
                $query->where(function ($query) use ($searchTerm) {
                    $query->where('user_number', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('name', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('phone_number', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('nin', 'LIKE', '%' . $searchTerm . '%')
                        ->orWhere('email', 'LIKE', '%' . $searchTerm . '%');
                });
            }

            // Apply gender filter
            if ($request->has('gender') && !empty($request->input('gender'))) {
                $query->where('gender', $request->input('gender'));
            }

            // Apply role filter
            if ($request->has('role') && !empty($request->input('role'))) {
                $query->where('role_id', $request->input('role'));
            }

            // Fetch the paginated data
            $users = $query->orderBy('id', 'DESC')->paginate($paginationSize);

            return response()->json($users);
        } catch (\Exception $e) {
            Log::error('Error in Listing Users: ' . $e->getMessage());
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
        // Default password
        $defaultPassword = 'password';
        try {
            // Generate the 16-character UUID and prepend with 'UR'
            do {
                $uuid = strtoupper(substr(str_replace('-', '', Str::uuid()->toString()), 0, 14));
            } while (str_starts_with($uuid, '0'));

            $user_number = 'UR' . $uuid;

            // Validate request data
            $validated = request()->validate(
                [
                    'user_number'=> 'nullable',
                    'name' => 'required|min:3|max:255',
                    'gender' => 'required',
                    'nin' => 'required|min:14|max:14|unique:users,nin',
                    'phone_number' => 'required|unique:users,phone_number',
                    'email' => 'required|email|unique:users,email',
                    'password' => 'nullable',
                    'role_id' => 'required',
                    'environment' => 'required|in:PRODUCTION,TEST,DEVELOPMENT',
                    'created_by' => 'nullable',
                ],
                [
                    'role_id.required' => 'The role field is required.',
                ]
            );

            // Sanitize and normalize data
            $validated['user_number'] = $user_number;
            $validated['name'] = ucwords($validated['name']);
            $validated['nin'] = strtoupper($validated['nin']);
            $validated['password'] = Hash::make($defaultPassword);
            $validated['environment'] = strtoupper($validated['environment']);
            $validated['created_by'] = auth()->user()->id;

            // Create user
            $newUser = User::create($validated);

            // Extract specific form inputs for logging
            $inputNew = $request->only(['user_number', 'name', 'gender', 'nin', 'phone_number', 'email', 'role', 'environment']);

            // Log the creation
            (new ApplicationLogController())->storeLog(
                $request,
                'User',
                'Create',
                'Created user with id: ' . $newUser->id . ', details: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'User created successfully!',
                'data' => [
                    'user' => $newUser,
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
                'message' => 'Failed to create user: ' . $exception->getMessage(),
            ], 500);
        } catch (\Exception $e) {
            // Handle other exceptions
            Log::error('User creation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during user creation. Please try again.',
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        try {
            // Validate request data
            $validated = request()->validate(
                [
                    'name' => 'required|min:3|max:255',
                    'gender' => 'required',
                    'nin' => [
                        'required',
                        'min:14',
                        'max:14',
                        Rule::unique('users')->ignore($user->id),
                    ],
                    'phone_number' => [
                        'required',
                        'min:10',
                        'max:10',
                        Rule::unique('users')->ignore($user->id),
                    ],
                    'email' => [
                        'required',
                        'email',
                        Rule::unique('users')->ignore($user->id),
                    ],
                    'password' => 'nullable',
                    'role_id' => 'required',
                    'updated_by' => 'nullable',
                ],
                [
                    'role_id.required' => 'The role field is required.'
                ]
            );

            // Sanitize and normalize data
            $validated['name'] = ucwords($validated['name']);
            $validated['nin'] = strtoupper($validated['nin']);
            $validated['updated_by'] = auth()->user()->id;

            // Extract specific stored data for logging
            $inputStored = [
                'user_number' => $user->user_number,
                'name' => $user->name,
                'gender' => $user->gender,
                'nin' => $user->nin,
                'phone_number' => $user->phone_number,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'environment' => $user->environment,
            ];

            // Update the user
            $user->update($validated);

            // Extract specific form inputs for logging
            $inputNew = $request->only(['user_number','name', 'gender', 'nin', 'phone_number', 'email', 'role', 'environment']);

            // Log the update
            (new ApplicationLogController())->storeLog(
                $request,
                'User',
                'Update',
                'Updated user with id: ' . $user->id . ', from: ' . json_encode($inputStored) . ', to: ' . json_encode($inputNew) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully!',
                'data' => [
                    'user' => $user,
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
            Log::error('User update error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during user update. Please try again.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user)
    {
        // Log the deleted user's information
        $inputStored = [
            'user_number' => $user->user_number,
            'name' => $user->name,
            'gender' => $user->gender,
            'nin' => $user->nin,
            'phone_number' => $user->phone_number,
            'email' => $user->email,
            'role_id' => $user->role_id,
            'environment' => $user->environment,
        ];

        (new ApplicationLogController())->storeLog(
            $request,
            'User',
            'SoftDelete',
            'Deleted user with id: ' . $user->id . ', details: ' . json_encode($inputStored) . '.',
            auth()->user()->id
        );

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully'
        ], 200);
    }
    /**
     * Remove mass resource from storage.
     */
    public function massDestroy(Request $request)
    {
        // Validate that an array of user IDs is provided
        $validated = $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id', // Ensures each ID exists in the users table
        ]);

        $userIds = $validated['user_ids'];

        // Get the users that are about to be deleted
        $users = User::whereIn('id', $userIds)->get();

        foreach ($users as $user) {
            // Log the deleted user's information
            $inputStored = [
                'user_number' => $user->user_number,
                'name' => $user->name,
                'gender' => $user->gender,
                'nin' => $user->nin,
                'phone_number' => $user->phone_number,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'environment' => $user->environment,
            ];

            // Store the log for each user deleted
            (new ApplicationLogController())->storeLog(
                $request,
                'User',
                'SoftDelete',
                'Deleted user with id: ' . $user->id . ', details: ' . json_encode($inputStored) . '.',
                auth()->user()->id
            );

            // Perform soft delete
            $user->delete();
        }

        return response()->json([
            'success' => true,
            'message' => 'Users deleted successfully'
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        // Customize the data to return only the necessary fields
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'user_number' => $user->user_number,
                'name' => $user->name,
                'gender' => $user->gender,
                'nin' => $user->nin,
                'phone_number' => $user->phone_number,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role' => $user->role->name,
                'environment' => $user->environment,
                'created_at' => $user->created_at->toDateTimeString(),
            ]
        ], 200);
    }

    /**
     * Generate the specified user pdf resource.
     */
    public function pdf(User $user)
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
            <big class="header">User Details</big><br>
            <strong>Printed on : ' . date('Y-m-d H:i:s') . '</strong><hr>
            <table class="table align-middle table-hover m-0">
                <tr>
                    <th>USER NUMBER/ID</th>
                    <td>' . $user->user_number . '</td>
                </tr>
                <tr>
                    <th>NAME</th>
                    <td>' . $user->name . '</td>
                </tr>
                <tr>
                    <th>GENDER</th>
                    <td>' . $user->gender . '</td>
                </tr>
                <tr>
                    <th>NIN</th>
                    <td>' . $user->nin . '</td>
                </tr>
                <tr>
                    <th>PHONE NUMBER</th>
                    <td>' . $user->phone_number . '</td>
                </tr>
                <tr>
                    <th>EMAIL</th>
                    <td>' . $user->email . '</td>
                </tr>
                <tr>
                    <th>ROLE</th>
                    <td>' . $user->role->name . '</td>
                </tr>
                <tr>
                    <th>DATE</th>
                    <td>' . $user->created_at->format('Y-m-d') . '</td>
                </tr>
            </table>
        </body>
        </html>';

        $pdf = Pdf::loadHTML($html)->setPaper('A4', 'portrait');
        $filename = date('d_m_Y') . '_user_' . $user->id . '.pdf';
        return $pdf->download($filename);
    }

    public function xlsx()
    {
        /*
        installation: composer require maatwebsite/excel
        */
        $filename = date('d_m_Y') . '_users.xlsx';
        return Excel::download(new UsersExport, $filename);
    }

    public function csv()
    {
        $filename = date('d_m_Y') . '_users.csv';
        return Excel::download(new UsersExport, $filename);
    }

    /**
     * Get user gender resource.
     */
    public function userGender()
    {
        // Fetch gender from the users table
        $gender = User::select('gender')
            ->distinct()
            ->orderBy('gender', 'ASC')
            ->get();

        return response()->json($gender);
    }

    /**
     * Get user roles resource.
     */
    public function userRoles()
    {
        try {
            // Fetch the roles with business_name
            $roles = Role::get(['roles.id', 'roles.name']);

            return response()->json($roles);
        } catch (\Exception $e) {
            Log::error('Error in Fetching User Roles: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }
    /**
     * Change the user's password.
     */
    public function userChangePassword(Request $request)
    {
        try {
            // Validate the request data
            $validated = $request->validate([
                'current_password' => 'required|string',
                'new_password' => 'required|string|confirmed',
            ]);

            $user = auth()->user(); // Get the currently authenticated user

            if (!$user instanceof User) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found.',
                ], 404);
            }

            // Check if the current password is correct
            if (!Hash::check($validated['current_password'], $user->password)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The current password is incorrect.',
                ], 422);
            }

            // Fetch security settings
            $settings = SecuritySettings::first();
            if (!$settings) {
                return response()->json([
                    'success' => false,
                    'message' => 'Security settings not found.',
                ], 500);
            }

            // Instantiate PasswordPolicyController to access methods
            $policyController = new PasswordPolicyController();
            $characterSets = $policyController->getCharacterSets();

            $validChars = '';
            if ($settings->security_settings_lowercase === 'Yes') {
                $validChars .= $characterSets['lowercase'];
            }
            if ($settings->security_settings_uppercase === 'Yes') {
                $validChars .= $characterSets['uppercase'];
            }
            if ($settings->security_settings_numbers === 'Yes') {
                $validChars .= $characterSets['numbers'];
            }
            if ($settings->security_settings_symbols === 'Yes') {
                $validChars .= $characterSets['symbols'];
            }

            $securityConfig = [
                'security_settings_length' => $settings->security_settings_length ?? 8,
            ];

            // Validate new password based on security settings
            $passwordValidationResult = $policyController->validatePassword($validated['new_password'], $validChars, $securityConfig);

            if ($passwordValidationResult !== true) {
                return response()->json([
                    'success' => false,
                    'message' => $passwordValidationResult,
                ], 422);
            }

            // Ensure new password is different from the current one
            if ($validated['new_password'] === $validated['current_password']) {
                return response()->json([
                    'success' => false,
                    'message' => 'New password cannot be the same as the current password.',
                ], 422);
            }

            // Check password history to prevent reuse
            $historyLimit = $settings->security_settings_history_counts ?? 5;
            if (!$policyController->passwordPolicyV($user->id, $validated['new_password'], $historyLimit)) {
                return response()->json([
                    'success' => false,
                    'message' => 'You cannot reuse a recently used password.',
                ], 422);
            }

            // Update the user's password
            $user->password = Hash::make($validated['new_password']);
            $user->save();

            // Store password in history
            $hashedPassword = Hash::make($validated['new_password']); // Encrypt password
            $passwordExpiry = $policyController->passwordExpiry();
            $policyController->passwordPolicyIn($hashedPassword, $passwordExpiry,$user->id);

            // Log the password change
            (new ApplicationLogController())->storeLog(
                $request,
                'User',
                'Change Password',
                'User with ID ' . $user->id . ' changed their password.',
                $user->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Password updated successfully!',
            ], 200);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation Error: ' . $e->validator->errors()->first(),
                'errors' => $e->validator->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error changing password: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating the password. Please try again.',
            ], 500);
        }
    }

    public function userPersonalDetails()
    {
        // Get the currently authenticated user
        $user = auth()->user();

        // Check if the user is authenticated
        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated.',
            ], 401); // Unauthorized status
        }

        // Customize the data to return only the necessary fields
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'user_number' => $user->user_number,
                'name' => $user->name,
                'gender' => $user->gender,
                'nin' => $user->nin,
                'phone_number' => $user->phone_number,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'role' => $user->role->name,
                'environment' => $user->environment,
                'created_at' => $user->created_at->toDateTimeString(),
            ]
        ], 200);
    }
    /**
     * lock/unlock the specified resource in storage.
     */
    public function userLockUnlock(User $user)
    {
        try {

            // Toggle lock status (1 → 0, 0 → 1)
            $newStatus = !$user->is_locked;

            // Update user record
            $user->update([
                'is_locked' => $newStatus,
                'failed_attempts' => 0,
                'lockout_until' => null,
                'last_failed_attempt' => null,
                'updated_by' => auth()->id(),
            ]);

            // Log the deleted user's information
            $inputStored = [
                'user_number' => $user->user_number,
                'name' => $user->name,
                'gender' => $user->gender,
                'nin' => $user->nin,
                'phone_number' => $user->phone_number,
                'email' => $user->email,
                'role_id' => $user->role_id,
                'environment' => $user->environment,
            ];
            // Log the action
            (new ApplicationLogController())->storeLog(
                request(),
                'User',
                'LockUnlock',
                ($user->is_locked ? 'Locked' : 'Unlocked') . ' user with ID: ' . $user->id . ', details: ' . json_encode($inputStored) . '.',
                auth()->user()->id
            );

            // Return success response
            return response()->json([
                'success' => true,
                'message' => 'User successfully ' . ($user->is_locked ? 'locked' : 'unlocked') . '!',
                'data' => [
                    'user' => $user,
                ],
            ], 200);

        } catch (\Exception $e) {
            // Log error and return response
            Log::error('User lock/unlock error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while locking/unlocking the user.',
            ], 500);
        }
    }
}
