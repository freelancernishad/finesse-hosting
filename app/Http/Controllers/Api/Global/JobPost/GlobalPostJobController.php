<?php

namespace App\Http\Controllers\Api\Global\JobPost;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PostJob;

class GlobalPostJobController extends Controller
{
    // Global list with filtering
   public function index(Request $request)
{
    $query = PostJob::query()->where('status', 'open'); // Only publicly open jobs

    // Filter by job title
    if ($request->filled('title')) {
        $query->where('title', 'like', '%' . $request->title . '%');
    }

    // Filter by location
    if ($request->filled('location')) {
        $query->where('location', 'like', '%' . $request->location . '%');
    }

    // Filter by multiple categories
    if ($request->filled('categories') && is_array($request->categories)) {
        foreach ($request->categories as $category) {
            $query->whereJsonContains('category', $category);
        }
    }

    // Filter by multiple job types (model)
    if ($request->filled('types') && is_array($request->types)) {
        $query->whereIn('model', $request->types);
    }

    // Filter by experience
    if ($request->filled('experience')) {
        $query->where('experience', 'like', '%' . $request->experience . '%');
    }

    // Salary range
    if ($request->filled('min_salary')) {
        $query->where('min_salary', '>=', $request->min_salary);
    }

    if ($request->filled('max_salary')) {
        $query->where('max_salary', '<=', $request->max_salary);
    }

    return response()->json(
        $query->latest()->paginate(10)
    );
}


    // Global single view
    public function show($id)
    {
        $job = PostJob::where('id', $id)
            ->where('status', 'open') // Ensure only open jobs are shown
            ->firstOrFail();

        return response()->json($job);
    }
}
