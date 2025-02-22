<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateJobSeeker;
use App\Http\Controllers\Api\Global\ReviewController;
use App\Http\Controllers\Api\JobSeeker\JobSeekerController;
use App\Http\Controllers\Api\JobSeeker\JobApplicationController;
use App\Http\Controllers\Api\Auth\JobSeeker\JobSeekerAuthController; // JobSeekerAuthController

Route::prefix('auth/jobseeker')->group(function () { // Prefix for job seeker routes
    Route::post('login', [JobSeekerAuthController::class, 'login'])->name('jobseeker.login');
    Route::post('register', [JobSeekerAuthController::class, 'register'])->name('jobseeker.register');

    Route::middleware(AuthenticateJobSeeker::class)->group(function () { // Applying jobseeker authentication middleware
        Route::post('logout', [JobSeekerAuthController::class, 'logout']);
        Route::get('me', [JobSeekerAuthController::class, 'me']);
        Route::post('/change-password', [JobSeekerAuthController::class, 'changePassword']);
        Route::get('check-token', [JobSeekerAuthController::class, 'checkToken']);
    });
});



Route::prefix('jobseeker')->group(function () {
    Route::middleware(AuthenticateJobSeeker::class)->group(function () { // Applying admin middleware

        Route::get('/profile', [JobSeekerController::class, 'getProfile']);
        Route::put('/update-profile', [JobSeekerController::class, 'updateProfile']);
        Route::post('/update-profile-picture', [JobSeekerController::class, 'updateProfilePicture']);
        Route::post('/update-resume', [JobSeekerController::class, 'updateResume']);



        Route::get('/reviews', [ReviewController::class, 'getMyReviews']);




        // Job Seeker Routes
        Route::post('/job-apply', [JobApplicationController::class, 'applyForJob']);  // Apply for a job



    });
});




