<?php

namespace App\Http\Controllers\Api\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\AppliedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

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
            'category' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $jobSeeker = Auth::guard('job_seeker')->user();
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
            'category' => $request->category,
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
