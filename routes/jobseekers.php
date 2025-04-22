<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateJobSeeker;
use App\Http\Controllers\Api\Global\ReviewController;
use App\Http\Controllers\Api\JobSeeker\JobSeekerController;
use App\Http\Controllers\Api\JobSeeker\JobApplicationController;
use App\Http\Controllers\Api\Auth\JobSeeker\JobSeekerPasswordResetController;
use App\Http\Controllers\Api\Auth\JobSeeker\JobSeekerAuthController; // JobSeekerAuthController

// Route::prefix('auth/jobseeker')->group(function () { // Prefix for job seeker routes
//     Route::post('login', [JobSeekerAuthController::class, 'login'])->name('jobseeker.login');
//     Route::post('/verify-otp', [JobSeekerAuthController::class, 'verifyOtp']);
//     Route::post('/resend-otp', [JobSeekerAuthController::class, 'resendOtp']);
//     Route::post('register', [JobSeekerAuthController::class, 'register'])->name('jobseeker.register');

//     Route::middleware(AuthenticateJobSeeker::class)->group(function () { // Applying jobseeker authentication middleware
//         Route::post('logout', [JobSeekerAuthController::class, 'logout']);
//         Route::get('me', [JobSeekerAuthController::class, 'me']);
//         Route::post('/change-password', [JobSeekerAuthController::class, 'changePassword']);
//         Route::get('check-token', [JobSeekerAuthController::class, 'checkToken']);
//     });
// });


// Route::post('jobseeker/password/email', [JobSeekerPasswordResetController::class, 'sendResetLinkEmail']);
// Route::post('jobseeker/password/reset', [JobSeekerPasswordResetController::class, 'reset']);


Route::prefix('jobseeker')->group(function () {
    Route::middleware(AuthenticateJobSeeker::class)->group(function () {










        // Job Seeker Routes
        // Apply for a job



    });
});




