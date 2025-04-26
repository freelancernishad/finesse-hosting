<?php

namespace App\Http\Controllers\Api\Global\RequestQuote;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\RequestQuote;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class RequestQuoteController extends Controller
{
    public function store(Request $request)
    {
        // Define validation rules for new fields
        $rules = [
            'selected_industry' => 'nullable|string|max:255',
            'selected_categories' => 'nullable|array',
            'selected_categories.*.id' => 'nullable',
            'selected_categories.*.name' => 'nullable|string|max:255',
            'selected_categories.*.parent_id' => 'nullable|integer',
            'selected_categories.*.number_of_employee' => 'nullable|integer|min:1',
            'job_descriptions' => 'nullable|array',
            'job_descriptions.*.title' => 'nullable|string|max:255',
            'job_descriptions.*.description' => 'nullable|string',
            'is_use_my_current_company_location' => 'nullable|boolean',
            'job_location' => 'nullable|array',
            'job_location.job_location_country' => 'nullable|string|max:255',
            'job_location.job_location_state' => 'nullable|string|max:255',
            'job_location.job_location_zipcode' => 'nullable|string|max:20',
            'job_location.job_location_full_address' => 'nullable|string|max:255',
            'years_of_experience' => 'nullable|string|max:255',
            'reason_for_hire' => 'nullable|string|max:255',
            'note' => 'nullable|string',
            'hire_for_my_current_company' => 'nullable|boolean',
            'company_info' => 'nullable|array',
            'company_info.name' => 'nullable|string|max:255',
            'company_info.size' => 'nullable|string|max:255',
            'company_info.industry' => 'nullable|string|max:255',
            'company_info.description' => 'nullable|string',
            'total_hours' => 'nullable|integer',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ];

        // Validate request
        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        // Determine job_location based on is_use_my_current_company_location flag
        $job_location = $request->job_location;

        if ($request->is_use_my_current_company_location) {
            // Get location from authenticated user
            $job_location = [
                'job_location_country' => $user->country,
                'job_location_state' => $user->state,
                'job_location_zipcode' => $user->zip_code,
                'job_location_full_address' => $user->full_address,
            ];
        }

        // Determine company_info based on hire_for_my_current_company flag
        $company_info = $request->company_info;

        if ($request->hire_for_my_current_company) {
            // Fetch employer info if hire_for_my_current_company is true
            $employer = $user->employer; // Assuming the employer relationship is defined

            if ($employer) {
                $company_info = [
                    'name' => $employer->company_name,
                    'size' => $employer->company_size,
                    'industry' => $employer->industry,
                    'description' => $employer->company_description,
                ];
            }
        }

        // Create new RequestQuote linked to authenticated user
        $quote = RequestQuote::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,

            'selected_industry' => $request->selected_industry,
            'selected_categories' => $request->selected_categories,
            'job_descriptions' => $request->job_descriptions,
            'is_use_my_current_company_location' => $request->is_use_my_current_company_location,
            'job_location' => $job_location,
            'years_of_experience' => $request->years_of_experience,
            'reason_for_hire' => $request->reason_for_hire,
            'note' => $request->note,
            'hire_for_my_current_company' => $request->hire_for_my_current_company,
            'company_info' => $company_info,
            'total_hours' => $request->total_hours,
            'start_date' => $request->start_date ? Carbon::parse($request->start_date)->format('Y-m-d H:i:s') : null,
            'end_date' => $request->end_date ? Carbon::parse($request->end_date)->format('Y-m-d H:i:s') : null,
        ]);

        return response()->json([
            'message' => 'Request a Quote submitted successfully!',
            'quote' => $quote,
        ], 201);
    }




    public function getJobSeekersByRequestQuote($requestQuoteId)
    {
        // Validate that the RequestQuote exists
        $requestQuote = RequestQuote::findOrFail($requestQuoteId);

        // Get all JobSeekers associated with the RequestQuote
        $jobSeekers = $requestQuote->jobSeekers;

        // Return the list of JobSeekers
        return response()->json($jobSeekers, 200);
    }
}
