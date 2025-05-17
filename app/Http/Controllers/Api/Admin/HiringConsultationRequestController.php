<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HiringConsultationRequest;

class HiringConsultationRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = HiringConsultationRequest::query();

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('company_name')) {
            $query->where('company_name', 'like', '%' . $request->company_name . '%');
        }

        if ($request->filled('hiring_urgency')) {
            $query->where('hiring_urgency', $request->hiring_urgency);
        }

        if ($request->filled('email')) {
            $query->where('email', 'like', '%' . $request->email . '%');
        }

        if ($request->filled('phone_number')) {
            $query->where('phone_number', 'like', '%' . $request->phone_number . '%');
        }

        if ($request->filled('preferred_consultation_date')) {
            $query->whereDate('preferred_consultation_date', $request->preferred_consultation_date);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('preferred_consultation_date', [
                $request->date_from,
                $request->date_to
            ]);
        }

        // Pagination
        $perPage = $request->get('per_page', 10);
        $data = $query->latest()->paginate($perPage);

        return response()->json($data);
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:pending,approved,rejected'
        ]);

        $requestData = HiringConsultationRequest::findOrFail($id);
        $requestData->status = $request->status;
        $requestData->save();

        return response()->json([
            'message' => 'Status updated successfully',
            'data' => $requestData
        ]);
    }
}
