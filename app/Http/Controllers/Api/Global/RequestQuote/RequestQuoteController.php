<?php

namespace App\Http\Controllers\Api\Global\RequestQuote;

use App\Models\User;
use Illuminate\Support\Str;
use App\Models\RequestQuote;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RequestQuoteController extends Controller
{
    public function store(Request $request)
    {
        // Define validation rules
        $rules = [
            'name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:15',
            'how_did_you_hear' => 'nullable|string|max:255',
            'event_date' => 'nullable|date',
            'start_time' => 'nullable|date_format:H:i',
            'categories' => 'nullable|array', // Ensure it's an array
            'categories.*.id' => 'nullable|exists:job_categories,id', // Ensure each category id exists in job_categories
            'categories.*.name' => 'nullable|string|max:255', // Ensure name is provided and is a string
            'categories.*.count' => 'nullable|integer|min:1', // Ensure count is provided and is a valid integer
            'number_of_guests' => 'nullable|integer|min:1',
            'event_location' => 'nullable|string|max:255',
            'event_details' => 'nullable|string',
            'area' => 'nullable|string|max:255', // Validation for area
            'type_of_hiring' => 'nullable|string|max:255', // Validation for type_of_hiring
        ];

        // Create a validator instance
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422); // Return 422 Unprocessable Entity status
        }

        // Check if user exists by email
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Create new user with a random password
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make(Str::random(12)), // Random password
            ]);
        }

        // Create quote and link to user
        $quote = RequestQuote::create([
            'user_id' => $user->id,
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'how_did_you_hear' => $request->how_did_you_hear,
            'event_date' => $request->event_date,
            'start_time' => $request->start_time,
            'categories' => json_encode($request->categories), // Store the categories as a JSON string
            'number_of_guests' => $request->number_of_guests,
            'event_location' => $request->event_location,
            'event_details' => $request->event_details,
            'area' => $request->area, // Added area field
            'type_of_hiring' => $request->type_of_hiring, // Added type_of_hiring field
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
