<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Api\Auth\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\JobSeeker\JobSeekerController;
use App\Http\Controllers\Api\Admin\JobCategory\JobCategoryController;
use App\Http\Controllers\Api\Admin\JobSeeker\JobApplicationController;
use App\Http\Controllers\Api\Admin\DashboardMetrics\DashboardController;
use App\Http\Controllers\Api\Admin\JobSeeker\JobSeekerRequestQuoteController;


Route::prefix('auth/admin')->group(function () {
    Route::post('login', [AdminAuthController::class, 'login'])->name('admin.login');
    Route::post('register', [AdminAuthController::class, 'register']);

    Route::middleware(AuthenticateAdmin::class)->group(function () { // Applying admin middleware
        Route::post('logout', [AdminAuthController::class, 'logout']);
        Route::get('me', [AdminAuthController::class, 'me']);
        Route::post('/change-password', [AdminAuthController::class, 'changePassword']);
        Route::get('check-token', [AdminAuthController::class, 'checkToken']);
    });
});



Route::prefix('admin')->group(function () {
    Route::middleware(AuthenticateAdmin::class)->group(function () { // Applying admin middleware


        Route::get('/dashboard/overview', [DashboardController::class, 'getOverview']);
        Route::get('/dashboard/statistics', [DashboardController::class, 'getStatistics']);
        Route::get('/dashboard/recent-activities', [DashboardController::class, 'getRecentActivities']);



        Route::prefix('/job-seeker')->group(function () {
            Route::get('/', [JobSeekerController::class, 'index']); // List all JobSeekers
            Route::post('/', [JobSeekerController::class, 'store']); // Create a new JobSeeker
            Route::get('{id}', [App\Http\Controllers\Api\JobSeeker\JobSeekerController::class, 'getProfile']); // Show a specific JobSeeker
            Route::post('{id}', [JobSeekerController::class, 'update']); // Update a JobSeeker
            Route::delete('{id}', [JobSeekerController::class, 'destroy']); // Delete a JobSeeker
            Route::get('/request-quote/{requestQuoteId}/job-seekers', [JobSeekerController::class, 'getJobSeekersByRequestQuote']); // Get JobSeekers by RequestQuote
        });






        Route::prefix('job-seeker/job-application')->group(function () {
            // Route to get all job applications
            Route::get('/list', [JobApplicationController::class, 'getJobApplications']);

            Route::post('/{jobApplicationId}/update', [JobApplicationController::class, 'updateJobApplication']);

            // Route to update job application status by admin
            Route::put('/{jobApplicationId}', [JobApplicationController::class, 'adminUpdateJobApplication']);

            // Route to get details of a specific job application
            Route::get('/{jobApplicationId}/details', [JobApplicationController::class, 'getJobApplicationDetails']);

            // Route to delete a job application
            Route::delete('/{jobApplicationId}', [JobApplicationController::class, 'deleteJobApplication']);
        });




            // Job category routes
            Route::get('job-categories', [JobCategoryController::class, 'getJobCategories']);
            Route::post('job-categories', [JobCategoryController::class, 'createJobCategory']);
            Route::put('job-categories/{category_id}', [JobCategoryController::class, 'updateJobCategory']);
            Route::delete('job-categories/{category_id}', [JobCategoryController::class, 'deleteJobCategory']);
            Route::get('job-categories/{category_id}', [JobCategoryController::class, 'getJobCategory']);





            Route::get('/available-job-seekers', [JobSeekerRequestQuoteController::class, 'getAvailableJobSeekers']);

            Route::get('request-quotes', [JobSeekerRequestQuoteController::class, 'index']);
            Route::get('request-quote/{id}', [JobSeekerRequestQuoteController::class, 'show']);
            Route::post('request-quote/{id}/assign-job-seekers', [JobSeekerRequestQuoteController::class, 'assignJobSeekers']);
            Route::put('request-quote/{id}/update-status', [JobSeekerRequestQuoteController::class, 'updateStatus']);
            Route::post('request-quote/{id}/confirm-quote', [JobSeekerRequestQuoteController::class, 'confirmQuote']);







    });
});




