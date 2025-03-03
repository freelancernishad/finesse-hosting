<?php

namespace App\Http\Controllers\Api\Auth\JobSeeker;

use Carbon\Carbon;
use App\Models\JobSeeker;
use Illuminate\Http\Request;
use App\Mail\JobSeekerOtpMail;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class JobSeekerAuthController extends Controller
{
    /**
     * Register a new job seeker and send OTP for email verification.
     */
    public function register(Request $request)
    {
        // Check if Google access token is provided
        if ($request->access_token) {
            return handleGoogleAuthForJobSeeker($request);
        }

        // Validate the input data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Check if the email is already taken and if it's verified
        $existingJobSeeker = JobSeeker::where('email', $request->email)->first();

        if ($existingJobSeeker) {
            // If the email is taken but not verified
            if (!$existingJobSeeker->email_verified) {
                return response()->json([
                    'message' => 'This email is already registered but not verified. Please verify your email.',
                    "email" => $request->email,
                ], 400);
            }
            // If the email is already verified
            return response()->json([
                'message' => 'This email is already registered and verified. Please log in.',
                "email" => $request->email,
            ], 400);
        }

        // Generate OTP (this will only happen for new registrations)
        $otp = rand(100000, 999999);
        $otpExpiresAt = now()->addMinutes(10); // OTP expires in 10 minutes

        // Create the new job seeker account
        $jobSeeker = JobSeeker::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => $otp,
            'otp_expires_at' => $otpExpiresAt,
            'email_verified' => false,
        ]);

        // Send OTP via email
        Mail::to($jobSeeker->email)->send(new JobSeekerOtpMail($otp));

        return response()->json([
            'message' => 'Registration successful! An OTP has been sent to your email.',
            "email" => $jobSeeker->email,
            "email_verified" => $jobSeeker->email_verified,
        ], 201);
    }






    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:job_seekers,email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $jobSeeker = JobSeeker::where('email', $request->email)->first();

        if (!$jobSeeker) {
            return response()->json(['message' => 'Job seeker not found.'], 404);
        }

        if ($jobSeeker->email_verified) {
            return response()->json(['message' => 'Email is already verified.'], 400);
        }

        // Generate a new OTP
        $otp = rand(100000, 999999);
        $otpExpiresAt = Carbon::now()->addMinutes(10); // OTP expires in 10 minutes

        // Update OTP in the database
        $jobSeeker->update([
            'otp' => $otp,
            'otp_expires_at' => $otpExpiresAt,
        ]);

        // Send OTP via Email
        Mail::to($jobSeeker->email)->send(new JobSeekerOtpMail($otp));

        return response()->json([
            'message' => 'A new OTP has been sent to your email.',
        ], 200);
    }


    /**
     * Verify OTP and activate the job seeker account.
     */
    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email|exists:job_seekers,email',
            'otp' => 'required|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $jobSeeker = JobSeeker::where('email', $request->email)->first();

        if (!$jobSeeker) {
            return response()->json(['message' => 'Job seeker not found.'], 404);
        }

        if ($jobSeeker->email_verified) {
            return response()->json(['message' => 'Email is already verified.'], 400);
        }

        if (!$jobSeeker->otp || $jobSeeker->otp !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP.'], 400);
        }

        // Check if OTP has expired
        if ($jobSeeker->otp_expires_at && Carbon::parse($jobSeeker->otp_expires_at)->isPast()) {
            return response()->json(['message' => 'OTP has expired. Please request a new one.'], 400);
        }

        // Mark email as verified and update email_verified_at timestamp
        $jobSeeker->update([
            'email_verified' => true,
            'email_verified_at' => now(),
            'otp' => null,
            'otp_expires_at' => null,
        ]);

        return response()->json(['message' => 'Email verified successfully. You can now log in.'], 200);
    }


    /**
     * Log in a job seeker only if email is verified.
     */
    public function login(Request $request)
    {



        if($request->access_token){

            return handleGoogleAuthForJobSeeker($request);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        $jobSeeker = JobSeeker::where('email', $request->email)->first();

        if (!$jobSeeker || !Hash::check($request->password, $jobSeeker->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        if (!$jobSeeker->email_verified) {
            return response()->json(['message' => 'Please verify your email before logging in.'], 403);
        }

        try {
            $token = JWTAuth::fromUser($jobSeeker);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }
        return response()->json([
            'token' => $token,
            'jobSeeker' => [
                'name' => $jobSeeker->name,
                'email' => $jobSeeker->email,
                'phone_number' => $jobSeeker->phone_number,
                'location' => $jobSeeker->location,
                'join_date' => $jobSeeker->join_date,
                'post_code' => $jobSeeker->post_code,
                'city' => $jobSeeker->city,
                'country' => $jobSeeker->country,
                'email_verified' => $jobSeeker->email_verified,
            ],
        ], 200);

    }


     /**
     * Get the authenticated job seeker.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function me(Request $request)
    {
        return response()->json(Auth::guard('job_seeker')->user());
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
        $jobSeeker = Auth::guard('job_seeker')->user();

        // Check if the current password matches
        if (!Hash::check($request->current_password, $jobSeeker->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect.'
            ], 400);
        }

        // Update the password
        $jobSeeker->password = Hash::make($request->new_password);
        $jobSeeker->save();

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
            $jobSeeker = JWTAuth::setToken($token)->authenticate();

            if (!$jobSeeker) {
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
