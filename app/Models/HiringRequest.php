<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HiringRequest extends Model
{

    use HasFactory;

    protected $fillable = [
        // Old fields
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

        // New fields
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
        'categories' => 'array', // Old: event categories
        'selected_categories' => 'array', // New: selected categories with number_of_employee
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


    public function jobSeekers()
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

    // Methods

    public function assignJobSeekers($jobSeekerIds)
    {
        $this->jobSeekers()->sync($jobSeekerIds);
        $this->status = 'assigned';
        $this->save();
    }



    public function matchedJobSeekers()
    {
        $selectedCategories = is_string($this->selected_categories)
            ? json_decode($this->selected_categories, true)
            : $this->selected_categories;

        $requestedCategoryNames = collect($selectedCategories)->pluck('name')->toArray();

        // Get all assigned job seeker IDs (any hiring request that is not 'completed')
        $assignedJobSeekerIds = DB::table('hiring_request_job_seeker')
            ->join('hiring_requests', 'hiring_request_job_seeker.hiring_request_id', '=', 'hiring_requests.id')
            ->where('hiring_requests.status', '!=', 'completed')
            ->pluck('job_seeker_id')
            ->toArray();

        return $this->hasMany(JobSeeker::class, 'id')
            ->whereNotIn('id', $assignedJobSeekerIds ?: [0])
            ->whereHas('approvedJobCategories', function ($q) use ($requestedCategoryNames) {
                $q->whereIn('category', $requestedCategoryNames);
            });
    }



}
