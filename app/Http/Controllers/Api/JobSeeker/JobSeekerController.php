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
    public function getProfile(Request $request, $id = null)
    {
        if (Auth::guard('admin')->check()) {
            // Admin is authenticated, fetch JobSeeker by ID
            $jobSeeker = JobSeeker::with(['HiringRequests' => function ($query) {
                $query->whereIn('status', ['assigned', 'completed']); // Fetch both assigned and completed quotes
            }])->findOrFail($id);
        } else {
            // Otherwise, authenticate as JobSeeker
            $jobSeeker = Auth::guard('job_seeker')->user();
            if (!$jobSeeker) {
                return response()->json([
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Load assigned & completed request quotes for the job seeker
            $jobSeeker->load(['HiringRequests' => function ($query) {
                $query->whereIn('status', ['assigned', 'completed']); // Fetch both assigned and completed quotes
            }]);
        }

        return response()->json([
            'id' => $jobSeeker->id,
            'name' => $jobSeeker->name,
            'member_id' => $jobSeeker->member_id,
            'id_no' => $jobSeeker->id_no,
            'phone_number' => $jobSeeker->phone_number,
            'email' => $jobSeeker->email,
            'email_verified_at' => $jobSeeker->email_verified_at,
            'otp_expires_at' => $jobSeeker->otp_expires_at,
            'email_verified' => $jobSeeker->email_verified,
            'location' => $jobSeeker->location,
            'post_code' => $jobSeeker->post_code,
            'city' => $jobSeeker->city,
            'country' => $jobSeeker->country,
            'join_date' => $jobSeeker->join_date,
            'resume' => $jobSeeker->resume,
            'profile_picture' => $jobSeeker->profile_picture,
            'created_at' => $jobSeeker->created_at,
            'updated_at' => $jobSeeker->updated_at,
            'average_review_rating' => $jobSeeker->average_review_rating,
            'review_summary' => $jobSeeker->review_summary,
            'total_reviews' => $jobSeeker->total_reviews,
            'approved_job_roles' => $jobSeeker->approved_job_roles,
            'last_review' => $jobSeeker->last_review,
            'applied_jobs' => $jobSeeker->applied_jobs,
            'is_assigned_quote' => $jobSeeker->HiringRequests->where('status', 'assigned')->isNotEmpty(),
            'assigned_quotes' => $jobSeeker->HiringRequests->where('status', 'assigned')->values(),
            'completed_quotes' => $jobSeeker->HiringRequests->where('status', 'completed')->values(),
        ]);
    }



       /**
     * Update the full profile of the job seeker.
     */
    public function updateProfile(Request $request)
    {
        if (Auth::guard('job_seeker')->check()) {
            $jobSeeker = Auth::guard('job_seeker')->user();
        } elseif (Auth::guard('admin')->check() && $request->has('job_seeker_id')) {
            $jobSeeker = JobSeeker::findOrFail($request->job_seeker_id);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|unique:job_seekers,email,' . $jobSeeker->id,
            'phone_number' => 'nullable|string|max:15',
            'location' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:10',
            'city' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'join_date' => 'nullable|date',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $jobSeeker->fill($request->only([
            'name', 'email', 'phone_number', 'location', 'post_code', 'city', 'country', 'join_date'
        ]));

        if ($request->filled('password')) {
            $jobSeeker->password = Hash::make($request->password);
        }

        $jobSeeker->save();

        return response()->json(['message' => 'Profile updated successfully'], 200);
    }



    /**
     * Update Job Seeker Resume
     */
    public function updateResume(Request $request)
    {
        if (Auth::guard('api')->check()) {
            $user = Auth::guard('api')->user();
            $jobSeeker = $user->jobSeeker;

            if (!$jobSeeker) {
                return response()->json([
                    'status' => false,
                    'message' => 'JobSeeker profile not found for user.',
                ], 404);
            }
            // Check if the user's active profile is JobSeeker
            if ($user->active_profile !== 'JobSeeker') {
                return response()->json([
                    'status' => false,
                    'message' => 'You must have an active JobSeeker profile to access this.',
                ], 403);
            }



        } elseif (Auth::guard('admin')->check() && $request->has('job_seeker_id')) {
            $jobSeeker = JobSeeker::findOrFail($request->job_seeker_id);

        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate resume
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

        // Save the resume
        $filePath = $jobSeeker->saveResume($request->file('resume'));

        // Update JobSeeker model
        $jobSeeker->resume = $filePath;
        $jobSeeker->save();

        return response()->json([
            'status' => true,
            'message' => 'Resume updated successfully!',
            'resume' => $filePath
        ]);
    }

}
