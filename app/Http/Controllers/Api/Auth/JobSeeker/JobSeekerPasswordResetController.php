<?php

namespace App\Http\Controllers\Api\Auth\JobSeeker;

use App\Models\JobSeeker;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Mail\PasswordResetMail;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Validator;

class JobSeekerPasswordResetController extends Controller
{
    /**
     * Send a password reset link to the job seeker.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:job_seekers,email',
            'redirect_url' => 'required|url'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $email = $request->input('email');
        $resetUrlBase = $request->input('redirect_url');

        // Find the job seeker by email
        $jobSeeker = JobSeeker::where('email', $email)->first();

        // Send the password reset link
        $response = Password::broker('job_seekers')->sendResetLink(
            $request->only('email'),
            function ($user, $token) use ($resetUrlBase) {
                // Create the full reset URL
                $resetUrl = "{$resetUrlBase}?token={$token}&email={$user->email}";

                // Send the email
                Mail::to($user->email)->send(new PasswordResetMail($user, $resetUrl));
            }
        );

        // Return response based on whether the reset link was sent
        if ($response == Password::RESET_LINK_SENT) {
            return response()->json([
                'status' => __($response),
                'job_seeker' => [
                    'name' => $jobSeeker->name,
                    'email' => $jobSeeker->email
                ]
            ], 200);
        } else {
            return response()->json(['error' => __($response)], 400);
        }
    }

    /**
     * Reset the job seeker password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required',
            'email' => 'required|email|exists:job_seekers,email',
            'password' => 'required|confirmed|min:8',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $response = Password::broker('job_seekers')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = Hash::make($password);
                $user->save();

                event(new PasswordReset($user));
            }
        );

        return $response == Password::PASSWORD_RESET
            ? response()->json(['message' => 'Password has been reset successfully.'])
            : response()->json(['error' => 'Unable to reset password.'], 500);
    }
}
