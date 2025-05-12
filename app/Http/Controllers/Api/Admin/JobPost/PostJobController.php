<?php

namespace App\Http\Controllers\Api\Admin\JobPost;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostJob;

class PostJobController extends Controller
{
    public function index()
    {
        return PostJob::latest()->paginate(10);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'hiring_request_id' => 'required|exists:hiring_requests,id',
            'title' => 'required|string|max:255',
            'category' => 'required|string|max:255',
            'model' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'salary_type' => 'required|string|max:100',
            'min_salary' => 'nullable|numeric',
            'max_salary' => 'nullable|numeric',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:open,closed,draft',
        ]);

        // Set default status to 'open' if not provided
        if (!isset($validated['status'])) {
            $validated['status'] = 'open';
        }

        $postJob = PostJob::create($validated);

        return response()->json($postJob, 201);
    }

    public function show(PostJob $postJob)
    {
        return $postJob;
    }

    public function update(Request $request, PostJob $postJob)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|string|max:255',
            'model' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'salary_type' => 'sometimes|required|string|max:100',
            'min_salary' => 'nullable|numeric',
            'max_salary' => 'nullable|numeric',
            'location' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:open,closed,draft',
        ]);

        $postJob->update($validated);

        return response()->json($postJob);
    }

    public function destroy(PostJob $postJob)
    {
        $postJob->delete();

        return response()->json(['message' => 'PostJob deleted successfully']);
    }


    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:open,closed,draft',
        ]);

        $postJob = PostJob::findOrFail($id);
        $postJob->status = $request->status;
        $postJob->save();

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $postJob
        ]);
    }

}
