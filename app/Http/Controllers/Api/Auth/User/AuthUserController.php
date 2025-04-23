<?php

namespace App\Http\Controllers\Api\Auth\User;

use Carbon\Carbon;
use App\Models\User;
use App\Mail\VerifyEmail;
use App\Models\JobSeeker;

use Illuminate\Support\Str;
use Illuminate\Http\Request;


use App\Mail\OtpNotification;
use App\Models\TokenBlacklist;

use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Validation\ValidationException;

class AuthUserController extends Controller
{
   /**
     * Register a new job seeker and send OTP for email verification.
     */
    public function register(Request $request)
    {
        if ($request->access_token) {
            return handleGoogleAuth($request);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
            'active_profile' => 'required|in:JobSeeker,Employer',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $existingUser = User::where('email', $request->email)->first();

        if ($existingUser) {
            if (!$existingUser->email_verified_at) {
                return response()->json([
                    'message' => 'This email is already registered but not verified. Please verify your email.',
                    'email' => $request->email,
                ], 400);
            }
            return response()->json([
                'message' => 'This email is already registered and verified. Please log in.',
                'email' => $request->email,
            ], 400);
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $otpExpiresAt = now()->addMinutes(10);

        // Create the new user
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => $otp,
            'otp_expires_at' => $otpExpiresAt,
            'email_verified' => false,
            'active_profile' => $request->active_profile,
        ]);

        // 👉 If active_profile is JobSeeker, create related JobSeeker profile
        if ($user->active_profile === 'JobSeeker') {
            $this->createJobSeekerProfile($user);
        } elseif ($user->active_profile === 'Employer') {

            $this->createEmployerProfile($user);
        }

        // Send OTP email
        Mail::to($user->email)->send(new OtpNotification($otp));

        // Generate token
        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json([
            'token' => $token,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'email_verified' => (bool) $user->email_verified_at,
                'active_profile' => $user->active_profile,
            ],
        ], 201);
    }

    private function createJobSeekerProfile(User $user)
    {
        JobSeeker::create([
            'user_id' => $user->id,
            'join_date' => now(),
        ]);
    }

    private function createEmployerProfile(User $user)
    {
        $user->employer()->create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'password' => $user->password,
        ]);
    }



    /**
     * Log in a job seeker only if email is verified.
     */
    public function login(Request $request)
    {
        if ($request->access_token) {
            return handleGoogleAuth($request);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
            'active_profile' => 'required|in:JobSeeker,Employer', // 👈 validate active_profile
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$user->email_verified_at) {
            return response()->json(['message' => 'Please verify your email before logging in.'], 403);
        }

        // Update active_profile on login
        $user->update([
            'active_profile' => $request->active_profile, // 👈 update active_profile
        ]);

        try {
            $token = JWTAuth::fromUser($user);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }


        // Load the related profile (either JobSeeker or Employer)
        $profile = null;
        if ($user->active_profile === 'JobSeeker') {
            $profile = $user->jobSeeker;
        } elseif ($user->active_profile === 'Employer') {
            $profile = $user->employer;
        }

        return response()->json([
            'token' => $token,
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'email_verified' => (bool) $user->email_verified_at,
                'active_profile' => $user->active_profile, // 👈 return active_profile
                'profile' => $profile,
            ],
        ], 200);
    }





     /**
     * Verify OTP and activate the job seeker account.
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = auth()->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthorized.'], 401);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email is already verified.'], 400);
        }

        if (!$user->otp || $user->otp !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        if ($user->otp_expires_at && Carbon::parse($user->otp_expires_at)->isPast()) {
            return response()->json(['message' => 'OTP has expired. Please request a new one.'], 400);
        }

        // Update user as verified
        $user->update([
            'email_verified' => true,
            'email_verified_at' => now(),
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json(['message' => 'Email verified successfully.'], 200);
    }


    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'User not found.'], 404);
        }

        if ($user->email_verified_at) {
            return response()->json(['message' => 'Email is already verified.'], 400);
        }

        // Generate a new OTP
        $otp = rand(100000, 999999);
        $otpExpiresAt = Carbon::now()->addMinutes(10); // OTP expires in 10 minutes

        // Update OTP in the database
        $user->update([
            'otp' => $otp,
            'otp_expires_at' => $otpExpiresAt,
        ]);

        // Send OTP via Email
        Mail::to($user->email)->send(new OtpNotification($otp));

        return response()->json([
            'message' => 'A new OTP has been sent to your email.',
        ], 200);
    }



/**
     * Log out the authenticated job seeker.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        // Get the Bearer token from the Authorization header
        $token = $request->bearerToken();

        // Check if the token is present
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token not provided.'
            ], 401);
        }

        // Proceed with token invalidation
        try {
            // Blacklist the token by storing it in the blacklist table
            TokenBlacklist($token);

            // Invalidate the token
            JWTAuth::setToken($token)->invalidate();

            return response()->json([
                'success' => true,
                'message' => 'Logged out successfully.'
            ], 200);
        } catch (JWTException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error while processing token: ' . $e->getMessage()
            ], 500);
        }
    }




     /**
     * Get the authenticated job seeker.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        // Get the authenticated user
        $user = Auth::guard('api')->user();

        // Load the related profile based on active_profile (either JobSeeker or Employer)
        $profile = null;
        if ($user->active_profile === 'JobSeeker') {
            $profile = $user->jobSeeker;  // Assuming there's a relationship method `jobSeeker()` on User model
        } elseif ($user->active_profile === 'Employer') {
            $profile = $user->employer;  // Assuming there's a relationship method `employer()` on User model
        }

        // Return the user data along with the profile
        return response()->json([
            'user' =>
            [
                'name' => $user->name,
                'email' => $user->email,
                'phone_number' => $user->phone_number,
                'email_verified' => (bool) $user->email_verified_at,
                'active_profile' => $user->active_profile, // 👈 return active_profile
            ],
            'profile' => $profile,  // Include the profile data (JobSeeker or Employer)
        ]);
    }




    /**
     * Change the password of the authenticated job seeker.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function changePassword(Request $request)
    {
        // Validate input using Validator
        $validator = Validator::make($request->all(), [
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:4|confirmed',
        ]);

        // Return validation errors if any
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Get the currently authenticated job seeker
        $user = Auth::guard('api')->user();

        // Check if the current password matches
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ], 400);
        }

        // Update the password
        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Password updated successfully.'
        ], 200);
    }




    /**
     * Check if a JWT token is valid.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkToken(Request $request)
    {
        $token = $request->bearerToken(); // Get the token from the Authorization header

        if (!$token) {
            return response()->json(['message' => 'Token not provided.'], 400);
        }

        try {
            $user = JWTAuth::setToken($token)->authenticate();

            if (!$user) {
                return response()->json(['message' => 'Token is invalid or job seeker not found.'], 401);
            }

            return response()->json(["message"=>"Token is valid"], 200);
        } catch (\Tymon\JWTAuth\Exceptions\TokenExpiredException $e) {
            return response()->json(['message' => 'Token has expired.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\TokenInvalidException $e) {
            return response()->json(['message' => 'Token is invalid.'], 401);
        } catch (\Tymon\JWTAuth\Exceptions\JWTException $e) {
            return response()->json(['message' => 'Token is missing or invalid.'], 401);
        }
    }





}
