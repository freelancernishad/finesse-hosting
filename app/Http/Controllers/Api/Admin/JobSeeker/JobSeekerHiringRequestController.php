<?php

namespace App\Http\Controllers\Api\Admin\JobSeeker;

use Stripe\Stripe;
use App\Models\PostJob;
use App\Models\JobSeeker;
use App\Models\AppliedJob;
use Illuminate\Http\Request;
use Stripe\Checkout\Session;
use App\Models\HiringRequest;
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
    $perPage = $request->input('per_page', 10);
    $status = $request->input('status');
    $userId = $request->input('user_id');
    $sortBy = $request->input('sort_by', 'created_at');
    $sortOrder = $request->input('sort_order', 'desc');

    $query = HiringRequest::with([
        'jobSeekers' => function ($query) {
            $query->select('job_seekers.id', 'users.name as job_seeker_name', 'job_seekers.member_id')
                ->join('users', 'users.id', '=', 'job_seekers.user_id')
                ->withPivot('hourly_rate', 'total_hours', 'total_amount');
        }
    ]);

    if (!empty($status)) {
        $query->where('status', $status);
    }

    if (auth()->guard('admin')->check()) {
        if (!empty($userId)) {
            $query->where('user_id', $userId);
        }
    } else if (auth()->guard('api')->check()) {
        $user = auth()->guard('api')->user();
        $query->where('user_id', $user->id);
    }

    $query->orderBy($sortBy, $sortOrder);

    $HiringRequests = $query->paginate($perPage);

    // Add matched job seekers to each HiringRequest model instance
    $HiringRequests->getCollection()->transform(function ($hiringRequest) {
        $hiringRequest->matched_job_seekers_count  = $hiringRequest->matchedJobSeekers();
        return $hiringRequest;
    });

    return response()->json($HiringRequests);
}








    // Show a specific HiringRequest with JobSeekers
public function show($id)
{
    $hiringRequest = HiringRequest::with('jobSeekers.user', 'user.employer')->find($id);

    if (!$hiringRequest) {
        return response()->json(['message' => 'Hiring request not found'], 404);
    }

    $user = $hiringRequest->user;
    $employer = $user->employer ?? null;

    return response()->json([
            'id' => $hiringRequest->id,
            'selected_industry' => $hiringRequest->selected_industry,
            'selected_categories' => $hiringRequest->selected_categories,
            'job_descriptions' => $hiringRequest->job_descriptions,
            'is_use_my_current_company_location' => $hiringRequest->is_use_my_current_company_location,
            'job_location' => $hiringRequest->job_location,
            'years_of_experience' => $hiringRequest->years_of_experience,
            'reason_for_hire' => $hiringRequest->reason_for_hire,
            'note' => $hiringRequest->note,
            'hire_for_my_current_company' => $hiringRequest->hire_for_my_current_company,
            'company_info' => $hiringRequest->company_info,
            'expected_joining_date' => $hiringRequest->expected_joining_date,
            'type_of_hiring' => $hiringRequest->type_of_hiring,
            'model_name' => $hiringRequest->model_name,
            'min_yearly_salary' => $hiringRequest->min_yearly_salary,
            'mix_yearly_salary' => $hiringRequest->mix_yearly_salary,
            'total_hours' => $hiringRequest->total_hours,
            'start_date' => $hiringRequest->start_date,
            'end_date' => $hiringRequest->end_date,
            'status' => $hiringRequest->status,
            'created_at' => $hiringRequest->created_at,
            'updated_at' => $hiringRequest->updated_at,
            'requested_user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'active_profile' => $user->active_profile,
                'profile_picture' => $user->profile_picture,
                'country' => $user->country,
                'state' => $user->state,
                'city' => $user->city,
                'region' => $user->region,
                'street_address' => $user->street_address,
                'zip_code' => $user->zip_code,
                'full_address' => $user->full_address,
                'profile' => $employer, // You may format this as needed too
            ],
            'assigned_job_seekers' => $hiringRequest->jobSeekers->map(function ($js) {
                return [
                    'id' => $js->id,
                    'name' => $js->user->name ?? null,
                    'email' => $js->user->email ?? null,
                    'member_id' => $js->member_id,
                    'average_review_rating' => $js->average_review_rating,
                    'review_summary' => $js->review_summary,
                    'approved_job_roles' => $js->approved_job_roles,
                    'pivot' => $js->pivot,
                    'applied_jobs' => $js->applied_jobs,
                    'user' => [
                        'id' => $js->user->id ?? null,
                        'name' => $js->user->name ?? null,
                        'email' => $js->user->email ?? null,
                        'active_profile' => $js->user->active_profile ?? null,
                        'profile_picture' => $js->user->profile_picture ?? null,
                        'country' => $js->user->country ?? null,
                        'state' => $js->user->state ?? null,
                        'city' => $js->user->city ?? null,
                        'region' => $js->user->region ?? null,
                        'street_address' => $js->user->street_address ?? null,
                        'zip_code' => $js->user->zip_code ?? null,
                        'full_address' => $js->user->full_address ?? null,
                    ]
                ];
            }),

    ]);
}



    // Assign JobSeekers to a HiringRequest
    public function assignJobSeekers(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'job_seekers' => 'required|array',
            'job_seekers.*.id' => 'required|exists:job_seekers,id',
            'job_seekers.*.hourly_rate' => 'required|numeric|min:0',
            'job_seekers.*.total_hours' => 'required|integer|min:0',
            'job_seekers.*.total_amount' => 'required|numeric|min:0',
            'job_seekers.*.job_application_id' => 'nullable|exists:applied_jobs,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $hiringRequest = HiringRequest::find($id);

        if (!$hiringRequest) {
            return response()->json(['message' => 'HiringRequest not found'], 404);
        }

        // Prepare job seeker data for sync
        $jobSeekerData = [];
        foreach ($request->job_seekers as $jobSeeker) {
            $jobSeekerData[$jobSeeker['id']] = [
                'hourly_rate' => $jobSeeker['hourly_rate'],
                'total_hours' => $jobSeeker['total_hours'],
                'total_amount' => $jobSeeker['total_amount'],
            ];

            // Update AppliedJob status to 'approved'
            if (isset($jobSeeker['job_application_id'])) {
                $appliedJob = AppliedJob::find($jobSeeker['job_application_id']);
                if ($appliedJob) {
                    $appliedJob->status = 'approved';
                    $appliedJob->save();
                }
            }
        }

        // Sync the job seekers with pivot data
        $hiringRequest->jobSeekers()->sync($jobSeekerData);

        // Update HiringRequest status
        $hiringRequest->status = 'assigned';
        $hiringRequest->save();

        // Update PostJob status if it exists for this HiringRequest
        $postJob = PostJob::where('hiring_request_id', $hiringRequest->id)->first();
        if ($postJob) {
            $postJob->status = 'assigned';
            $postJob->save();
        }

        return response()->json([
            'message' => 'JobSeekers assigned and applications approved successfully!',
            'request_quote' => $hiringRequest
        ]);
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
                ->whereHas('approvedJobCategories', function ($q) use ($requestedCategoryNames) {
                    $q->whereIn('category', $requestedCategoryNames);
                });
        } else {
            $query->whereHas('approvedJobCategories', function ($q) {
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
