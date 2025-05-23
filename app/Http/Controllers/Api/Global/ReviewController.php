<?php

namespace App\Http\Controllers\Api\Global;

use App\Models\Review;
use App\Models\JobSeeker;
use App\Models\HiringRequest;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Add reviews for multiple JobSeekers for a single HiringRequest.
     *
     * @param Request $request
     * @param int $HiringRequestId
     * @return \Illuminate\Http\Response
     */
    public function addReviewsForHiringRequest(Request $request, $HiringRequestId)
    {
        // Define the validation rules
        $rules = [
            'reviews' => 'required|array',
            'reviews.*.job_seeker_id' => 'required|exists:job_seekers,id',
            'reviews.*.rating' => 'required|integer|min:1|max:5',
            'reviews.*.comment' => 'nullable|string',
        ];

        // Create the validator
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422); // Unprocessable Entity status code
        }

        // Check if the HiringRequest exists
        $HiringRequest = HiringRequest::findOrFail($HiringRequestId);

        // Check if the HiringRequest has any assigned JobSeekers
        if ($HiringRequest->jobSeekers->isEmpty()) {
            return response()->json([
                'message' => 'The HiringRequest has no JobSeekers assigned. Please assign JobSeekers first.',
            ], 400); // Bad Request status code
        }

        // Get the user associated with the HiringRequest
        $user = $HiringRequest->user; // Assuming the relationship is 'user' in the HiringRequest model

        // Loop through the reviews and create each one
        foreach ($request->reviews as $reviewData) {
            // Ensure the JobSeeker is part of the associated JobSeekers for this HiringRequest
            if (!$HiringRequest->jobSeekers->contains($reviewData['job_seeker_id'])) {
                return response()->json([
                    'message' => 'The specified JobSeeker is not assigned to this HiringRequest.',
                ], 400); // Bad Request status code
            }

            // Check if the review already exists (to prevent duplicates)
            $existingReview = Review::where([
                'reviewer_id' => $user->id, // Use the associated user's ID from the HiringRequest
                'job_seeker_id' => $reviewData['job_seeker_id'],
                'request_quote_id' => $HiringRequest->id,
            ])->first();

            if ($existingReview) {
                return response()->json([
                    'message' => 'This review has already been submitted by the same reviewer for this JobSeeker and HiringRequest.',
                ], 400); // Bad Request status code
            }

            // Create the review for the JobSeeker
            Review::create([
                'job_seeker_id' => $reviewData['job_seeker_id'],
                'applied_job_id' => null, // If needed, add an applied job reference
                'reviewer_id' => $user->id, // Use the associated user's ID from the HiringRequest
                'reviewer_name' => $user->name, // User's name
                'reviewer_email' => $user->email, // User's email from the HiringRequest
                'reviewer_phone' => $user->phone, // User's phone (if exists)
                'rating' => $reviewData['rating'],
                'comment' => $reviewData['comment'],
                'title' => $reviewData['title'] ?? 'Review Title', // Optional title
                'reviewer_type' => 'admin', // Set this as per your requirement
                'request_quote_id' => $HiringRequest->id, // Associate with HiringRequest
            ]);
        }

        return response()->json([
            'message' => 'Reviews added successfully.',
        ], 200);
    }


    /**
     * Add review for an individual JobSeeker.
     *
     * @param Request $request
     * @param int $jobSeekerId
     * @return \Illuminate\Http\Response
     */
    public function addReviewForJobSeeker(Request $request, $jobSeekerId)
    {
        // Define the validation rules
        $rules = [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string',
            'title' => 'nullable|string',
            'request_quote_id' => 'required|exists:hiring_requests,id',  // Ensure request_quote_id is passed and exists in the database
        ];

        // Create the validator
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422); // Unprocessable Entity status code
        }

        // Find the JobSeeker
        $jobSeeker = JobSeeker::findOrFail($jobSeekerId);

        // Check if the HiringRequest exists and retrieve the associated user
        $HiringRequest = HiringRequest::findOrFail($request->request_quote_id); // Fetch the HiringRequest based on the provided ID
        $user = $HiringRequest->user; // Get the user related to the request quote

        // Check if the JobSeeker is assigned to this HiringRequest
        if (!$HiringRequest->jobSeekers->contains($jobSeeker->id)) {
            return response()->json([
                'message' => 'The JobSeeker is not assigned to this HiringRequest.',
            ], 400); // Bad Request status code
        }

        // Create the review for the JobSeeker
        Review::create([
            'job_seeker_id' => $jobSeeker->id,
            'applied_job_id' => null, // If needed, add an applied job reference
            'reviewer_id' => $user->id, // Use the associated user's ID from the HiringRequest
            'reviewer_name' => $user->name, // User's name
            'reviewer_email' => $user->email, // User's email from the HiringRequest
            'reviewer_phone' => $user->phone, // User's phone (if exists)
            'rating' => $request->rating,
            'comment' => $request->comment,
            'title' => $request->title ?? 'Review Title', // Optional title
            'reviewer_type' => 'admin', // Set this as per your requirement
            'request_quote_id' => $HiringRequest->id, // Associate with the given HiringRequest ID
        ]);

        return response()->json([
            'message' => 'Review added successfully.',
        ], 200);
    }


    public function getJobSeekersByHiringRequest($HiringRequestId)
    {
        // Validate that the HiringRequest exists
        $HiringRequest = HiringRequest::findOrFail($HiringRequestId);

        // Get all JobSeekers associated with the HiringRequest
        $jobSeekers = $HiringRequest->jobSeekers;

        // Return the list of JobSeekers
        return response()->json([
            'job_seekers' => $jobSeekers,
        ], 200);
    }


      /**
     * Get the latest reviews for the authenticated job seeker.
     */
    public function getMyReviews(Request $request)
    {
        // Authenticate with 'api' guard
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

        // Get the related JobSeeker profile
        $jobSeeker = $user->jobSeeker;

        if (!$jobSeeker) {
            return response()->json(['status' => false, 'message' => 'No JobSeeker profile found'], 404);
        }

        // Dynamic per page
        $perPage = $request->query('per_page', 10);

        // Use relationship (if you have reviews relation defined)
        $reviews = $jobSeeker->reviews()->latest()->paginate($perPage);

        return response()->json([
            'status' => true,
            'message' => 'Reviews fetched successfully',
            'data' => $reviews,
        ], 200);
    }




}
