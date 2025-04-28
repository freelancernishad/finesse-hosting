<?php

namespace App\Http\Controllers\Api\Admin\JobCategory;

use App\Http\Controllers\Controller;
use App\Models\JobCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobCategoryController extends Controller
{
    // ... (your getIndustryCategories and getJobCategories methods remain same)

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
