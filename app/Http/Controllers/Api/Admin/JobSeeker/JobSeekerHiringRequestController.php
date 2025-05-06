<?php

namespace App\Http\Controllers\Api\Admin\JobSeeker;

use Stripe\Stripe;
use App\Models\JobSeeker;
use App\Models\HiringRequest;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Mail\ReviewRequestMail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use App\Mail\HiringRequestPaymentMail;
use Illuminate\Support\Facades\Validator;

class JobSeekerHiringRequestController extends Controller
{
    // Get all HiringRequests with related JobSeekers
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10); // Default to 10 per page
        $status = $request->input('status'); // Get status filter from request
        $userId = $request->input('user_id'); // Admin can filter by user_id

        $query = HiringRequest::with([
            'jobSeekers' => function ($query) {
                $query->select('job_seekers.id', 'users.name as job_seeker_name', 'job_seekers.member_id')
                      ->join('users', 'users.id', '=', 'job_seekers.user_id')
                      ->withPivot('hourly_rate', 'total_hours', 'total_amount');
            }
        ]);

        // Apply status filter if provided
        if (!empty($status)) {
            $query->where('status', $status);
        }

        // if (!empty($status)) {
        //     $query->where('status', $status);
        // } else {
        //     $query->whereNotIn('status', ['pending', 'completed']);
        // }

        // 👇 Check Guard and Apply Filters
        if (auth()->guard('admin')->check()) {
            // Admin: can see all, or filter by user_id
            if (!empty($userId)) {
                $query->where('user_id', $userId);
            }
        } else if (auth()->guard('api')->check()) {
            // Normal User: see only his/her own requests
            $user = auth()->guard('api')->user();
            $query->where('user_id', $user->id);
        }

        $HiringRequests = $query->paginate($perPage); // Apply pagination

        return response()->json($HiringRequests);

        // Hide attributes from jobSeekers
        $HiringRequests->getCollection()->each(function ($HiringRequest) {
            $HiringRequest->jobSeekers->each(function ($jobSeeker) {
                $jobSeeker->makeHidden([
                    'average_review_rating',
                    'review_summary',
                    'total_reviews',
                    'approved_job_roles',
                    'last_review'
                ]);
            });
        });

        return response()->json($HiringRequests);
    }








    // Show a specific HiringRequest with JobSeekers
    public function show($id)
    {
        $HiringRequest = HiringRequest::with('jobSeekers')->find($id);


        $HiringRequest->categories = json_decode($HiringRequest->categories);


        if (!$HiringRequest) {
            return response()->json(['message' => 'HiringRequest not found'], 404);
        }

        return response()->json($HiringRequest);
    }

    // Assign JobSeekers to a HiringRequest
    public function assignJobSeekers(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'job_seekers' => 'required|array',
            'job_seekers.*.id' => 'exists:job_seekers,id',
            'job_seekers.*.hourly_rate' => 'required|numeric|min:0',
            'job_seekers.*.total_hours' => 'required|integer|min:0',
            'job_seekers.*.total_amount' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $HiringRequest = HiringRequest::find($id);

        if (!$HiringRequest) {
            return response()->json(['message' => 'HiringRequest not found'], 404);
        }

        // Prepare data for syncing
        $jobSeekerData = [];
        foreach ($request->job_seekers as $jobSeeker) {
            $jobSeekerData[$jobSeeker['id']] = [
                'hourly_rate' => $jobSeeker['hourly_rate'],
                'total_hours' => $jobSeeker['total_hours'],
                'total_amount' => $jobSeeker['total_amount'],
            ];
        }

        // Sync job seekers with pivot data
        $HiringRequest->jobSeekers()->sync($jobSeekerData);

        // Update the status of the HiringRequest
        $HiringRequest->status = 'assigned';
        $HiringRequest->save();

        return response()->json(['message' => 'JobSeekers assigned successfully!', 'request_quote' => $HiringRequest]);
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

        $HiringRequest = HiringRequest::find($id);

        if (!$HiringRequest) {
            return response()->json(['message' => 'HiringRequest not found'], 404);
        }

        // If status is 'assigned', assign JobSeekers
        if ($request->status == 'assigned') {
            $HiringRequest->assignJobSeekers($request->job_seeker_ids);
        }

        // Update the status of the HiringRequest
        $HiringRequest->status = $request->status;
        $HiringRequest->save();

        // If status is 'completed', send an email for review
        if ($request->status == 'completed') {
            try {
                Mail::to($HiringRequest->email)->send(new ReviewRequestMail($HiringRequest));
                Log::info('Email sent successfully to: ' . $HiringRequest->email);
            } catch (\Exception $e) {
                Log::error('Failed to send email: ' . $e->getMessage());
            }
        }
        return response()->json(['message' => 'HiringRequest status updated successfully!', 'request_quote' => $HiringRequest]);
    }


    public function getAvailableJobSeekers(Request $request)
    {
        $request->validate([
            'hiring_request_id' => 'nullable|exists:hiring_requests,id',
            'per_page' => 'nullable|integer|min:1',
        ]);

        $perPage = $request->input('per_page', 10);

        // Eager load user
        $query = JobSeeker::with('user');

        if ($request->filled('hiring_request_id')) {
            $HiringRequest = HiringRequest::findOrFail($request->hiring_request_id);

            $selectedCategories = is_string($HiringRequest->selected_categories)
                ? json_decode($HiringRequest->selected_categories, true)
                : $HiringRequest->selected_categories;

            $requestedCategoryNames = collect($selectedCategories)->pluck('name')->toArray();

            $assignedJobSeekerIds = \DB::table('hiring_request_job_seeker')
                ->join('hiring_requests', 'hiring_request_job_seeker.hiring_request_id', '=', 'hiring_requests.id')
                ->where('hiring_requests.status', '!=', 'completed')
                ->pluck('job_seeker_id')
                ->toArray();

            $query->whereNotIn('id', $assignedJobSeekerIds ?: [0])
                ->whereHas('appliedJobs', function ($q) use ($requestedCategoryNames) {
                    $q->whereIn('category', $requestedCategoryNames);
                });
        } else {
            $query->whereHas('appliedJobs', function ($q) {
                $q->whereNotNull('category');
            });
        }
        $availableJobSeekers = $query->paginate($perPage);

        // Transform each JobSeeker to include all attributes (including appends)
        $availableJobSeekers->getCollection()->transform(function ($jobSeekerModel) {
            $original = $jobSeekerModel->toArray(); // includes appends
            $insertAfterKey = 'id';

            $result = [];
            foreach ($original as $key => $value) {
                $result[$key] = $value;

                if ($key === $insertAfterKey) {
                    $result['name'] = $jobSeekerModel->user->name ?? null;
                    $result['email'] = $jobSeekerModel->user->email ?? null;
                }
            }

            return $result;
        });
        


        return response()->json($availableJobSeekers);
    }






    public function confirmQuote(Request $request, $id)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'area' => 'nullable|string',
            'name' => 'nullable|string',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'how_did_you_hear' => 'nullable|string',
            'event_date' => 'nullable|date',
            'start_time' => 'nullable|string',
            'categories' => 'nullable|array',
            'categories.*.id' => 'nullable|integer',
            'categories.*.name' => 'nullable|string',
            'categories.*.count' => 'nullable|integer',
            'number_of_guests' => 'nullable|integer',
            'event_location' => 'nullable|string',
            'event_details' => 'nullable|string',
            'type_of_hiring' => 'nullable|string',
            'budget' => 'nullable|numeric',
            'success_url' => 'required|url',
            'cancel_url' => 'required|url',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $HiringRequest = HiringRequest::find($id);

        if (!$HiringRequest) {
            return response()->json(['message' => 'HiringRequest not found'], 404);
        }

        // Update HiringRequest details
        $HiringRequest->update([
            'area' => $request->area,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'how_did_you_hear' => $request->how_did_you_hear,
            'event_date' => $request->event_date,
            'start_time' => $request->start_time,
            'number_of_guests' => $request->number_of_guests,
            'event_location' => $request->event_location,
            'event_details' => $request->event_details,
            'type_of_hiring' => $request->type_of_hiring,
            'budget' => $request->budget,
            'status' => 'confirmed',
            'categories' => json_encode($request->categories), // Store categories as JSON
        ]);

        // Stripe Integration - Set your secret key
        Stripe::setApiKey(env('STRIPE_SECRET'));

        // Create a Stripe Checkout Session
        $session = Session::create([
            'payment_method_types' => ['card', 'amazon_pay', 'us_bank_account'],
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'usd',  // You can change to your desired currency
                        'product_data' => [
                            'name' => 'Event Budget: ' . $HiringRequest->name,
                        ],
                        'unit_amount' => $HiringRequest->budget * 100, // Stripe requires the amount in cents
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',


            'success_url' => $request->success_url . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $request->success_url,


        ]);

        // Send email with Stripe payment link to the HiringRequest email
        Mail::to($HiringRequest->email)->send(new HiringRequestPaymentMail($HiringRequest, $session->url));

        return response()->json(['message' => 'HiringRequest confirmed successfully! A payment link has been sent to your email.', 'request_quote' => $HiringRequest]);
    }





}
