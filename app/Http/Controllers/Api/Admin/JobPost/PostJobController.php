<?php

namespace App\Http\Controllers\Api\Admin\JobPost;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostJob;
use Illuminate\Support\Facades\Validator;

class PostJobController extends Controller
{
    public function index()
    {
        return PostJob::latest()->paginate(10);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'hiring_request_id' => 'nullable|exists:hiring_requests,id',
            'title' => 'required|string|max:255',
            'category' => 'required|array',
            'category.*' => 'string|max:255',
            'model' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'salary_type' => 'required|string|max:100',
            'min_salary' => 'nullable|numeric',
            'max_salary' => 'nullable|numeric',
            'location' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:open,closed,draft',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $data = $validator->validated();

        // Set default status if not provided
        if (!isset($data['status'])) {
            $data['status'] = 'open';
        }

        $postJob = PostJob::create($data);

        return response()->json($postJob, 201);
    }

    public function show(PostJob $postJob)
    {
        $postJob->load('hiringRequest');
        return $postJob;
    }

    public function update(Request $request, PostJob $postJob)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'sometimes|required|string|max:255',
            'category' => 'sometimes|required|array',
            'category.*' => 'string|max:255',
            'model' => 'nullable|string|max:255',
            'experience' => 'nullable|string|max:255',
            'salary_type' => 'sometimes|required|string|max:100',
            'min_salary' => 'nullable|numeric',
            'max_salary' => 'nullable|numeric',
            'location' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'in:open,closed,draft',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $postJob->update($validator->validated());

        return response()->json($postJob);
    }

    public function destroy(PostJob $postJob)
    {
        $postJob->delete();

        return response()->json(['message' => 'PostJob deleted successfully']);
    }

    public function updateStatus(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'required|in:open,closed,draft',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $postJob = PostJob::findOrFail($id);
        $postJob->status = $request->status;
        $postJob->save();

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $postJob
        ]);
    }
}
