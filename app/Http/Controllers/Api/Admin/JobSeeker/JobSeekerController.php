<?php

namespace App\Http\Controllers\Api\Admin\JobSeeker;


use App\Models\JobSeeker;
use App\Models\HiringRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;


class JobSeekerController extends Controller
{
    /**
     * Display a listing of JobSeekers.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {
        // Validate per_page input, set default to 10 if not provided
        $request->validate([
           'per_page' => 'nullable|integer|min:1', // Ensure per_page is an integer and at least 1
        ]);

        $perPage = $request->input('per_page', 10);

        // Retrieve paginated job seekers with the latest ones first
        $jobSeekers = JobSeeker::with(['HiringRequests' => function ($query) {
           $query->where('status', 'assigned'); // Only fetch assigned quotes
        }, 'user'])->orderBy('created_at', 'desc')->paginate($perPage);

        // Transform response to include assigned quote details
        $jobSeekers->setCollection(collect($jobSeekers->items())->transform(function ($jobSeeker) {
           return [
              'id' => $jobSeeker->id,
              'name' => optional($jobSeeker->user)->name, // Use optional() to handle null user
              'email' => optional($jobSeeker->user)->email,
              'phone_number' => $jobSeeker->phone_number,
              'location' => $jobSeeker->location,
              'join_date' => $jobSeeker->join_date,
              'average_review_rating' => $jobSeeker->average_review_rating,
              'total_reviews' => $jobSeeker->total_reviews,
              'user' => $jobSeeker->user, // Include user details

              'is_assigned_quote' => $jobSeeker->HiringRequests->isNotEmpty(), // Check if assigned any quote
              'assigned_quotes' => $jobSeeker->HiringRequests->map(function ($quote) {
                 return $quote;
              }),
           ];
        }));

        return response()->json($jobSeekers, 200);
    }




    /**
     * Store a newly created JobSeeker.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:job_seekers,email',
            'phone_number' => 'required|string',
            'password' => 'required|string|min:6',
            'location' => 'nullable|string',
            'join_date' => 'nullable|date',
            'resume' => 'nullable|file|mimes:pdf,doc,docx',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Create the JobSeeker
        $jobSeeker = JobSeeker::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'password' => bcrypt($request->password),
            'location' => $request->location,
            'join_date' => $request->join_date,
        ]);

        // Handle file uploads
        if ($request->hasFile('resume')) {
            $jobSeeker->saveResume($request->file('resume'));
        }

        if ($request->hasFile('profile_picture')) {
            $jobSeeker->saveProfilePicture($request->file('profile_picture'));
        }

        return response()->json([
            'message' => 'JobSeeker created successfully.',
            'job_seeker' => $jobSeeker
        ], 201);
    }

    /**
     * Display the specified JobSeeker.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
{
    $jobSeeker = JobSeeker::with(['HiringRequests' => function ($query) {
        $query->whereIn('status', ['assigned', 'completed']); // Fetch both assigned and completed quotes
    },'user'])->findOrFail($id);

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
        'user' => $jobSeeker->user,
        'is_assigned_quote' => $jobSeeker->HiringRequests->where('status', 'assigned')->isNotEmpty(),
        'assigned_quotes' => $jobSeeker->HiringRequests->where('status', 'assigned')->values(),
        'completed_quotes' => $jobSeeker->HiringRequests->where('status', 'completed')->values(),
    ]);
}



    /**
     * Update the specified JobSeeker.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $jobSeeker = JobSeeker::findOrFail($id);

        // Validate request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:job_seekers,email,' . $id,
            'phone_number' => 'required|string',
            'location' => 'nullable|string',
            'join_date' => 'nullable|date',
            'resume' => 'nullable|file|mimes:pdf,doc,docx',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // Update the JobSeeker
        $jobSeeker->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'location' => $request->location,
            'join_date' => $request->join_date,
        ]);

        // Handle file uploads
        if ($request->hasFile('resume')) {
            $jobSeeker->saveResume($request->file('resume'));
        }

        if ($request->hasFile('profile_picture')) {
            $jobSeeker->saveProfilePicture($request->file('profile_picture'));
        }

        return response()->json([
            'message' => 'JobSeeker updated successfully.',
            'job_seeker' => $jobSeeker
        ], 200);
    }

    /**
     * Remove the specified JobSeeker.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $jobSeeker = JobSeeker::findOrFail($id);
        $jobSeeker->delete();

        return response()->json([
            'message' => 'JobSeeker deleted successfully.'
        ], 200);
    }

    /**
     * Get JobSeekers by HiringRequest ID
     *
     * @param  int  $HiringRequestId
     * @return \Illuminate\Http\Response
     */
    public function getJobSeekersByHiringRequest($HiringRequestId)
    {
        // Find the HiringRequest
        $HiringRequest = HiringRequest::findOrFail($HiringRequestId);

        // Get all associated JobSeekers
        $jobSeekers = $HiringRequest->jobSeekers;

        return response()->json( $jobSeekers, 200);
    }


    public function updateOnCallStatus(Request $request, $id)
    {
        $jobSeeker = JobSeeker::findOrFail($id);

        // Validate the request
        $request->validate([
            'on_call_status' => 'required|string|in:Stand by,On-call',
        ]);

        // Update the on_call_status
        $jobSeeker->on_call_status = $request->on_call_status;
        $jobSeeker->save();

        return response()->json([
            'message' => 'on_call_status updated successfully.',
            'job_seeker' => $jobSeeker
        ], 200);
    }

}
