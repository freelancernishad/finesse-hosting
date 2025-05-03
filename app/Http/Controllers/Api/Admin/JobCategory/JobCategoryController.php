<?php

namespace App\Http\Controllers\Api\Admin\JobCategory;

use App\Http\Controllers\Controller;
use App\Models\JobCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobCategoryController extends Controller
{
      /**
     * Get all job categories with pagination and filtering.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */

     public function getIndustryCategories(Request $request)
     {
         // Base query
         $query = JobCategory::query()
             ->withCount([
                 'appliedJobs' => function ($q) {
                     $q->where('status', 'approved');
                 }
             ])
             ->with('categories'); // 👈 Load children relation

         // Filters
         if ($request->has('name')) {
             $query->where('name', 'like', '%' . $request->name . '%');
         }

         if ($request->has('status')) {
             $query->where('status', $request->status);
         }

         // Only fetch top-level parents
         $query->whereNull('parent_id')->latest();

         if (auth('admin')->check()) {
             $perPage = $request->input('per_page', 10);
             $jobCategories = $query->paginate($perPage);
         } else {
             $jobCategories = $query->get();
         }

         return response()->json($jobCategories, 200);
     }


    public function getJobCategories(Request $request)
    {
        // Apply filters if provided
        $query = JobCategory::query()->withCount([
            'appliedJobs' => function ($query) {
                $query->where('status', 'approved'); // Only count approved applications
            }
        ]);

        // Filter by category name
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Filter by parent_id if provided
        if ($request->has('parent_id')) {
            $query->where('parent_id', $request->parent_id);
        } else {
            // Only include categories where parent_id is null
            $query->whereNull('parent_id');
        }

        // Order by latest
        $query->latest(); // Equivalent to orderBy('created_at', 'desc')

        // Check if the user is authenticated with the 'admin' guard
        if (auth('admin')->check()) {
            $perPage = $request->input('per_page', 10); // Default to 10 if not provided
            $jobCategories = $query->paginate($perPage);
        } else {
            $jobCategories = $query->get(); // Get all without pagination
        }

        return response()->json($jobCategories, 200);
    }




    /**
     * Create a new job category.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    /**
     * Create a new job category.
     */
    public function createJobCategory(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_categories,name',
            'parent_id' => 'nullable|exists:job_categories,id',
            'hourly_rate' => 'nullable|numeric|min:0', // 🆕 Add hourly_rate validation
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Create job category
        $jobCategory = JobCategory::create([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'hourly_rate' => $request->hourly_rate, // 🆕 Save hourly_rate
        ]);

        return response()->json([
            'message' => 'Job category created successfully!',
            'job_category' => $jobCategory,
        ], 201);
    }

    /**
     * Update an existing job category.
     */
    public function updateJobCategory(Request $request, $category_id)
    {
        // Find the job category
        $jobCategory = JobCategory::where('category_id', $category_id)->firstOrFail();

        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_categories,name,' . $jobCategory->id,
            'parent_id' => 'nullable|exists:job_categories,id',
            'hourly_rate' => 'nullable|numeric|min:0', // 🆕 Validate hourly_rate
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Update job category
        $jobCategory->update([
            'name' => $request->name,
            'parent_id' => $request->parent_id,
            'hourly_rate' => $request->hourly_rate, // 🆕 Update hourly_rate
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Job category updated successfully!',
            'job_category' => $jobCategory,
        ], 200);
    }

    /**
     * Delete a job category.
     */
    public function deleteJobCategory($category_id)
    {
        $jobCategory = JobCategory::findOrFail($category_id);
        $jobCategory->delete();

        return response()->json([
            'status' => true,
            'message' => 'Job category deleted successfully!',
        ], 200);
    }

    /**
     * Get details of a specific job category.
     */
    public function getJobCategory($category_id)
    {
        $jobCategory = JobCategory::findOrFail($category_id);

        return response()->json($jobCategory, 200);
    }
}
