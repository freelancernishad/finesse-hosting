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
        // Validate incoming data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:15',
            'email' => 'required|email|max:255',
            'date_of_birth' => 'required|date',
            'country' => 'required|string|max:255',
            'city' => 'required|string|max:255',
            'post_code' => 'required|string|max:10',
            'address' => 'required|string|max:255',
            'interest_file' => 'nullable|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // Optional file upload
            'area' => 'required|string|max:255',
            'job_category_id' => 'required|exists:job_categories,id', // Validate that job_category_id exists
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $jobSeeker = Auth::guard('job_seeker')->user();

        // Check if the job seeker already has an approved application in this category
        $existingApplication = AppliedJob::where('job_seeker_id', $jobSeeker->id)
            ->where('job_category_id', $request->job_category_id)
            ->where('status', 'approved') // Only check approved applications
            ->exists();

        if ($existingApplication) {
            return response()->json([
                'status' => false,
                'message' => 'You have already applied and been approved for this job category.',
            ], 403);
        }

        // Retrieve the JobCategory name using the provided ID
        $jobCategory = JobCategory::find($request->job_category_id);

        // Store the applied job data
        $appliedJob = AppliedJob::create([
            'name' => $request->name,
            'phone' => $request->phone,
            'email' => $request->email,
            'date_of_birth' => $request->date_of_birth,
            'country' => $request->country,
            'city' => $request->city,
            'post_code' => $request->post_code,
            'address' => $request->address,
            'area' => $request->area,
            'category' => $jobCategory->name, // Store the category name separately
            'job_category_id' => $jobCategory->id, // Store the category ID
            'job_seeker_id' => $jobSeeker->id,
        ]);

        // Handle the interest file upload using the saveInterestFile method
        if ($request->hasFile('interest_file')) {
            $appliedJob->saveInterestFile($request->file('interest_file'));
        }

        return response()->json([
            'message' => 'Job application submitted successfully!',
            'applied_job' => $appliedJob,
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
        $jobSeeker = Auth::guard('job_seeker')->user();

        if (!$jobSeeker) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized access.',
            ], 401);
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
