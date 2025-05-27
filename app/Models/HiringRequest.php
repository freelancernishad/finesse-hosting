<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HiringRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'email',
        'phone',
        'how_did_you_hear',
        'event_date',
        'start_time',
        'categories',
        'number_of_guests',
        'event_location',
        'event_details',
        'area',
        'type_of_hiring',
        'status',
        'budget',

        'selected_industry',
        'selected_categories',
        'job_descriptions',
        'is_use_my_current_company_location',
        'job_location',
        'years_of_experience',
        'reason_for_hire',
        'note',
        'hire_for_my_current_company',
        'company_info',
        'total_hours',
        'start_date',
        'end_date',
        'model_name',

        'expected_joining_date',
        'min_yearly_salary',
        'mix_yearly_salary',
    ];

    protected $casts = [
        'categories' => 'array',
        'selected_categories' => 'array',
        'job_descriptions' => 'array',
        'job_location' => 'array',
        'company_info' => 'array',
        'is_use_my_current_company_location' => 'boolean',
        'hire_for_my_current_company' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'expected_joining_date' => 'datetime',
        'min_yearly_salary' => 'decimal:2',
        'mix_yearly_salary' => 'decimal:2',
    ];

    protected $appends = ['rating', 'review_comment'];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function employer()
    {
        return $this->belongsTo(Employer::class, 'user_id', 'user_id');
    }

    public function jobSeekers()
    {
        return $this->belongsToMany(JobSeeker::class, 'hiring_request_job_seeker', 'hiring_request_id', 'job_seeker_id')
            ->withPivot('hourly_rate', 'total_hours', 'total_amount')
            ->withTimestamps();
    }

    public function AssignedJobSeekers()
    {
        return $this->belongsToMany(JobSeeker::class, 'hiring_request_job_seeker', 'hiring_request_id', 'job_seeker_id')
            ->withPivot('hourly_rate', 'total_hours', 'total_amount')
            ->withTimestamps();
    }

    public function review()
    {
        return $this->hasOne(Review::class, 'request_quote_id');
    }

    // Accessors
    public function getRatingAttribute()
    {
        return $this->review ? $this->review->rating : null;
    }

    public function getReviewCommentAttribute()
    {
        return $this->review ? $this->review->comment : null;
    }

    // Assign job seekers to this hiring request
    public function assignJobSeekers($jobSeekerIds)
    {
        $this->jobSeekers()->sync($jobSeekerIds);
        $this->status = 'assigned';
        $this->save();
    }

    // Matched job seekers based on selected categories and excluding already assigned job seekers
    public function matchedJobSeekers()
    {
        // Decode selected_categories safely
        $selectedCategories = is_string($this->selected_categories)
            ? json_decode($this->selected_categories, true)
            : ($this->selected_categories ?? []);

        Log::info('Selected Categories:', $selectedCategories);

        // Extract just the category names
        $categoryNames = collect($selectedCategories)
            ->pluck('name')
            ->filter()
            ->unique()
            ->values()
            ->toArray();

        Log::info('Requested Category Names:', $categoryNames);

        // Get IDs of job seekers already assigned to any non-completed hiring requests
        $assignedJobSeekerIds = DB::table('hiring_request_job_seeker')
            ->join('hiring_requests', 'hiring_request_job_seeker.hiring_request_id', '=', 'hiring_requests.id')
            ->where('hiring_requests.status', '!=', 'completed')
            ->pluck('job_seeker_id')
            ->unique()
            ->toArray();

        Log::info('Assigned Job Seeker IDs:', $assignedJobSeekerIds);

        // Return related job seekers that match category and are not already assigned
        return JobSeeker::whereNotIn('id', $assignedJobSeekerIds)
            ->whereHas('approvedJobCategories', function ($query) use ($categoryNames) {
                $query->whereIn('category', $categoryNames);
            })->count();
    }
}
