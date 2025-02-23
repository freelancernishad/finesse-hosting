<?php

namespace App\Http\Controllers\Api\Admin\JobSeeker;


use App\Models\JobSeeker;
use App\Models\RequestQuote;
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
    public function index()
    {
        $jobSeekers = JobSeeker::all();

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
        $jobSeeker = JobSeeker::findOrFail($id);

        return response()->json([
            'job_seeker' => $jobSeeker
        ], 200);
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
     * Get JobSeekers by RequestQuote ID
     *
     * @param  int  $requestQuoteId
     * @return \Illuminate\Http\Response
     */
    public function getJobSeekersByRequestQuote($requestQuoteId)
    {
        // Find the RequestQuote
        $requestQuote = RequestQuote::findOrFail($requestQuoteId);

        // Get all associated JobSeekers
        $jobSeekers = $requestQuote->jobSeekers;

        return response()->json( $jobSeekers, 200);
    }
}
