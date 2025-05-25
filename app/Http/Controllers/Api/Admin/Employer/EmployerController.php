<?php
namespace App\Http\Controllers\Api\Admin\Employer;

use App\Models\Employer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;

class EmployerController extends Controller
{
    // List Employers with optional pagination
    public function index(Request $request)
    {
        $request->validate([
            'per_page' => 'nullable|integer|min:1',
        ]);

        $perPage = $request->input('per_page', 10);

        $employers = Employer::with('user')->latest()->paginate($perPage);

        return response()->json($employers, 200);
    }

    // Show a specific Employer
    public function show($id)
    {
        $employer = Employer::with(['user', 'hiringRequests'])->findOrFail($id);

        return response()->json($employer, 200);
    }

    // Store a new Employer
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'company_name' => 'required|string',
            'industry' => 'nullable|string',
            'website' => 'nullable|url',
            'company_size' => 'nullable|string',
            'business_location' => 'nullable|string',
            'years_in_operation' => 'nullable|integer',
            'company_description' => 'nullable|string',
            'social_links' => 'nullable|json',
            'designation' => 'nullable|string',
            'bio' => 'nullable|string',
            'preferred_contact_time' => 'nullable|string',
            'preferred_contact_via' => 'nullable|string',
            'hired_before' => 'nullable|boolean',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $request->except('profile_picture');

        if ($request->hasFile('profile_picture')) {
            $data['profile_picture'] = $request->file('profile_picture')->store('employer_pictures', 'public');
        }

        $employer = Employer::create($data);

        return response()->json([
            'message' => 'Employer created successfully.',
            'employer' => $employer
        ], 201);
    }

    // Update an existing Employer
    public function update(Request $request, $id)
    {
        $employer = Employer::findOrFail($id);

        $validator = Validator::make($request->all(), [
            // User-related fields
            'name' => 'sometimes|required|string',
            'country' => 'nullable|string',
            'state' => 'nullable|string',
            'city' => 'nullable|string',
            'region' => 'nullable|string',
            'street_address' => 'nullable|string',
            'zip_code' => 'nullable|string',

            // Employer-related fields
            'company_name' => 'sometimes|required|string',
            'industry' => 'nullable|string',
            'website' => 'nullable|url',
            'company_size' => 'nullable|string',
            'business_location' => 'nullable|string',
            'years_in_operation' => 'nullable|integer',
            'company_description' => 'nullable|string',
            'social_links' => 'nullable|string', // Changed from json to string for comma-separated
            'designation' => 'nullable|string',
            'bio' => 'nullable|string',
            'preferred_contact_time' => 'nullable|string',
            'preferred_contact_via' => 'nullable|string',
            'hired_before' => 'nullable|boolean',
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Separate user and employer data
        $userData = $request->only(['name', 'country', 'state', 'city', 'region', 'street_address', 'zip_code']);
        $employerData = $request->except(['name', 'country', 'state', 'city', 'region', 'street_address', 'zip_code', 'profile_picture']);

        // Handle profile picture
        if ($request->hasFile('profile_picture')) {
            $employerData['profile_picture'] = $request->file('profile_picture')->store('employer_pictures', 'public');
        }

        // Update employer
        $employer->update($employerData);

        // Update associated user
        if ($employer->user) {
            $employer->user->update($userData);
        }

        return response()->json([
            'message' => 'Employer profile updated successfully.',
            'employer' => $employer->load('user')
        ]);
    }


    // Delete an Employer
    public function destroy($id)
    {
        $employer = Employer::findOrFail($id);
        $employer->delete();

        return response()->json([
            'message' => 'Employer deleted successfully.'
        ]);
    }
}
