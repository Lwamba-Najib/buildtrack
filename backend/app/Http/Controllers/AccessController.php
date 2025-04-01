<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\PasswordPolicy;
use App\Models\SecuritySettings;
use Laravel\Sanctum\HasApiTokens;
use App\Services\PhpMailerService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use App\Http\Controllers\ApplicationLogController;

class AccessController extends Controller
{
    protected $passwordPolicyController;

    // Inject PasswordPolicyController via the constructor
    public function __construct(PasswordPolicyController $passwordPolicyController)
    {
        $this->passwordPolicyController = $passwordPolicyController;
    }
    public function authenticate(Request $request, PhpMailerService $phpMailerService)
    {
        // Validate the incoming request
        $validated = $request->validate(
            [
                'email' => 'required|email',
                'password' => 'required|min:6',
            ],
            [
                'email.required' => 'The email address is required.',
                'email.email' => 'Please enter a valid email address.',
                'password.required' => 'The password field is required.',
                'password.min' => 'The password must be at least :min characters.',
            ]
        );

        try {
            // Fetch the security settings from the database
            $securitySettings = SecuritySettings::first();
            if (!$securitySettings) {
                return response()->json(['error' => 'Security settings not found'], 404);
            }

            // Get the user based on the provided email
            $user = User::where('email', $validated['email'])->first();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid login credentials!',
                ], 401);
            }

            // **Check if the account is locked (failed attempts or dormancy)**
            if ($user->is_locked) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is locked due to inactivity or security reasons. Please contact support.',
                ], 403);
            }

            // Fetch login attempt settings and check failed login attempts
            $maxAttempts = $securitySettings->security_settings_login_attempt ?? 5;
            $lockoutTime = 1; // Minutes - updated to 1 minute lockout duration

            if ($user->failed_attempts >= $maxAttempts && $user->lockout_until && Carbon::parse($user->lockout_until)->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed login attempts. Please try again after 1 minute.',
                ], 429);
            }
            // If the user has exceeded max attempts and the lockout time has passed, permanently lock the account
            if ($user->failed_attempts >= $maxAttempts && Carbon::parse($user->lockout_until)->isPast()) {
                // Lock the account permanently if lockout time has passed and max attempts were still exceeded
                $user->update([
                    'is_locked' => true,  // Lock the account permanently
                    'lockout_until' => null,  // Clear the temporary lockout time
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed login attempts. Your account has been locked. Please contact support.',
                ], 403);
            }
            // Attempt to authenticate the user
            if (Auth::attempt($validated)) {
                $user = Auth::user();

                if (!$user instanceof User) {
                    return response()->json( [
                        'success' => false,
                        'message' => 'Invalid user instance.'
                    ], 400);
                }

                // Ensure the User model uses HasApiTokens
                if (!in_array(HasApiTokens::class, class_uses($user))) {
                    return response()->json([
                        'success' => false,
                        'message' => 'User model missing HasApiTokens.'
                    ], 500);
                }

                // Generate a unique session ID for this login
                $newSessionId = Str::uuid()->toString();

                // If user has an existing session ID, revoke all their tokens (log them out)
                if ($user->session_id) {
                    $user->tokens()->delete();

                    // Log the forced logout event
                    (new ApplicationLogController())->storeLog(
                        $request,
                        'Authentication',
                        'Force Logout',
                        'User was logged out from another device due to new login.',
                        $user->id
                    );
                }

                // **Reset failed attempts & unlock if applicable**
                $user->update([
                    'session_id' => $newSessionId,
                    'failed_attempts' => 0,
                    'lockout_until' => null,
                    'last_failed_attempt' => null,
                    'last_login_at' => now(),
                    'is_locked' => false, // Unlock account on successful login
                ]);

                // Check if the password has expired using `password_policy_due`
                $passwordPolicy = PasswordPolicy::where('user_id', $user->id)
                    ->orderByDesc('id')
                    ->first();

                if ($passwordPolicy && Carbon::parse($passwordPolicy->password_policy_due)->isPast()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Your password has expired. Please reset your password.',
                    ], 403);
                }

                // Check if 2FA is enabled
                $securitySettings = SecuritySettings::first();
                if ($securitySettings && $securitySettings->security_settings_2fa === 'Yes') {
                    // Generate OTP and return response indicating 2FA is required
                    $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
                    // Send the OTP via email
                    $to = $user->email; // Use the user's email address
                    $subject = 'Your OTP Code';
                    $body = "Your OTP code is: <strong>$otp</strong>. Please use this code to verify your account.";

                    $phpMailerService->sendEmail($to, $subject, $body);

                    $hashedOTP = Hash::make($otp);
                    $user->update([
                        'otp' => $hashedOTP,
                        'otp_expires_at' => now()->addMinutes(5), // OTP expires in 5 minutes
                    ]);

                    return response()->json([
                        'success' => true,
                        'message' => '2FA required.',
                        'data' => [
                            'user' => [
                                'id' => $user->id,
                                'name' => $user->name,
                                'email' => $user->email,
                            ],
                            'otp' => $otp, // Send OTP to the user (e.g., via email or SMS)
                        ],
                    ], 200);
                }

                // If 2FA is not enabled, proceed with normal authentication

                // Create token with session ID in the name for easier identification
                $token = $user->createToken('authToken_' . $newSessionId)->plainTextToken;

                // Log the authentication event
                (new ApplicationLogController())->storeLog(
                    $request,
                    'Authentication',
                    'Login',
                    'User logged in successfully.',
                    $user->id
                );

                return response()->json([
                    'success' => true,
                    'message' => 'Logged in successfully!',
                    'data' => [
                        'user' => [
                            'name' => $user->name,
                            'id' => $user->id,
                            'role_id' => $user->role_id,
                        ],
                        'token' => $token,
                    ],
                ], 200);
            }
            // Increment failed attempts
            $user->increment('failed_attempts');
            $user->update(['last_failed_attempt' => now()]);
            // Lock account if max attempts are reached
            if ($user->failed_attempts >= $maxAttempts) {
                $user->update([
                    'lockout_until' => now()->addMinutes($lockoutTime), // Optional temporary lockout
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed login attempts. Please try again after 1 minute.',
                ], 429);
            }

            // If authentication fails, return an error
            return response()->json([
                'success' => false,
                'message' => 'Invalid login credentials!',
            ], 401);

        } catch (\Exception $e) {
            // Log the exception for debugging
            Log::error('Authentication error: ' . $e->getMessage());

            // Return a generic error response
            return response()->json([
                'success' => false,
                'message' => 'An error occurred during authentication. Please try again.',
            ], 500);
        }
    }

    public function verifyOTP(Request $request)
    {
        $request->validate([
            'otp' => 'required|digits:6',
            'user_id' => 'required|exists:users,id',
        ]);

        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Check if the OTP has expired
        if ($user->otp_expires_at && now()->gt($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 401);
        }
        // Verify the OTP
        if (Hash::check($request->otp, $user->otp)) {
            // Generate new session ID
            $newSessionId = Str::uuid()->toString();

            // If user has an existing session, revoke it
            if ($user->session_id) {
                $user->tokens()->delete();

                // Log the forced logout
                (new ApplicationLogController())->storeLog(
                    $request,
                    'Authentication',
                    'Force Logout',
                    'User was logged out from another device due to new login (2FA).',
                    $user->id
                );
            }

            // Clear the OTP and its expiry time after successful verification
            $user->update([
                'session_id' => $newSessionId,
                'otp' => null,
                'otp_expires_at' => null,
            ]);
            // Create token with session ID
            $token = $user->createToken('authToken_' . $newSessionId)->plainTextToken;
            // Log the authentication event
            /* (new ApplicationLogController())->storeLog(
                $request,
                'Authentication',
                'Login',
                'User logged in successfully without working hours restrictions.',
                $user->id,
            ); */

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully!',
                'data' => [
                    'user' => [
                        'name' => $user->name,
                        'id' => $user->id,
                        'role_id' => $user->role_id,
                        'client_id' => $user->client_id,
                        'user_type' => $user->user_type,
                        'country' => $user->country,
                    ],
                    'token' => $token,
                ],
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid OTP.',
        ], 401);
    }

    public function loggedinuser(Request $request)
    {
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not authenticated',
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role_id' => $user->role_id,
                    'role_name' => $user->role->name,
                ],
            ],
        ]);
    }

    public function authenticated()
    {
        // Check if the user is authenticated
        if (Auth::check()) {
            $user = Auth::user(); // Retrieve the authenticated user
            // Get the session lifetime from the config (in minutes)
            $sessionLifetime = config('session.lifetime');

            // Calculate the time since the last request (last activity is stored automatically)
            $lastActivity = Session::get('last_activity') ?? time();
            $lastActivityTime = Carbon::createFromTimestamp($lastActivity);

            // Calculate the remaining time
            $remainingMinutes = max(0, $sessionLifetime - Carbon::now()->diffInMinutes($lastActivityTime));

            return response()->json([
                'success' => true,
                'message' => 'Session is still active!',
                'remaining_time_in_minutes' => $remainingMinutes,
            ], 200);
        }

        return response()->json([
            'success' => false,
            'message' => 'User is not authenticated.',
        ], 401);
    }

    public function getOTP(Request $request, PhpMailerService $phpMailerService)
    {
        // Retrieve the user_id from the request
        $userId = $request->input('user_id');

        // Find the user by ID
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Generate a 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Hash the OTP (optional, depending on your security requirements)
        $hashedOTP = Hash::make($otp);

        // Update the OTP column in the users table
        $user->update([
            'otp' => $hashedOTP,
            'otp_expires_at' => now()->addMinutes(5), // OTP expires in 5 minutes
        ]);

        // Send the OTP via email
        $to = $user->email; // Use the user's email address
        $subject = 'Your OTP Code';
        $body = "Your OTP code is: <strong>$otp</strong>. Please use this code to verify your account.";

        if ($phpMailerService->sendEmail($to, $subject, $body)) {
            return response()->json([
                'success' => true,
                'message' => 'OTP generated and sent successfully.',
                'data' => $otp, // For debugging purposes only; remove in production
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'OTP generated but failed to send email.',
                'data' => $otp, // For debugging purposes only; remove in production
            ], 500);
        }
    }

    public function generateUserOTP(PhpMailerService $phpMailerService)
    {
        // Get the currently authenticated user
        $user = Auth::user();

        if (!$user instanceof User) {
            return response()->json( [
                'success' => false,
                'message' => 'Invalid user instance.'
            ], 400);
        }

        // Generate a 6-digit OTP
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // Hash the OTP (optional, depending on your security requirements)
        $hashedOTP = Hash::make($otp);

        // Update the OTP column in the users table
        $user->update([
            'otp' => $hashedOTP,
            'otp_expires_at' => now()->addMinutes(5), // OTP expires in 5 minutes
        ]);

        // Send the OTP via email
        $to = $user->email; // Use the user's email address
        $subject = 'Your OTP Code';
        $body = "Your OTP code is: <strong>$otp</strong>. Please use this code to authorize the transaction.";

        if ($phpMailerService->sendEmail($to, $subject, $body)) {
            return response()->json([
                'success' => true,
                'message' => 'OTP generated and sent successfully.',
                'data' => $otp, // For debugging purposes only; remove in production
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'OTP generated but failed to send email.',
                'data' => $otp, // For debugging purposes only; remove in production
            ], 500);
        }
    }

    public function forgotPassword(Request $request, PhpMailerService $phpMailerService)
    {
        // Validate request data
        $validated = request()->validate(
            [
                'email' => 'required|email|exists:users,email', // Ensure the email exists in the users table
            ]
        );

        // Sanitize and normalize data
        $email = $validated['email'];

        // Find the user by email
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        // Generate a password using the passwordSetting() method
        $password = $this->passwordPolicyController->passwordSetting();

        if (!$password) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate password.',
            ], 500);
        }

        // Hash the password
        $hashedPassword = Hash::make($password);

        // Update the user's password
        $user->update([
            'password' => $hashedPassword,
        ]);

        // Set password expiry date
        $passwordExpiry = $this->passwordPolicyController->passwordExpiry();

        // Add the new password to the password history
        $this->passwordPolicyController->passwordPolicyIn($hashedPassword, $passwordExpiry, $user->id);

        // Send the password via email
        $to = $email; // Use the user's email address
        $subject = 'Your New Password';
        $body = "Your new password is: <strong>$password</strong>. Please use this password to log in to your account.";

        if ($phpMailerService->sendEmail($to, $subject, $body)) {
            return response()->json([
                'success' => true,
                'message' => 'Password generated and sent successfully.',
                'data' => $password, // For debugging purposes only; remove in production
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Password generated but failed to send email.',
                'data' => $password, // For debugging purposes only; remove in production
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return response()->json( [
                'success' => false,
                'message' => 'Invalid user instance.'
            ], 400);
        }
        //Log::info('Accessing logged in user data', ['user' => $user]);

        if ($user) {
            // Clear the session ID when logging out
            $user->update(['session_id' => null]);
            // Store the log
            (new ApplicationLogController())->storeLog($request, 'Authentication', 'Logout', 'User logged out successfully.', $user->id);

            // Revoke all tokens for the user
            $user->tokens()->delete();
            Auth::logout();
            Session::flush();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully!',
            ],200);
        }

        return response()->json([
            'success' => false,
            'message' => 'User not authenticated',
        ], 401);
    }
}
