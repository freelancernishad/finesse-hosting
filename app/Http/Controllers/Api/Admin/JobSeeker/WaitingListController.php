<?php

namespace App\Http\Controllers\Api\Admin\JobSeeker;

use App\Http\Controllers\Controller;
use App\Models\AppliedJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class WaitingListController extends Controller
{
    /**
     * Get the list of waiting list job applications.
     */
    public function getWaitingListApplications(Request $request)
    {
        $query = AppliedJob::with('jobSeeker', 'admin')
            ->where('job_type', 'waiting_list')
            ->latest();

        if ($request->has('category') && $request->category != '') {
            $query->where('category', 'like', '%' . $request->category . '%');
        }

        if ($request->has('area') && $request->area != '') {
            $query->where('area', 'like', '%' . $request->area . '%');
        }

        if ($request->has('search') && $request->search != '') {
            $searchTerm = $request->search;
            $query->where(function ($query) use ($searchTerm) {
                $query->where('name', 'like', '%' . $searchTerm . '%')
                    ->orWhere('phone', 'like', '%' . $searchTerm . '%')
                    ->orWhere('email', 'like', '%' . $searchTerm . '%')
                    ->orWhere('country', 'like', '%' . $searchTerm . '%')
                    ->orWhere('city', 'like', '%' . $searchTerm . '%')
                    ->orWhere('address', 'like', '%' . $searchTerm . '%');
            });
        }

        $perPage = $request->get('per_page', 10);
        $jobApplications = $query->paginate($perPage);

        return response()->json($jobApplications, 200);
    }

    /**
     * Admin update waiting list application details.
     */
    public function updateWaitingListApplication(Request $request, $jobApplicationId)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:15',
            'email' => 'nullable|email|max:255',
            'date_of_birth' => 'nullable|date',
            'country' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'post_code' => 'nullable|string|max:10',
            'address' => 'nullable|string|max:255',
            'interest_file' => 'nullable|mimes:pdf,doc,docx,jpg,jpeg,png|max:5120',
            'area' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $appliedJob = AppliedJob::where('job_type', 'waiting_list')->findOrFail($jobApplicationId);

        $appliedJob->name = $request->name ?? $appliedJob->name;
        $appliedJob->phone = $request->phone ?? $appliedJob->phone;
        $appliedJob->email = $request->email ?? $appliedJob->email;
        $appliedJob->date_of_birth = $request->date_of_birth ?? $appliedJob->date_of_birth;
        $appliedJob->country = $request->country ?? $appliedJob->country;
        $appliedJob->city = $request->city ?? $appliedJob->city;
        $appliedJob->post_code = $request->post_code ?? $appliedJob->post_code;
        $appliedJob->address = $request->address ?? $appliedJob->address;
        $appliedJob->area = $request->area ?? $appliedJob->area;
        $appliedJob->category = $request->category ?? $appliedJob->category;

        if ($request->hasFile('interest_file')) {
            $appliedJob->interest_file = $request->file('interest_file')->store('interest_files');
        }

        $appliedJob->save();

        return response()->json([
            'status' => true,
            'message' => 'Waiting list application updated successfully!',
            'applied_job' => $appliedJob,
        ], 200);
    }

    /**
     * Admin update waiting list application status.
     */
    public function adminUpdateWaitingListApplication(Request $request, $jobApplicationId)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:pending,approved,rejected',
            'review_comments' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $appliedJob = AppliedJob::where('job_type', 'waiting_list')->findOrFail($jobApplicationId);

        $appliedJob->status = $request->status;
        $appliedJob->review_comments = $request->review_comments;
        $appliedJob->admin_id = Auth::guard('admin')->id();

        $appliedJob->save();

        return response()->json([
            'status' => true,
            'message' => 'Waiting list application status updated successfully!',
            'applied_job' => $appliedJob,
        ], 200);
    }

    /**
     * Get details of a specific waiting list job application.
     */
    public function getWaitingListApplicationDetails($jobApplicationId)
    {
        $appliedJob = AppliedJob::with('jobSeeker', 'admin')
            ->where('job_type', 'waiting_list')
            ->findOrFail($jobApplicationId);

        return response()->json([
            'status' => true,
            'message' => 'Waiting list application details retrieved successfully!',
            'applied_job' => $appliedJob,
        ], 200);
    }

    /**
     * Delete a waiting list job application.
     */
    public function deleteWaitingListApplication($jobApplicationId)
    {
        $appliedJob = AppliedJob::where('job_type', 'waiting_list')->findOrFail($jobApplicationId);
        $appliedJob->delete();

        return response()->json([
            'status' => true,
            'message' => 'Waiting list application deleted successfully!',
        ], 200);
    }
}
