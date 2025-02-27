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
        // Get all active RequestQuote category IDs (excluding "completed" ones)
        $requestedCategoryIds = RequestQuote::where('status', '!=', 'completed')
            ->get()
            ->flatMap(fn($quote) => collect($quote->categories)->pluck('id'))
            ->unique()
            ->toArray();

        // Get job seekers currently assigned to active RequestQuotes
        $assignedJobSeekerIds = \DB::table('job_seeker_request_quote')
            ->join('request_quotes', 'job_seeker_request_quote.request_quote_id', '=', 'request_quotes.id')
            ->where('request_quotes.status', '!=', 'completed') // Exclude completed quotes
            ->pluck('job_seeker_id')
            ->toArray();

        // Get unassigned job seekers
        $unassignedJobSeekers = JobSeeker::whereNotIn('id', $assignedJobSeekerIds)->get();

        // Filter job seekers whose applied job categories match any active RequestQuote categories
        $matchingJobSeekers = $unassignedJobSeekers->filter(function ($jobSeeker) use ($requestedCategoryIds) {
            $appliedCategoryIds = $jobSeeker->appliedJobs->pluck('category_id')->toArray();
            return !empty(array_intersect($appliedCategoryIds, $requestedCategoryIds));
        });

        return response()->json([
            'status' => 'success',
            'data' => $matchingJobSeekers->values(),
        ]);
    }


}
