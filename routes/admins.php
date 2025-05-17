<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Api\Auth\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\JobPost\PostJobController;
use App\Http\Controllers\Api\Admin\Employer\EmployerController;
use App\Http\Controllers\Api\Admin\JobSeeker\JobSeekerController;
use App\Http\Controllers\Api\Admin\JobSeeker\WaitingListController;
use App\Http\Controllers\Api\Admin\JobCategory\JobCategoryController;
use App\Http\Controllers\Api\Admin\JobSeeker\JobApplicationController;
use App\Http\Controllers\Api\Admin\DashboardMetrics\DashboardController;
use App\Http\Controllers\Api\Admin\JobSeeker\JobSeekerHiringRequestController;
use App\Http\Controllers\Api\Admin\HiringConsultationRequestController as AdminHCR;


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
            Route::get('/request-quote/{HiringRequestId}/job-seekers', [JobSeekerController::class, 'getJobSeekersByHiringRequest']); // Get JobSeekers by HiringRequest
        });
         Route::get('/download-resume/{job_seeker_id}', [JobSeekerController::class, 'downloadResume']);



        Route::prefix('/employers')->group(function () {
            Route::get('/', [EmployerController::class, 'index']);
            Route::post('/', [EmployerController::class, 'store']);
            Route::get('/{id}', [EmployerController::class, 'show']);
            Route::post('/{id}', [EmployerController::class, 'update']);
            Route::delete('/{id}', [EmployerController::class, 'destroy']);
        });






       Route::prefix('job-seeker/waiting-list')->group(function () {
            // Route to get all job applications
            Route::get('/list', [WaitingListController ::class, 'getWaitingListApplications']);

            Route::post('/{jobApplicationId}/update', [WaitingListController ::class, 'updateWaitingListApplication']);

            // Route to update job application status by admin
            Route::put('/{jobApplicationId}', [WaitingListController ::class, 'adminUpdateWaitingListApplication']);

            // Route to get details of a specific job application
            Route::get('/{jobApplicationId}/details', [WaitingListController ::class, 'getWaitingListApplicationDetails']);

            // Route to delete a job application
            Route::delete('/{jobApplicationId}', [WaitingListController ::class, 'deleteWaitingListApplication']);
        });



        Route::prefix('job-seeker/job/application')->group(function () {
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
            Route::get('get/all/industry-and-category', [JobCategoryController::class, 'getIndustryCategories']);
            Route::get('job-categories', [JobCategoryController::class, 'getJobCategories']);
            Route::get('industries', [JobCategoryController::class, 'getJobCategories']);

            Route::post('job-categories', [JobCategoryController::class, 'createJobCategory']);
            Route::put('job-categories/{category_id}', [JobCategoryController::class, 'updateJobCategory']);
            Route::delete('job-categories/{category_id}', [JobCategoryController::class, 'deleteJobCategory']);
            Route::get('job-categories/{category_id}', [JobCategoryController::class, 'getJobCategory']);





            Route::get('/available-job-seekers', [JobSeekerHiringRequestController::class, 'getAvailableJobSeekers']);

            Route::get('hiring-request', [JobSeekerHiringRequestController::class, 'index']);
            Route::get('hiring-request/{id}', [JobSeekerHiringRequestController::class, 'show']);
            Route::post('hiring-request/{id}/assign-job-seekers', [JobSeekerHiringRequestController::class, 'assignJobSeekers']);
            Route::put('hiring-request/{id}/update-status', [JobSeekerHiringRequestController::class, 'updateStatus']);
            Route::post('hiring-request/{id}/confirm-request', [JobSeekerHiringRequestController::class, 'confirmQuote']);






        // List all jobs
        Route::get('/post-jobs', [PostJobController::class, 'index']);

        // Create a new job
        Route::post('/post-jobs', [PostJobController::class, 'store']);

        // Show a specific job
        Route::get('/post-jobs/{postJob}', [PostJobController::class, 'show']);

        // Update a specific job
        Route::put('/post-jobs/{postJob}', [PostJobController::class, 'update']);
        Route::patch('/post-jobs/{postJob}', [PostJobController::class, 'update']);

        // Delete a job
        Route::delete('/post-jobs/{postJob}', [PostJobController::class, 'destroy']);

        Route::patch('/post-jobs/{id}/status', [PostJobController::class, 'updateStatus']);



    Route::get('/hiring-consultation-requests', [AdminHCR::class, 'index']);
    Route::put('/hiring-consultation-requests/{id}/status', [AdminHCR::class, 'updateStatus']);



    });
});




