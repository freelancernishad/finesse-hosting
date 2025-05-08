<?php

namespace App\Http\Controllers\Api\JobSeeker;

use App\Models\AppliedJob;
use App\Models\JobCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class JobApplicationController extends Controller
{
    /**
     * Apply for a job.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function applyForJob(Request $request)
{
    // Authenticate user with 'api' guard
    $user = Auth::guard('api')->user();

    if (!$user) {
        return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    }

    // Check if the user's active profile is JobSeeker
    if ($user->active_profile !== 'JobSeeker') {
        return response()->json([
            'status' => false,
            'message' => 'You must have an active JobSeeker profile to access this.',
        ], 403);
    }

    // Retrieve JobSeeker profile
    $jobSeeker = $user->jobSeeker;

    if (!$jobSeeker) {
        return response()->json(['status' => false, 'message' => 'JobSeeker profile not found'], 404);
    }

    // Validate only the fields that are not coming from the user
    $validator = Validator::make($request->all(), [
        'interest_file' => 'nullable|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        'area' => 'required|array|min:1',
        'area.*' => 'string|max:255',
        'job_category_id' => 'required|exists:job_categories,id',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

    // Check if the job seeker already has an application in this category
    $existingApplication = AppliedJob::where('job_seeker_id', $jobSeeker->id)
        ->where('job_category_id', $request->job_category_id)
        ->exists();

    if ($existingApplication) {
        return response()->json([
            'status' => false,
            'message' => 'You have already applied for this job category.',
        ], 403);
    }

    // Retrieve the JobCategory name
    $jobCategory = JobCategory::find($request->job_category_id);

    // Store the applied job data using data from the user
    $Waiting_list = AppliedJob::create([
        'name' => $user->name,
        'phone' => $user->jobSeeker->phone ?? null,
        'email' => $user->email,
        'date_of_birth' => $user->jobSeeker->date_of_birth ?? null,
        'country' => $user->country,
        'city' => $user->city,
        'post_code' => $user->zip_code,
        'address' => $user->street_address,
        'area' => $request->area, // storing area as JSON
        'category' => $jobCategory->name,
        'job_category_id' => $jobCategory->id,
        'job_seeker_id' => $jobSeeker->id,
    ]);

    // Handle the interest file upload
    if ($request->hasFile('interest_file')) {
        $Waiting_list->saveInterestFile($request->file('interest_file'));
    }

    return response()->json([
        'status' => true,
        'message' => 'Waiting list submitted successfully!',
        'Waiting_list' => $Waiting_list,
    ], 200);
}




    /**
     * Get the job list for the authenticated job seeker with pagination and status filter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function getJobList(Request $request)
    {
        // Authenticate user with 'api' guard
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access.',
            ], 401);
        }


            // Check if the user's active profile is JobSeeker
    if ($user->active_profile !== 'JobSeeker') {
        return response()->json([
            'status' => false,
            'message' => 'You must have an active JobSeeker profile to access this.',
        ], 403);
    }


        // Retrieve the JobSeeker profile
        $jobSeeker = $user->jobSeeker;

        if (!$jobSeeker) {
            return response()->json([
                'status' => false,
                'message' => 'JobSeeker profile not found.',
            ], 404);
        }

        // Get per_page value from request, default to 10
        $perPage = $request->query('per_page', 10);

        // Get status filter from request
        $status = $request->query('status'); // Example values: 'pending', 'approved', 'rejected'

        // Query applied jobs for the authenticated job seeker
        $query = AppliedJob::where('job_seeker_id', $jobSeeker->id)->latest();

        // Apply status filter if provided
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // Paginate results
        $appliedJobs = $query->paginate($perPage);

        return response()->json($appliedJobs, 200);
    }





}
