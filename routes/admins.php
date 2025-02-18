<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateAdmin;
use App\Http\Controllers\Api\Auth\Admin\AdminAuthController;
use App\Http\Controllers\Api\Admin\JobCategory\JobCategoryController;
use App\Http\Controllers\Api\Admin\JobSeeker\JobApplicationController;


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













    });
});




