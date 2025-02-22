<?php

namespace App\Http\Controllers\Api\JobSeeker;

use App\Models\JobSeeker;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class JobSeekerController extends Controller
{


    /**
     * Get Authenticated Job Seeker Profile
     */
    public function getProfile(Request $request)
    {
        $jobSeeker = Auth::guard('job_seeker')->user();

        if (!$jobSeeker) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        return response()->json($jobSeeker);
    }

       /**
     * Update the full profile of the job seeker.
     */
    public function updateProfile(Request $request)
    {
        // Determine the authenticated job seeker
        if (Auth::guard('job_seeker')->check()) {
            $jobSeeker = Auth::guard('job_seeker')->user();
        } elseif (Auth::guard('admin')->check() && $request->has('job_seeker_id')) {
            $jobSeeker = JobSeeker::findOrFail($request->job_seeker_id);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:job_seekers,email,' . $jobSeeker->id,
            'phone_number' => 'nullable|string|max:15',
            'location' => 'nullable|string|max:255',
            'join_date' => 'nullable|date',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // Update fields if present
        $jobSeeker->fill($request->only(['name', 'email', 'phone_number', 'location', 'join_date']));

        if ($request->filled('password')) {
            $jobSeeker->password = Hash::make($request->password);
        }

        $jobSeeker->save();

        return response()->json(['message' => 'Profile updated successfully'], 200);
    }

    /**
     * Update Job Seeker Profile Picture
     */
    public function updateProfilePicture(Request $request)
    {
        if (Auth::guard('job_seeker')->check()) {
            $jobSeeker = Auth::guard('job_seeker')->user();
        } elseif (Auth::guard('admin')->check() && $request->has('job_seeker_id')) {
            $jobSeeker = JobSeeker::findOrFail($request->job_seeker_id);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filePath = $jobSeeker->saveProfilePicture($request->file('profile_picture'));

        return response()->json([
            'status' => true,
            'message' => 'Profile picture updated successfully!',
            'profile_picture' => $filePath
        ]);
    }

    /**
     * Update Job Seeker Resume
     */
    public function updateResume(Request $request)
    {
        if (Auth::guard('job_seeker')->check()) {
            $jobSeeker = Auth::guard('job_seeker')->user();
        } elseif (Auth::guard('admin')->check() && $request->has('job_seeker_id')) {
            $jobSeeker = JobSeeker::findOrFail($request->job_seeker_id);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'resume' => 'required|mimes:pdf,doc,docx,jpeg,png,jpg|max:5120',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filePath = $jobSeeker->saveResume($request->file('resume'));

        return response()->json([
            'status' => true,
            'message' => 'Resume updated successfully!',
            'resume' => $filePath
        ]);
    }
}
