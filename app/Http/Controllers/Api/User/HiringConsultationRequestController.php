<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HiringConsultationRequest;
use Illuminate\Support\Facades\Auth;

class HiringConsultationRequestController extends Controller
{
    // Store a new hiring consultation request
    public function store(Request $request)
    {
        $request->validate([
            'full_name' => 'required|string',
            'email' => 'required|email',
            'phone_number' => 'nullable|string',
            'company_name' => 'nullable|string',
            'industry_sector' => 'nullable|string',
            'company_size' => 'nullable|string',
            'company_description' => 'nullable|string',
            'hiring_needs' => 'required|string',
            'number_of_positions' => 'required|string',
            'hiring_urgency' => 'required|string',
            'preferred_consultation_date' => 'nullable|date',
            'additional_info' => 'nullable|string',
        ]);

        $data = $request->all();
        $data['user_id'] = Auth::id(); // authenticated user
        $data['status'] = 'pending';   // default

        $consultation = HiringConsultationRequest::create($data);

        return response()->json([
            'message' => 'Hiring consultation request submitted successfully.',
            'data' => $consultation
        ], 201);
    }

    // List all requests for authenticated user
    public function index()
    {
        $user = Auth::user();
        $perPage = request()->get('per_page', 15);
        $requests = HiringConsultationRequest::with('user')->where('user_id', $user->id)
            ->latest()
            ->paginate($perPage);

        return response()->json($requests);
    }
}
