<?php

namespace App\Http\Controllers\Api\Admin\DashboardMetrics;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\JobSeeker;
use App\Models\AppliedJob;
use App\Models\RequestQuote;
use App\Models\JobCategory;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function getOverview()
    {
        // Total counts
        $totalJobSeekers = JobSeeker::count();
        $totalJobApplications = AppliedJob::count();
        $totalRequestQuotes = RequestQuote::count();
        $totalJobCategories = JobCategory::count();

        // Recent activity counts (last 7 days)
        $recentJobSeekers = JobSeeker::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $recentJobApplications = AppliedJob::where('created_at', '>=', Carbon::now()->subDays(7))->count();
        $recentRequestQuotes = RequestQuote::where('created_at', '>=', Carbon::now()->subDays(7))->count();

        // Status breakdowns
        $jobApplicationStatuses = AppliedJob::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        $requestQuoteStatuses = RequestQuote::selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status');

        return response()->json([
            'total_counts' => [
                'job_seekers' => $totalJobSeekers,
                'job_applications' => $totalJobApplications,
                'request_quotes' => $totalRequestQuotes,
                'job_categories' => $totalJobCategories,
            ],
            'recent_activity' => [
                'job_seekers' => $recentJobSeekers,
                'job_applications' => $recentJobApplications,
                'request_quotes' => $recentRequestQuotes,
                'time_period' => 'last_7_days',
            ],
            'status_breakdowns' => [
                'job_applications' => $jobApplicationStatuses,
                'request_quotes' => $requestQuoteStatuses,
            ]
        ]);
    }

    public function getStatistics()
    {
        // Monthly trends for the past 6 months
        $months = [];
        $jobSeekerData = [];
        $jobApplicationData = [];
        $requestQuoteData = [];

        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $month = $date->format('M Y');
            $months[] = $month;

            $start = $date->copy()->startOfMonth();
            $end = $date->copy()->endOfMonth();

            $jobSeekerData[] = JobSeeker::whereBetween('created_at', [$start, $end])->count();
            $jobApplicationData[] = AppliedJob::whereBetween('created_at', [$start, $end])->count();
            $requestQuoteData[] = RequestQuote::whereBetween('created_at', [$start, $end])->count();
        }

// Top job categories by applications
$topCategories = JobCategory::select([
    'job_categories.id',
    'job_categories.name',
    \DB::raw('COUNT(applied_jobs.id) as applications_count')
])
->leftJoin('applied_jobs', 'job_categories.id', '=', 'applied_jobs.job_category_id')
->groupBy('job_categories.id', 'job_categories.name') // Include all non-aggregated columns
->orderBy('applications_count', 'desc')
->limit(5)
->get()
->map(function($category) {
    return [
        'id' => $category->id,
        'name' => $category->name,
        'applications_count' => $category->applications_count
    ];
});

        // Get job seekers assigned to active RequestQuotes (status != 'completed')
        $assignedJobSeekerIds = \DB::table('job_seeker_request_quote')
            ->join('request_quotes', 'job_seeker_request_quote.request_quote_id', '=', 'request_quotes.id')
            ->where('request_quotes.status', '!=', 'completed')
            ->pluck('job_seeker_id')
            ->toArray();

        // Total job seekers count
        $totalJobSeekers = JobSeeker::count();

        // Available job seekers (not assigned to active requests)
        $availableJobSeekers = $totalJobSeekers - count(array_unique($assignedJobSeekerIds));

        // Active request quotes count
        $activeRequestQuotesCount = RequestQuote::where('status', '!=', 'completed')->count();

        return response()->json([
            'monthly_trends' => [
                'months' => $months,
                'job_seekers' => $jobSeekerData,
                'job_applications' => $jobApplicationData,
                'request_quotes' => $requestQuoteData,
            ],
            'top_categories' => $topCategories,
            'job_seeker_availability' => [
                'available' => $availableJobSeekers,
                'assigned_to_active_requests' => count(array_unique($assignedJobSeekerIds)),
                'total' => $totalJobSeekers,
            ],
            'active_request_quotes' => $activeRequestQuotesCount,
            'metrics' => [
                'average_seekers_per_active_request' => $activeRequestQuotesCount > 0
                    ? count($assignedJobSeekerIds) / $activeRequestQuotesCount
                    : 0,
            ]
        ]);
    }

    public function getRecentActivities()
    {
        // Recent job applications
        $recentApplications = AppliedJob::with(['jobSeeker', 'jobCategory'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($application) {
                return [
                    'id' => $application->id,
                    'job_seeker' => $application->jobSeeker->name,
                    'category' => $application->jobCategory->name,
                    'status' => $application->status,
                    'date' => $application->created_at->format('M d, Y H:i'),
                ];
            });

        // Recent request quotes
        $recentQuotes = RequestQuote::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($quote) {
                return [
                    'id' => $quote->id,
                    'title' => $quote->title,
                    'status' => $quote->status,
                    'date' => $quote->created_at->format('M d, Y H:i'),
                ];
            });

        // Recent job seekers
        $recentSeekers = JobSeeker::orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function($seeker) {
                return [
                    'id' => $seeker->id,
                    'name' => $seeker->name,
                    'email' => $seeker->email,
                    'date' => $seeker->created_at->format('M d, Y H:i'),
                ];
            });

        return response()->json([
            'recent_applications' => $recentApplications,
            'recent_quotes' => $recentQuotes,
            'recent_seekers' => $recentSeekers,
        ]);
    }
}
