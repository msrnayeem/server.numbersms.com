<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\Mailer;
use App\Models\Otp;
use App\Models\PasswordOtp;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    // register
    public function register(Request $request)
    {
        try {
            // validation
            $request->validate([
                'name' => 'required|string|min:1',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|min:6|max:50|confirmed',
            ]);

            // check this email already register or not
            $existinguser = User::where('email', $request->email)->exists();
            if ($existinguser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Email already registered',
                    'data' => []
                ]);
            }

            // save data after validation
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => bcrypt($request->password)
            ]);

            // send otp code
            $verificationCode = rand(100000, 999999);
            Otp::create([
                'user_id' => $user->id,
                'otp' => $verificationCode
            ]);

            $sendMail = Mail::to($user->email)->send(new Mailer('Email Verification', 'Your verification code is: ' . $verificationCode));

            if ($sendMail) {
                return response()->json([
                    'status' => true,
                    'message' => 'Account created successfully. An email verification code has been sent.',
                    'data' => $user
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Somthing else wrong try again!',
                    'data' => []
                ]);
            }
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // login
    public function login(Request $request)
    {
        // validation
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|min:6|max:50',
        ]);

        try {

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid Email address',
                    'data' => []
                ]);
            }

            if (!$user->email_verify) {
                return response()->json([
                    'status' => false,
                    'message' => 'Please verify your email',
                    'data' => []
                ]);
            }

            // auth Facade
            $token = auth()->attempt([
                'email' => $request->email,
                'password' => $request->password
            ]);

            if (!$token) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid login details',
                    'data' => []
                ]);
            }
            $cookie = cookie('token', $token, 30 * 24 * 60);
            return response()->json([
                'status' => true,
                'message' => 'User logged in',
                'data' => auth()->user(),
                'token' => $token,
                'expires_in' => auth()->factory()->getTTL() * 60
            ])->cookie($cookie);

        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // profile
    public function profile()
    {
        try {
            $userData = auth()->user();
            return response()->json([
                'status' => true,
                'message' => 'user profile data',
                'data' => $userData
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // update prifle
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:1',
        ]);

        // update profile
        try {
            $user = auth()->user();
            $user->name = $request->name;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'User profile updated  success',
                'data' => auth()->user()
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // refresh token
    public function refreshToken()
    {
        try {
            $token = auth()->refresh();

            return response()->json([
                'status' => true,
                'message' => 'New token making success',
                'data' => [],
                'token' => $token,
                'expires_in' => auth()->factory()->getTTL() * 60
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }

    }

    // logout
    public function logout()
    {
        try {
            auth()->logout();

            return response()->json([
                'status' => true,
                'message' => 'User logged out',
                'data' => []
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // verify email
    public function verifyEmail(Request $request)
    {
        $request->validate([
            'otp' => 'required|min:6',
            'email' => 'required|email',
            'password' => 'required|min:6|max:50'
        ]);

        try {
            $exituser = User::where('email', $request->email)->first();

            if (!$exituser) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email address',
                    'data' => []
                ]);
            }

            $otp = Otp::where('otp', $request->otp)
                ->where('user_id', $exituser->id)
                ->first();

            if ($otp) {
                if ($otp->created_at < Carbon::now()->subMinutes(10)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Verification code timeout',
                        'data' => []
                    ]);
                } else {
                    $user = User::find($otp->user_id);
                    $user->email_verify = true;
                    $user->save();

                    $otp->delete();

                    $token = auth()->attempt([
                        'email' => $request->email,
                        'password' => $request->password
                    ]);

                    if (!$token) {
                        return response()->json([
                            'status' => false,
                            'message' => 'Invalid login details',
                            'data' => []
                        ]);
                    }

                    $cookie = cookie('token', $token, auth()->factory()->getTTL() * 60);
                    return response()->json([
                        'status' => true,
                        'message' => 'User logged in',
                        'data' => auth()->user(),
                        'token' => $token,
                        'expires_in' => auth()->factory()->getTTL() * 60
                    ])->cookie($cookie);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid OTP code',
                    'data' => []
                ]);
            }
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // reset password
    public function resetPassword(Request $request)
    {
        $request->validate([
            'otp' => 'required|min:6',
            'password' => 'required|min:6|max:50|confirmed'
        ]);

        try {
            $otp = PasswordOtp::where('otp', $request->otp)->first();

            if ($otp) {
                if ($otp->created_at < Carbon::now()->subMinutes(5)) {
                    return response()->json([
                        'status' => false,
                        'message' => 'Verification code timeout',
                        'data' => []
                    ]);
                } else {
                    $user = User::where('email', $otp->user_id)->first();
                    $user->password = bcrypt($request->password);
                    $user->save();

                    $otp->delete();
                    return response()->json([
                        'status' => true,
                        'message' => 'Password change success',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid OTP code',
                    'data' => []
                ]);
            }
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // chanage password when user loggden
    public function chanagePassword(Request $request)
    {
        $request->validate([
            'old_password' => 'required|min:6|max:50',
            'password' => 'required|min:6|max:50|confirmed'
        ]);

        try {
            if (!Hash::check($request->old_password, auth()->user()->password)) {
                return response()->json([
                    'status' => false,
                    'message' => 'Password does not match.',
                    'data' => []
                ]);
            }

            $user = auth()->user();
            $user->password = bcrypt($request->password);
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Password change success',
                'data' => []
            ]);
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // forget password send mail
    public function forgetPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            $verificationCode = rand(100000, 999999);
            $user = User::where('email', $request->email)->first();

            if ($user) {
                PasswordOtp::create([
                    'user_id' => $request->email,
                    'otp' => $verificationCode
                ]);

                $sendMail = Mail::to($user->email)->send(new Mailer('Reset password', 'Your OTP code is: ' . $verificationCode));

                if ($sendMail) {
                    return response()->json([
                        'status' => true,
                        'message' => 'Verification email has been sent successfully',
                        'data' => []
                    ]);
                } else {
                    return response()->json([
                        'status' => false,
                        'message' => 'Somthing else wrong try again!',
                        'data' => []
                    ]);
                }
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email',
                    'data' => []
                ]);
            }
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }

    // send otp
    public function sendotp(Request $request)
    {
        $request->validate([
            'email' => 'required|email'
        ]);

        try {
            $verificationCode = rand(100000, 999999);

            $user = User::where('email', $request->email)->first();
            if (!$user) {
                return response()->json([
                    'status' => false,
                    'message' => 'Invalid email address',
                    'data' => []
                ]);
            }

            Otp::create([
                'user_id' => $user->id,
                'otp' => $verificationCode
            ]);

            $sendMail = Mail::to($request->email)->send(new Mailer('Email Verification', 'Your verification code is: ' . $verificationCode));

            if ($sendMail) {
                return response()->json([
                    'status' => true,
                    'message' => 'Verification email has been sent successfully',
                    'data' => []
                ]);
            } else {
                return response()->json([
                    'status' => false,
                    'message' => 'Somthing else wrong try again!',
                    'data' => []
                ]);
            }
        } catch (\Exception $th) {
            return response()->json([
                'status' => false,
                'message' => $th->getMessage(),
                'data' => []
            ]);
        }
    }
}
