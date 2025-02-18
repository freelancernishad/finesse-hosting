<?php

namespace App\Http\Controllers\Api\Auth\JobSeeker;

use App\Models\JobSeeker;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\TokenBlacklist;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

class JobSeekerAuthController extends Controller
{
    /**
     * Register a new job seeker.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:job_seekers',
            'password' => 'required|string|min:8|confirmed',
            // Add other validation rules as needed
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $jobSeeker = JobSeeker::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // Add other fields as needed
        ]);

        // Define payload data
        $payload = [
            'email' => $jobSeeker->email,
            'name' => $jobSeeker->name,
            // Add additional fields as necessary
        ];

        try {
            // Generate a JWT token for the newly created job seeker
            $token = JWTAuth::fromUser($jobSeeker);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json([
            'token' => $token,
            'jobSeeker' => $payload,
        ], 201);
    }

    /**
     * Log in a job seeker.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $credentials = $request->only('email', 'password');

        if (Auth::guard('job_seeker')->attempt($credentials)) {  // Use 'job_seeker' guard
            $jobSeeker = Auth::guard('job_seeker')->user();  // Use 'job_seeker' guard

            // Custom payload data
            $payload = [
                'email' => $jobSeeker->email,
                'name' => $jobSeeker->name,
                // Add additional fields as necessary
            ];

            try {
                // Generate a JWT token with custom claims
                $token = JWTAuth::fromUser($jobSeeker);
            } catch (JWTException $e) {
                return response()->json(['error' => 'Could not create token'], 500);
            }

            return response()->json([
                'token' => $token,
                'jobSeeker' => $payload,
            ], 200);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
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
