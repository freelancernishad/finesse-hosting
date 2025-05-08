<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\Global\ReviewController;
use App\Http\Controllers\Api\Server\ServerStatusController;
use App\Http\Controllers\Api\User\Package\UserPackageController;
use App\Http\Controllers\Api\Admin\JobCategory\JobCategoryController;
use App\Http\Controllers\Api\Global\HiringRequest\HiringRequestController;
use App\Http\Controllers\Api\User\PackageAddon\UserPackageAddonController;

// Load InitialRoutes
if (file_exists($userRoutes = __DIR__.'/InitialRoutes/example.php')) {
    require $userRoutes;
}


if (file_exists($userRoutes = __DIR__.'/InitialRoutes/users.php')) {
    require $userRoutes;
}

if (file_exists($adminRoutes = __DIR__.'/InitialRoutes/admins.php')) {
    require $adminRoutes;
}




// Load users and admins route files

if (file_exists($userRoutes = __DIR__.'/users.php')) {
    require $userRoutes;
}

if (file_exists($adminRoutes = __DIR__.'/admins.php')) {
    require $adminRoutes;
}

if (file_exists($adminRoutes = __DIR__.'/jobseekers.php')) {
    require $adminRoutes;
}





if (file_exists($stripeRoutes = __DIR__.'/Gateways/stripe.php')) {
    require $stripeRoutes;
}



Route::get('/server-status', [ServerStatusController::class, 'checkStatus']);






// Route to get all packages with discounts (query params for discount_months)
Route::get('global/packages', [UserPackageController::class, 'index']);

// Route to get a single package by ID with discounts
Route::get('global/package/{id}', [UserPackageController::class, 'show']);

Route::prefix('global/')->group(function () {
    Route::get('package-addons/', [UserPackageAddonController::class, 'index']); // List all addons
    Route::get('package-addons/{id}', [UserPackageAddonController::class, 'show']); // Get a specific addon

    Route::get('job-categories', [JobCategoryController::class, 'getJobCategories']);
    Route::get('industries', [JobCategoryController::class, 'getJobCategories']);
    Route::get('get/all/industry-and-category', [JobCategoryController::class, 'getIndustryCategories']);

    Route::get('/request-quotes/{HiringRequestId}/job-seekers', [HiringRequestController::class, 'getJobSeekersByHiringRequest']);



    Route::post('/request-quote', [HiringRequestController::class, 'store']);
    Route::post('request-quote/{HiringRequestId}/add-reviews', [ReviewController::class, 'addReviewsForHiringRequest']);
    Route::post('job-seeker/{jobSeekerId}/add-review', [ReviewController::class, 'addReviewForJobSeeker']);




});
