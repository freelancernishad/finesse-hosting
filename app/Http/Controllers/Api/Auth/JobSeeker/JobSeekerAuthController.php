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

        if($request->access_token){

            return handleGoogleAuthForJobSeeker($request);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:job_seekers',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Generate OTP
        $otp = rand(100000, 999999);
        $otpExpiresAt = now()->addMinutes(10); // OTP expires in 10 minutes

        $jobSeeker = JobSeeker::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'otp' => $otp,
            'otp_expires_at' => $otpExpiresAt,
            'email_verified' => false,
        ]);

        // Send OTP via Email
        Mail::to($jobSeeker->email)->send(new JobSeekerOtpMail($otp));

        return response()->json([

            'message' => 'Registration successful! An OTP has been sent to your email.',
            "email" => $request->email
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
                'email' => $jobSeeker->email,
                'name' => $jobSeeker->name,
                'email_verified' => $jobSeeker->email_verified,
            ],
        ], 200);
    }
}
