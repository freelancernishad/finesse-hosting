<?php

namespace App\Http\Controllers\Api\User\UserManagement;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class UserProfileController extends Controller
{
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
            'name' => 'sometimes|string|max:255',
            'profile_picture' => 'sometimes|image|max:2048',

            // Address fields
            'country' => 'sometimes|string|max:100',
            'state' => 'sometimes|string|max:100',
            'city' => 'sometimes|string|max:100',
            'region' => 'sometimes|string|max:100',
            'street_address' => 'sometimes|string|max:255',
            'zip_code' => 'sometimes|string|max:20',
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
                'id_no' => 'sometimes|string|max:255',
                'phone_number' => 'sometimes|string|max:20',
                'location' => 'sometimes|string|max:255',
                'post_code' => 'sometimes|string|max:20',
                'city' => 'sometimes|string|max:255',
                'country' => 'sometimes|string|max:255',
                'resume' => 'sometimes|file|mimes:pdf,doc,docx|max:2048',
                'profile_picture' => 'sometimes|image|max:2048',
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
            ]));

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
                'name' => 'sometimes|string|max:255',
                'profile_picture' => 'sometimes|string',
                'company_name' => 'nullable|string|max:255',
                'industry' => 'nullable|string|max:255',
                'website' => 'nullable|string|max:255',
                'company_size' => 'nullable|string|max:50',
                'business_location' => 'nullable|string|max:255',
                'years_in_operation' => 'nullable|string|max:100',
                'company_description' => 'nullable|string',
                'social_links' => 'nullable|string', // or 'array' if JSON
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
