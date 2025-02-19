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
    public function getJobCategories(Request $request)
    {
        // Apply filters if provided
        $query = JobCategory::query();

        // Filter by category name
        if ($request->has('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
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
    public function createJobCategory(Request $request)
    {
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_categories,name',
            // 'status' => 'required|in:active,inactive',
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
        ]);

        return response()->json([
            'message' => 'Job category created successfully!',
            'job_category' => $jobCategory,
        ], 201);
    }

    /**
     * Update an existing job category.
     *
     * @param \Illuminate\Http\Request $request
     * @param string $category_id
     * @return \Illuminate\Http\Response
     */
    public function updateJobCategory(Request $request, $category_id)
    {
        // Find the job category
        $jobCategory = JobCategory::findOrFail($category_id);
        // Validate incoming request data
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:job_categories,name,' . $category_id . ',category_id',

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

        ]);

        return response()->json([
            'status' => true,
            'message' => 'Job category updated successfully!',
            'job_category' => $jobCategory,
        ], 200);
    }

    /**
     * Delete a job category.
     *
     * @param string $category_id
     * @return \Illuminate\Http\Response
     */
    public function deleteJobCategory($category_id)
    {
        // Find the job category
        $jobCategory = JobCategory::findOrFail($category_id);

        // Delete the job category
        $jobCategory->delete();

        return response()->json([
            'status' => true,
            'message' => 'Job category deleted successfully!',
        ], 200);
    }

    /**
     * Get details of a specific job category.
     *
     * @param string $category_id
     * @return \Illuminate\Http\Response
     */
    public function getJobCategory($category_id)
    {
        // Find the job category
        $jobCategory = JobCategory::findOrFail($category_id);

        return response()->json( $jobCategory, 200);
    }
}
