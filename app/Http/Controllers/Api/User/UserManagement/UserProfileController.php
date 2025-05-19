<?php

namespace App\Http\Controllers\Api\User\UserManagement;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{



        public function EmployerOverview(Request $request)
        {
            if (Auth::guard('api')->check()) {
                $user = Auth::guard('api')->user();
            } else {
                $user = $request->user();
            }

            $hiringSummary = $user->hiringRequests()
                ->selectRaw("status, COUNT(*) as count")
                ->groupBy('status')
                ->pluck('count', 'status');

            return response()->json([
                'pending'   => $hiringSummary->get('pending', 0),
                'confirmed' => $hiringSummary->get('confirmed', 0),
                'assigned'  => $hiringSummary->get('assigned', 0),
                'completed' => $hiringSummary->get('completed', 0),
                'canceled'  => $hiringSummary->get('canceled', 0),
            ]);
        }




    /**
     * Get the authenticated user's profile.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getProfile()
    {
        $user = Auth::user();

        // Load related profile based on active_profile
        $profile = null;
        if ($user->active_profile === 'JobSeeker') {
            $profile = $user->jobSeeker;
        } elseif ($user->active_profile === 'Employer') {
            $profile = $user->employer;
        }

        return response()->json([
            'user' => [
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
                'profile_completion' => $user->profile_completion,
            ],
            'profile' => $profile,
        ]);
    }


    /**
     * Update the authenticated user's profile.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateProfile(Request $request)
    {
        $user = Auth::user(); // Get authenticated user

        // Validate user fields (name, profile picture, address)
        $basicValidator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|max:2048',

            // Address fields
            'country' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'city' => 'nullable|string|max:100',
            'region' => 'nullable|string|max:100',
            'street_address' => 'nullable|string|max:255',
            'zip_code' => 'nullable|string|max:20',
        ]);

        if ($basicValidator->fails()) {
            return response()->json($basicValidator->errors(), 422);
        }

        // Update user fields if present
        $user->fill($request->only([
            'name',
            'country',
            'state',
            'city',
            'region',
            'street_address',
            'zip_code',
        ]));

        // Handle profile picture upload
        if ($request->hasFile('profile_picture')) {
            try {
                $filePath = $user->saveProfilePicture($request->file('profile_picture'));
                $user->profile_picture = $filePath;
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to upload profile picture: ' . $e->getMessage(),
                ], 500);
            }
        }

        // Optionally auto-generate full_address if you want
        $user->full_address = trim(collect([
            $user->street_address,
            $user->region,
            $user->city,
            $user->state,
            $user->zip_code,
            $user->country
        ])->filter()->implode(', '));

        $user->save();

        // Update role-specific profile (JobSeeker or Employer)
        if ($user->active_profile === 'JobSeeker') {
            return $this->updateJobSeekerProfile($request, $user);
        } elseif ($user->active_profile === 'Employer') {
            return $this->updateEmployerProfile($request, $user);
        }

        // Default fallback if no active profile
        return response()->json([
            'user' => [
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
            ],
            'profile' => null,
        ]);
    }


        protected function updateJobSeekerProfile(Request $request, $user)
        {
            $profile = $user->jobSeeker;

            // If JobSeeker profile doesn't exist, create a new one
            if (!$profile) {
                $profile = $user->jobSeeker()->create([
                    'user_id' => $user->id,
                ]);
            }

            $validator = Validator::make($request->all(), [
                'id_no' => 'nullable|string|max:255',
                'phone_number' => 'nullable|string|max:20',
                'location' => 'nullable|string|max:255',
                // 'post_code' => 'nullable|string|max:20',
                // 'city' => 'nullable|string|max:255',
                // 'country' => 'nullable|string|max:255',
                'description' => 'nullable|string',
                'resume' => 'nullable|file|mimes:pdf,doc,docx|max:2048',
                'profile_picture' => 'nullable|image|max:2048',

                // New JSON/array fields
                'language' => 'nullable|array',
                'skills' => 'nullable|array',
                'certificate' => 'nullable|array',
                'education' => 'nullable|array',
                'employment_history' => 'nullable|array',

                // 👇 Add validation for on_call_status
                'on_call_status' => 'nullable|in:Stand by,On-call',

            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $profile->fill($request->only([
                'id_no',
                'phone_number',
                'location',
                'post_code',
                'city',
                'country',
                'description',

                'language',
                'skills',
                'certificate',
                'education',
                'employment_history',
                'on_call_status', // 👈 Add this line
            ]));


                // Override these fields from $user, NOT from request
            $profile->post_code = $user->zip_code;
            $profile->city = $user->city;
            $profile->country = $user->country;

            if ($request->hasFile('resume')) {
                $resumePath = $request->file('resume')->store('resumes', 'public');
                $profile->resume = $resumePath;
            }


            $profile->save();

            return response()->json([
                'user' => [
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
                ],
                'profile' => $profile,
            ]);
        }



        protected function updateEmployerProfile(Request $request, $user)
        {
            $profile = $user->employer;

            // If Employer profile doesn't exist, create a new one
            if (!$profile) {
                $profile = $user->employer()->create([
                    'user_id' => $user->id,
                ]);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'nullable|string|max:255',
                'profile_picture' => 'nullable|string',
                'company_name' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'website' => 'nullable|string|max:255',
                'company_size' => 'nullable|string|max:50',
                'business_location' => 'nullable|string|max:255',
                'years_in_operation' => 'nullable|string|max:100',
                'company_description' => 'nullable|string',
                'social_links' => 'nullable|array',
                'designation' => 'nullable|string|max:255',
                'bio' => 'nullable|string',
                'preferred_contact_time' => 'nullable|string|max:100',
                'preferred_contact_via' => 'nullable|string|max:50',
                'hired_before' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 422);
            }

            $profile->fill($request->only([
                'name',
                'profile_picture',
                'company_name',
                'industry',
                'website',
                'company_size',
                'business_location',
                'years_in_operation',
                'company_description',
                'social_links',
                'designation',
                'bio',
                'preferred_contact_time',
                'preferred_contact_via',
                'hired_before',
            ]));

            $profile->save();

            return response()->json([
                'user' => [
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
                ],
                'profile' => $profile,
            ]);
        }





            /**
     * Update Job Seeker Profile Picture
     */
    public function updateProfilePicture(Request $request)
    {
        if (Auth::guard('api')->check()) {
            $user = Auth::guard('api')->user();
        } elseif (Auth::guard('admin')->check() && $request->has('user_id')) {
            $user = User::findOrFail($request->user_id);
        } else {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate request
        $validator = Validator::make($request->all(), [
            'profile_picture' => 'required|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filePath = $user->saveProfilePicture($request->file('profile_picture'));

        return response()->json([
            'status' => true,
            'message' => 'Profile picture updated successfully!',
            'profile_picture' => $filePath
        ]);
    }


}
