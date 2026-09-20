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
                return response()->json(['success' => false, 'message' => 'Security settings not found'], 404);
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
            $lockoutTime = 1; // Minutes

            if ($user->failed_attempts >= $maxAttempts && $user->lockout_until && Carbon::parse($user->lockout_until)->isFuture()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed login attempts. Please try again after 1 minute.',
                ], 429);
            }
            
            // If the user has exceeded max attempts and the lockout time has passed, permanently lock the account
            if ($user->failed_attempts >= $maxAttempts && Carbon::parse($user->lockout_until)->isPast()) {
                $user->update([
                    'is_locked' => true,
                    'lockout_until' => null,
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
                    return response()->json([
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
                    'is_locked' => false,
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
                    $to = $user->email;
                    $subject = 'Your OTP Code';
                    $body = "Your OTP code is: <strong>$otp</strong>. Please use this code to verify your account.";

                    $phpMailerService->sendEmail($to, $subject, $body);

                    $hashedOTP = Hash::make($otp);
                    $user->update([
                        'otp' => $hashedOTP,
                        'otp_expires_at' => now()->addMinutes(5),
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
                            'otp' => $otp,
                        ],
                    ], 200);
                }

                // If 2FA is not enabled, proceed with normal authentication
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
                    'lockout_until' => now()->addMinutes($lockoutTime),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Too many failed login attempts. Please try again after 1 minute.',
                ], 429);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid login credentials!',
            ], 401);

        } catch (\Exception $e) {
            Log::error('Authentication error: ' . $e->getMessage());
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

        if ($user->otp_expires_at && now()->gt($user->otp_expires_at)) {
            return response()->json([
                'success' => false,
                'message' => 'OTP has expired. Please request a new one.',
            ], 401);
        }
        
        if (Hash::check($request->otp, $user->otp)) {
            $newSessionId = Str::uuid()->toString();

            if ($user->session_id) {
                $user->tokens()->delete();

                (new ApplicationLogController())->storeLog(
                    $request,
                    'Authentication',
                    'Force Logout',
                    'User was logged out from another device due to new login (2FA).',
                    $user->id
                );
            }

            $user->update([
                'session_id' => $newSessionId,
                'otp' => null,
                'otp_expires_at' => null,
            ]);
            
            $token = $user->createToken('authToken_' . $newSessionId)->plainTextToken;

            return response()->json([
                'success' => true,
                'message' => 'OTP verified successfully!',
                'data' => [
                    'user' => [
                        'name' => $user->name,
                        'id' => $user->id,
                        'role_id' => $user->role_id,
                        'country' => $user->country ?? 'Unknown',
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

        // Safely get role name to prevent crash if relationship is broken or null
        $roleName = $user->role ? $user->role->name : 'Super Admin';

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'role_id' => $user->role_id,
                    'role_name' => $roleName,
                ],
            ],
        ]);
    }

    public function authenticated()
    {
        if (Auth::check()) {
            $user = Auth::user();
            $sessionLifetime = config('session.lifetime');

            $lastActivity = Session::get('last_activity') ?? time();
            $lastActivityTime = Carbon::createFromTimestamp($lastActivity);

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
        $userId = $request->input('user_id');

        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hashedOTP = Hash::make($otp);

        $user->update([
            'otp' => $hashedOTP,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $to = $user->email;
        $subject = 'Your OTP Code';
        $body = "Your OTP code is: <strong>$otp</strong>. Please use this code to verify your account.";

        if ($phpMailerService->sendEmail($to, $subject, $body)) {
            return response()->json([
                'success' => true,
                'message' => 'OTP generated and sent successfully.',
                'data' => $otp,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'OTP generated but failed to send email.',
                'data' => $otp,
            ], 500);
        }
    }

    public function generateUserOTP(PhpMailerService $phpMailerService)
    {
        $user = Auth::user();

        if (!$user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid user instance.'
            ], 400);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $hashedOTP = Hash::make($otp);

        $user->update([
            'otp' => $hashedOTP,
            'otp_expires_at' => now()->addMinutes(5),
        ]);

        $to = $user->email;
        $subject = 'Your OTP Code';
        $body = "Your OTP code is: <strong>$otp</strong>. Please use this code to authorize the transaction.";

        if ($phpMailerService->sendEmail($to, $subject, $body)) {
            return response()->json([
                'success' => true,
                'message' => 'OTP generated and sent successfully.',
                'data' => $otp,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'OTP generated but failed to send email.',
                'data' => $otp,
            ], 500);
        }
    }

    public function forgotPassword(Request $request, PhpMailerService $phpMailerService)
    {
        $validated = request()->validate(
            [
                'email' => 'required|email|exists:users,email',
            ]
        );

        $email = $validated['email'];

        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $password = $this->passwordPolicyController->passwordSetting();

        if (!$password) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate password.',
            ], 500);
        }

        $hashedPassword = Hash::make($password);

        $user->update([
            'password' => $hashedPassword,
        ]);

        $passwordExpiry = $this->passwordPolicyController->passwordExpiry();
        $this->passwordPolicyController->passwordPolicyIn($hashedPassword, $passwordExpiry, $user->id);

        $to = $email;
        $subject = 'Your New Password';
        $body = "Your new password is: <strong>$password</strong>. Please use this password to log in to your account.";

        if ($phpMailerService->sendEmail($to, $subject, $body)) {
            return response()->json([
                'success' => true,
                'message' => 'Password generated and sent successfully.',
                'data' => $password,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Password generated but failed to send email.',
                'data' => $password,
            ], 500);
        }
    }

    public function logout(Request $request)
    {
        $user = Auth::user();
        if (!$user instanceof User) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid user instance.'
            ], 400);
        }

        $user->update(['session_id' => null]);
        
        (new ApplicationLogController())->storeLog($request, 'Authentication', 'Logout', 'User logged out successfully.', $user->id);

        $user->tokens()->delete();
        Session::flush();
        
        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully!',
        ], 200);
    }
}