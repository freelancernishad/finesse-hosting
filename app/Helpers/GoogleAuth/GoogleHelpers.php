<?php


use App\Models\User;
use App\Models\JobSeeker;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Exceptions\JWTException;

function handleGoogleAuth(Request $request)
{
    // Validate the access token
    $validator = Validator::make($request->all(), [
        'access_token' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // Fetch user data from Google API
        $response = Http::get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'access_token' => $request->access_token,
        ]);

        if ($response->failed() || !isset($response['email'])) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid access token.',
            ], 400);
        }

        $userData = $response->json();
        $user = User::where('email', $userData['email'])->first();

        if (!$user) {
            // Register the user if they don't exist
            $user = User::create([
                'name' => $userData['name'] ?? explode('@', $userData['email'])[0],
                'email' => $userData['email'],
                'password' => Hash::make(Str::random(16)), // Generate a random password
                'email_verified_at' => now(),
            ]);
        }else{
            $user->update(['email_verified_at'=> now()]);
        }

        // Authenticate the user
        Auth::login($user);

        // Custom payload data
        $payload = [
            'email' => $user->email,
            'name' => $user->name,
            'category' => $user->category ?? 'default', // Assuming category might not be set for Google users
            'email_verified' => $user->hasVerifiedEmail(),
        ];

        try {
            // Generate a JWT token with custom claims
            $token = JWTAuth::fromUser($user, ['guard' => 'user']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json([
            'token' => $token,
            'user' => $payload,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'An error occurred during authentication.',
            'details' => $e->getMessage(),
        ], 500);
    }
}




 function handleGoogleAuthForJobSeeker(Request $request)
{
    // Validate the Google access token
    $validator = Validator::make($request->all(), [
        'access_token' => 'required|string',
    ]);

    if ($validator->fails()) {
        return response()->json(['errors' => $validator->errors()], 422);
    }

    try {
        // Fetch user data from Google API
        $response = Http::get('https://www.googleapis.com/oauth2/v3/userinfo', [
            'access_token' => $request->access_token,
        ]);

        if ($response->failed() || !isset($response['email'])) {
            return response()->json([
                'success' => false,
                'error' => 'Invalid access token.',
            ], 400);
        }

        $userData = $response->json();
        $jobSeeker = JobSeeker::where('email', $userData['email'])->first();

        if (!$jobSeeker) {
            // Register a new job seeker if not found
            $jobSeeker = JobSeeker::create([
                'name' => $userData['name'] ?? explode('@', $userData['email'])[0],
                'email' => $userData['email'],
                'password' => Hash::make(Str::random(16)), // Generate a random password
                'email_verified' => true, // Automatically mark email as verified
                'email_verified_at' => now(), // Set the email_verified_at timestamp
            ]);
        } else {
            // Update email verification status if already registered
            $jobSeeker->update(['email_verified' => true, 'email_verified_at' => now()]);
        }

        // Authenticate the job seeker
        Auth::login($jobSeeker);

        // Custom payload data
        $payload = [
            'email' => $jobSeeker->email,
            'name' => $jobSeeker->name,
            'email_verified' => $jobSeeker->hasVerifiedEmail(),
        ];

        try {
            // Generate a JWT token with custom claims
            $token = JWTAuth::fromUser($jobSeeker, ['guard' => 'job_seeker']);
        } catch (JWTException $e) {
            return response()->json(['error' => 'Could not create token'], 500);
        }

        return response()->json([
            'token' => $token,
            'user' => $payload,
        ], 200);
    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'error' => 'An error occurred during authentication.',
            'details' => $e->getMessage(),
        ], 500);
    }
}

