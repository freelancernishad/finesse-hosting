<?php

namespace App\Http\Controllers\Api\Admin\JobSeeker;

use App\Models\JobSeeker;
use App\Models\RequestQuote;
use Illuminate\Http\Request;
use App\Mail\ReviewRequestMail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;

class JobSeekerRequestQuoteController extends Controller
{
    // Get all RequestQuotes with related JobSeekers
    public function index()
    {
        $requestQuotes = RequestQuote::with('jobSeekers')->get();

        return response()->json($requestQuotes);
    }

    // Show a specific RequestQuote with JobSeekers
    public function show($id)
    {
        $requestQuote = RequestQuote::with('jobSeekers')->find($id);

        if (!$requestQuote) {
            return response()->json(['message' => 'RequestQuote not found'], 404);
        }

        return response()->json($requestQuote);
    }

    // Assign JobSeekers to a RequestQuote
    public function assignJobSeekers(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'job_seeker_ids' => 'required|array',
            'job_seeker_ids.*' => 'exists:job_seekers,id', // Ensure all JobSeeker IDs exist
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $requestQuote = RequestQuote::find($id);

        if (!$requestQuote) {
            return response()->json(['message' => 'RequestQuote not found'], 404);
        }

        // Assign JobSeekers to the RequestQuote
        $requestQuote->assignJobSeekers($request->job_seeker_ids);

        return response()->json(['message' => 'JobSeekers assigned successfully!', 'request_quote' => $requestQuote]);
    }

    // Update status and assign JobSeekers if status is 'assigned'
    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:assigned,completed,canceled', // Only valid statuses
            'job_seeker_ids' => 'required_if:status,assigned|array', // Only require job_seeker_ids if status is 'assigned'
            'job_seeker_ids.*' => 'exists:job_seekers,id', // Ensure all JobSeeker IDs exist
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $requestQuote = RequestQuote::find($id);

        if (!$requestQuote) {
            return response()->json(['message' => 'RequestQuote not found'], 404);
        }

        // If status is 'assigned', assign JobSeekers
        if ($request->status == 'assigned') {
            $requestQuote->assignJobSeekers($request->job_seeker_ids);
        }

        // Update the status of the RequestQuote
        $requestQuote->status = $request->status;
        $requestQuote->save();

        // If status is 'completed', send an email for review
        if ($request->status == 'completed') {
            try {
                Mail::to($requestQuote->email)->send(new ReviewRequestMail($requestQuote));
                Log::info('Email sent successfully to: ' . $requestQuote->email);
            } catch (\Exception $e) {
                Log::error('Failed to send email: ' . $e->getMessage());
            }
        }
        return response()->json(['message' => 'RequestQuote status updated successfully!', 'request_quote' => $requestQuote]);
    }


    public function getAvailableJobSeekers(Request $request)
{
    // Validate request
    $request->validate([
        'request_quote_id' => 'nullable|exists:request_quotes,id', // Nullable to allow missing request_quote_id
        'per_page' => 'nullable|integer|min:1', // Validate per_page to ensure it's a positive integer
    ]);

    // Default per_page to 10 if not provided
    $perPage = $request->input('per_page', 10);

    // Initialize the query for job seekers
    $query = JobSeeker::query();

    // If request_quote_id is provided, filter based on it
    if ($request->has('request_quote_id')) {
        // Get the RequestQuote
        $requestQuote = RequestQuote::findOrFail($request->request_quote_id);

        // Extract requested category names
        $requestedCategoryNames = collect($requestQuote->categories)->pluck('name')->toArray();

        // Get job seekers assigned to active RequestQuotes
        $assignedJobSeekerIds = \DB::table('job_seeker_request_quote')
            ->join('request_quotes', 'job_seeker_request_quote.request_quote_id', '=', 'request_quotes.id')
            ->where('request_quotes.status', '!=', 'completed') // Exclude completed ones
            ->pluck('job_seeker_id')
            ->toArray();

        // If no job seekers are assigned, consider the array empty and return all unassigned job seekers
        if (empty($assignedJobSeekerIds)) {
            $assignedJobSeekerIds = [0]; // Ensures no job seekers are excluded
        }

        // Filter the unassigned job seekers who have at least one matching applied job category
        $query->whereNotIn('id', $assignedJobSeekerIds)
            ->whereHas('appliedJobs', function ($query) use ($requestedCategoryNames) {
                $query->whereIn('category', $requestedCategoryNames);
            });
    } else {
        // If no request_quote_id is provided, return job seekers who have matching categories
        // Job seekers who have applied jobs in matching categories
        $query->whereHas('appliedJobs', function ($query) {
            // Apply your own logic here if necessary, for example, filtering by category
            // For now, we'll match all categories by default
            $query->whereNotNull('category');
        });
    }

    // Get the filtered/unfiltered job seekers based on the query conditions, with pagination
    $availableJobSeekers = $query->paginate($perPage);

    // Return the response with pagination data
    return response()->json([
        'status' => 'success',
        'data' => $availableJobSeekers,
    ]);
}









}
