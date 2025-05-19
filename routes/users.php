<?php

use Illuminate\Support\Facades\Route;
use App\Http\Middleware\AuthenticateUser;
use App\Http\Controllers\Api\Global\ReviewController;
use App\Http\Controllers\Api\Auth\User\AuthUserController;
use App\Http\Controllers\Api\JobSeeker\JobSeekerController;
use App\Http\Controllers\Api\JobSeeker\JobApplicationController;
use App\Http\Controllers\Api\Auth\User\UserPasswordResetController;
use App\Http\Controllers\Api\User\HiringConsultationRequestController;
use App\Http\Controllers\Api\User\UserManagement\UserProfileController;
use App\Http\Controllers\Api\Global\HiringRequest\HiringRequestController;
use App\Http\Controllers\Api\Admin\JobSeeker\JobSeekerHiringRequestController;

Route::prefix('auth/user')->group(function () { // Prefix for job seeker routes
    Route::post('register', [AuthUserController::class, 'register'])->name('user.register');
    Route::post('login', [AuthUserController::class, 'login'])->name('user.login');
    Route::post('/verify-otp', [AuthUserController::class, 'verifyOtp']);
    Route::post('/resend-otp', [AuthUserController::class, 'resendOtp']);

    Route::middleware(AuthenticateUser::class)->group(function () { // Applying user authentication middleware
        Route::post('logout', [AuthUserController::class, 'logout']);
        Route::get('me', [AuthUserController::class, 'me']);
        Route::post('/change-password', [AuthUserController::class, 'changePassword']);
        Route::get('check-token', [AuthUserController::class, 'checkToken']);
    });
});


Route::post('user/password/email', [UserPasswordResetController::class, 'sendResetLinkEmail']);
Route::post('user/password/reset', [UserPasswordResetController::class, 'reset']);



Route::prefix('user')->group(function () {
    Route::middleware(AuthenticateUser::class)->group(function () {





        Route::get('/employer/overview', [UserProfileController::class, 'EmployerOverview']);





        Route::get('/profile', [UserProfileController::class, 'getProfile']);
        Route::put('/update-profile', [UserProfileController::class, 'updateProfile']);
        Route::post('/update-profile-picture', [UserProfileController::class, 'updateProfilePicture']);
        Route::post('/update-resume', [JobSeekerController::class, 'updateResume']);
        Route::get('/download-resume', [JobSeekerController::class, 'downloadResume']);

        Route::get('/reviews', [ReviewController::class, 'getMyReviews']);



        Route::post('/join-waiting-list', [JobApplicationController::class, 'join_waiting_list']);
        Route::post('/job-apply', [JobApplicationController::class, 'join_waiting_list']);

        Route::get('/waiting-lists', [JobApplicationController::class, 'getWaitingLists']);
        Route::get('/job-applications', [JobApplicationController::class, 'getWaitingLists']);






        Route::post('/apply/jobs', [JobApplicationController::class, 'applyForPostedJob']);
        Route::get('/list/applied-jobs', [JobApplicationController::class, 'getPostedJobApplications']);






        Route::get('hiring-request', [JobSeekerHiringRequestController::class, 'index']);

        Route::post('/hiring-request', [HiringRequestController::class, 'store']);
        Route::post('/request-quote', [HiringRequestController::class, 'store']);


          Route::post('/hiring-consultation-requests', [HiringConsultationRequestController::class, 'store']);
            Route::get('/hiring-consultation-requests', [HiringConsultationRequestController::class, 'index']);





    });
});

