<?php

namespace App\Http\Controllers\Api\Admin\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\AppliedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class JobApplicationController extends Controller
{
    /**
     * Get the list of job applications.
     *
     * @return \Illuminate\Http\Response
     */
    public function getJobApplications(Request $request)
    {
        // Start building the query
        $query = AppliedJob::with('jobSeeker', 'admin');

        // Apply category filter if provided
        if ($request->has('category') && $request->category != '') {
            $query->where('category', 'like', '%' . $request->category . '%');
        }

        // Apply area filter if provided
        if ($request->has('area') && $request->area != '') {
            $query->where('area', 'like', '%' . $request->area . '%');
        }

        // Apply global search if any keyword is provided (search on multiple fields)
        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                      ->orWhere('phone', 'like', '%' . $searchTerm . '%')
                      ->orWhere('email', 'like', '%' . $searchTerm . '%')
                      ->orWhere('country', 'like', '%' . $searchTerm . '%')
                      ->orWhere('city', 'like', '%' . $searchTerm . '%')
                      ->orWhere('address', 'like', '%' . $searchTerm . '%');
            });
        }

        // Pagination (customize per page as needed, here it’s set to 10)
        $perPage = $request->get('per_page', 10); // Default to 10 items per page if not specified
        $jobApplications = $query->paginate($perPage);

        // Return paginated job applications
        return response()->json( $jobApplications, 200);
    }




        /**
     * Admin update job application details.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $jobApplicationId
     * @return \Illuminate\Http\Response
     */
    public function updateJobApplication(Request $request, $jobApplicationId)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'country' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:10',
            'address' => 'nullable|string|max:255',
            'interest_file' => 'nullable|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120', // Optional file upload
            'area' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the job application
        $appliedJob = AppliedJob::findOrFail($jobApplicationId);

        // Update the job application with new data
        $appliedJob->name = $request->name ?? $appliedJob->name;
        $appliedJob->phone = $request->phone ?? $appliedJob->phone;
        $appliedJob->email = $request->email ?? $appliedJob->email;
        $appliedJob->date_of_birth = $request->date_of_birth ?? $appliedJob->date_of_birth;
        $appliedJob->country = $request->country ?? $appliedJob->country;
        $appliedJob->city = $request->city ?? $appliedJob->city;
        $appliedJob->post_code = $request->post_code ?? $appliedJob->post_code;
        $appliedJob->address = $request->address ?? $appliedJob->address;
        $appliedJob->area = $request->area ?? $appliedJob->area;
        $appliedJob->category = $request->category ?? $appliedJob->category;

        // Handle the interest file upload if provided
        if ($request->hasFile('interest_file')) {
            $appliedJob->interest_file = $request->file('interest_file')->store('interest_files');
        }

        // Save the updated job application
        $appliedJob->save();

        return response()->json([
            'status' => true,
            'message' => 'Job application updated successfully!',
            'applied_job' => $appliedJob,
        ], 200);
    }




    /**
     * Admin update job application status.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $jobApplicationId
     * @return \Illuminate\Http\Response
     */
    public function adminUpdateJobApplication(Request $request, $jobApplicationId)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,rejected',
            'review_comments' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find the job application
        $appliedJob = AppliedJob::findOrFail($jobApplicationId);

        // Update the job application status and review comments
        $appliedJob->status = $request->status;
        $appliedJob->review_comments = $request->review_comments;
        $appliedJob->admin_id = Auth::guard('admin')->id();  // Assuming the admin is authenticated

        // Save the updates
        $appliedJob->save();

        return response()->json([
            'status' => true,
            'message' => 'Job application updated successfully!',
            'applied_job' => $appliedJob,
        ], 200);
    }

    /**
     * Get details of a specific job application.
     *
     * @param  int  $jobApplicationId
     * @return \Illuminate\Http\Response
     */
    public function getJobApplicationDetails($jobApplicationId)
    {
        // Find job application with related data
        $appliedJob = AppliedJob::with('jobSeeker', 'admin')->findOrFail($jobApplicationId);

        return response()->json([
            'status' => true,
            'message' => 'Job application details retrieved successfully!',
            'applied_job' => $appliedJob,
        ], 200);
    }

    /**
     * Delete a job application (admin only).
     *
     * @param  int  $jobApplicationId
     * @return \Illuminate\Http\Response
     */
    public function deleteJobApplication($jobApplicationId)
    {
        // Find the job application
        $appliedJob = AppliedJob::findOrFail($jobApplicationId);

        // Delete the job application
        $appliedJob->delete();

        return response()->json([
            'status' => true,
            'message' => 'Job application deleted successfully!',
        ], 200);
    }
}
