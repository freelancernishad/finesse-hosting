<?php

namespace App\Http\Controllers\Api\Global;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\JobSeeker;
use App\Models\RequestQuote;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    /**
     * Add reviews for multiple JobSeekers for a single RequestQuote.
     *
     * @param Request $request
     * @param int $requestQuoteId
     * @return \Illuminate\Http\Response
     */
    public function addReviewsForRequestQuote(Request $request, $requestQuoteId)
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

        // Check if the RequestQuote exists
        $requestQuote = RequestQuote::findOrFail($requestQuoteId);

        // Check if the RequestQuote has any assigned JobSeekers
        if ($requestQuote->jobSeekers->isEmpty()) {
            return response()->json([
                'message' => 'The RequestQuote has no JobSeekers assigned. Please assign JobSeekers first.',
            ], 400); // Bad Request status code
        }

        // Get the user associated with the RequestQuote
        $user = $requestQuote->user; // Assuming the relationship is 'user' in the RequestQuote model

        // Loop through the reviews and create each one
        foreach ($request->reviews as $reviewData) {
            // Ensure the JobSeeker is part of the associated JobSeekers for this RequestQuote
            if (!$requestQuote->jobSeekers->contains($reviewData['job_seeker_id'])) {
                return response()->json([
                    'message' => 'The specified JobSeeker is not assigned to this RequestQuote.',
                ], 400); // Bad Request status code
            }

            // Check if the review already exists (to prevent duplicates)
            $existingReview = Review::where([
                'reviewer_id' => $user->id, // Use the associated user's ID from the RequestQuote
                'job_seeker_id' => $reviewData['job_seeker_id'],
                'request_quote_id' => $requestQuote->id,
            ])->first();

            if ($existingReview) {
                return response()->json([
                    'message' => 'This review has already been submitted by the same reviewer for this JobSeeker and RequestQuote.',
                ], 400); // Bad Request status code
            }

            // Create the review for the JobSeeker
            Review::create([
                'job_seeker_id' => $reviewData['job_seeker_id'],
                'applied_job_id' => null, // If needed, add an applied job reference
                'reviewer_id' => $user->id, // Use the associated user's ID from the RequestQuote
                'reviewer_name' => $user->name, // User's name
                'reviewer_email' => $user->email, // User's email from the RequestQuote
                'reviewer_phone' => $user->phone, // User's phone (if exists)
                'rating' => $reviewData['rating'],
                'comment' => $reviewData['comment'],
                'title' => $reviewData['title'] ?? 'Review Title', // Optional title
                'reviewer_type' => 'admin', // Set this as per your requirement
                'request_quote_id' => $requestQuote->id, // Associate with RequestQuote
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
            'request_quote_id' => 'required|exists:request_quotes,id',  // Ensure request_quote_id is passed and exists in the database
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

        // Check if the RequestQuote exists and retrieve the associated user
        $requestQuote = RequestQuote::findOrFail($request->request_quote_id); // Fetch the RequestQuote based on the provided ID
        $user = $requestQuote->user; // Get the user related to the request quote

        // Check if the JobSeeker is assigned to this RequestQuote
        if (!$requestQuote->jobSeekers->contains($jobSeeker->id)) {
            return response()->json([
                'message' => 'The JobSeeker is not assigned to this RequestQuote.',
            ], 400); // Bad Request status code
        }

        // Create the review for the JobSeeker
        Review::create([
            'job_seeker_id' => $jobSeeker->id,
            'applied_job_id' => null, // If needed, add an applied job reference
            'reviewer_id' => $user->id, // Use the associated user's ID from the RequestQuote
            'reviewer_name' => $user->name, // User's name
            'reviewer_email' => $user->email, // User's email from the RequestQuote
            'reviewer_phone' => $user->phone, // User's phone (if exists)
            'rating' => $request->rating,
            'comment' => $request->comment,
            'title' => $request->title ?? 'Review Title', // Optional title
            'reviewer_type' => 'admin', // Set this as per your requirement
            'request_quote_id' => $requestQuote->id, // Associate with the given RequestQuote ID
        ]);

        return response()->json([
            'message' => 'Review added successfully.',
        ], 200);
    }


    public function getJobSeekersByRequestQuote($requestQuoteId)
    {
        // Validate that the RequestQuote exists
        $requestQuote = RequestQuote::findOrFail($requestQuoteId);

        // Get all JobSeekers associated with the RequestQuote
        $jobSeekers = $requestQuote->jobSeekers;

        // Return the list of JobSeekers
        return response()->json([
            'job_seekers' => $jobSeekers,
        ], 200);
    }



}
