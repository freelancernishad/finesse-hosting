<?php

namespace App\Http\Controllers\Api\JobSeeker;

use App\Models\AppliedJob;
use App\Models\JobCategory;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\PostJob;
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
    public function join_waiting_list(Request $request)
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
        'certificate' => 'nullable|array',

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
        'certificate' => $request->certificate ?? $jobSeeker->certificate,
        'job_type' => 'waiting_list', // Set job_type to 'waiting_list'
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
    public function getWaitingLists(Request $request)
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

    // Query applied jobs for the authenticated job seeker where job_type is 'waiting_list'
    $query = AppliedJob::where('job_seeker_id', $jobSeeker->id)
                       ->where('job_type', 'waiting_list') // Ensure job_type is 'waiting_list'
                       ->latest();

    // Apply status filter if provided
    if (!empty($status)) {
        $query->where('status', $status);
    }

    // Paginate results
    $appliedJobs = $query->paginate($perPage);

    return response()->json($appliedJobs, 200);
}









     /**
     * Apply for a specific posted job.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
public function applyForPostedJob(Request $request)
{
    $user = Auth::guard('api')->user();

    if (!$user) {
        return response()->json(['status' => false, 'message' => 'Unauthorized'], 401);
    }

    if ($user->active_profile !== 'JobSeeker') {
        return response()->json([
            'status' => false,
            'message' => 'You must have an active JobSeeker profile to apply.',
        ], 403);
    }

    $validator = Validator::make($request->all(), [
        'interest_file' => 'nullable|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
        'area' => 'nullable|array',
        'area.*' => 'string|max:255',
        'post_job_id' => 'nullable|exists:post_jobs,id',
        'job_category_id' => 'required_without:post_job_id|nullable|exists:job_categories,id',

        // Fields for user and jobSeeker fallback
        'country' => 'nullable|string|max:255',
        'city' => 'nullable|string|max:255',
        'zip_code' => 'nullable|string|max:20',
        'street_address' => 'nullable|string|max:255',
        'phone' => 'nullable|string|max:20',
        'date_of_birth' => 'nullable|date',

        // Optional applied job fields
        'describe_yourself' => 'nullable|string',
        'resume' => 'nullable|mimes:pdf,doc,docx,jpg,jpeg,png',
        'cover_letter' => 'nullable|string',
        'experience' => 'nullable|string',
        'preferred_contact_method' => 'nullable|in:email,phone',
        'on_call_status' => 'nullable|in:Stand by,On-call',
    ]);

    if ($validator->fails()) {
        return response()->json([
            'status' => false,
            'message' => 'Validation error',
            'errors' => $validator->errors(),
        ], 422);
    }

    $postJobId = $request->post_job_id;



    $jobCategoryId = $request->job_category_id;

    if(!$request->job_category_id){
        $getPostJob = PostJob::find($postJobId);
        $jobCategory = $getPostJob->category;
        $category = $jobCategory;
        $jobCategoryId = JobCategory::where('name', $jobCategory)->first()->id;
    }else{
        $jobCategory = JobCategory::find($jobCategoryId)->name;
        $category = $jobCategory;
    }


    // Prevent duplicate application for same job post
    $alreadyApplied = AppliedJob::where('post_job_id', $postJobId)
        ->where('job_category_id', $jobCategoryId)
        ->where('job_seeker_id', optional($user->jobSeeker)->id)
        ->exists();

    if ($alreadyApplied) {
        return response()->json([
            'status' => false,
            'message' => 'You have already applied for this job post in this category.',
        ], 403);
    }

    // === STEP 1: Ensure User fields are filled (only if null or blank) ===
    $userNeedsUpdate = false;

    if (empty($user->country) && $request->filled('country')) {
        $user->country = $request->country;
        $userNeedsUpdate = true;
    }
    if (empty($user->city) && $request->filled('city')) {
        $user->city = $request->city;
        $userNeedsUpdate = true;
    }
    if (empty($user->zip_code) && $request->filled('zip_code')) {
        $user->zip_code = $request->zip_code;
        $userNeedsUpdate = true;
    }
    if (empty($user->street_address) && $request->filled('street_address')) {
        $user->street_address = $request->street_address;
        $userNeedsUpdate = true;
    }

    if ($userNeedsUpdate) {
        $user->save();
    }

    // === STEP 2: Ensure JobSeeker exists and has necessary data ===
    $jobSeeker = $user->jobSeeker;

    if (!$jobSeeker) {
        $jobSeeker = $user->jobSeeker()->create([
            'phone_number' => $request->phone
        ]);
    } else {
        $jobSeekerNeedsUpdate = false;

        if (empty($jobSeeker->phone_number) && $request->filled('phone')) {
            $jobSeeker->phone_number = $request->phone;
            $jobSeekerNeedsUpdate = true;
        }



        if ($jobSeekerNeedsUpdate) {
            $jobSeeker->save();
        }
    }

    // === STEP 3: Create AppliedJob ===
    $appliedJob = AppliedJob::create([
        'name' => $user->name,
        'phone' => $jobSeeker->phone_number,
        'email' => $user->email,
        'country' => $user->country,
        'city' => $user->city,
        'post_code' => $user->zip_code,
        'address' => $user->street_address,
        'area' => $request->area,
        'post_job_id' => $postJobId,
        'category' => $category,
        'job_category_id' => $jobCategoryId,  // Add job category ID here
        'job_seeker_id' => $jobSeeker->id,

        // Optional fields
        'describe_yourself' => $request->describe_yourself,
        'cover_letter' => $request->cover_letter,
        'experience' => $request->experience,
        'preferred_contact_mehtod' => $request->preferred_contact_method,
        'on_call_status' => $request->on_call_status,
        'job_type' => 'hiring_request_apply', // Set job_type to 'hiring_request_apply'
    ]);

    // Save interest file if present
    if ($request->hasFile('interest_file')) {
        $appliedJob->saveInterestFile($request->file('interest_file'));
    }

    // Save resume file if present
    if ($request->hasFile('resume')) {
        $resumePath = uploadFileToS3($request->file('resume'), 'resumes/' . $jobSeeker->id);
        $appliedJob->resume = $resumePath;
        $appliedJob->save();
    }

    return response()->json([
        'status' => true,
        'message' => 'Application submitted successfully!',
        'job_apply' => $appliedJob,
    ], 200);
}





    /**
     * Get posted job applications with optional post_job_id filter.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
public function getPostedJobApplications(Request $request)
{
    $user = Auth::guard('api')->user();

    if (!$user) {
        return response()->json(['status' => false, 'message' => 'Unauthorized access.'], 401);
    }

    if ($user->active_profile !== 'JobSeeker') {
        return response()->json([
            'status' => false,
            'message' => 'You must have an active JobSeeker profile to access this.',
        ], 403);
    }

    $jobSeeker = $user->jobSeeker;

    if (!$jobSeeker) {
        return response()->json(['status' => false, 'message' => 'JobSeeker profile not found.'], 404);
    }

    $perPage = $request->query('per_page', 10);
    $postJobId = $request->query('post_job_id');

    $query = AppliedJob::with('postJob')->where('job_seeker_id', $jobSeeker->id)
                       ->where('job_type', 'hiring_request_apply') // Filter by job_type 'hiring_request_apply'
                       ->whereNotNull('post_job_id') // Ensure post_job_id is not null
                       ->where('post_job_id', '!=', '') // Ensure post_job_id is not an empty string
                       ->latest();

    if (!empty($postJobId)) {
        $query->where('post_job_id', $postJobId);
    }

    $applications = $query->paginate($perPage);

    return response()->json($applications, 200);
}




}
