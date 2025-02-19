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



}
