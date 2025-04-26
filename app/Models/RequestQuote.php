<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestQuote extends Model
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
    ];

    protected $appends = ['rating', 'review_comment'];

    // Relationships

    public function jobSeekers()
    {
        return $this->belongsToMany(JobSeeker::class, 'job_seeker_request_quote', 'request_quote_id', 'job_seeker_id')
                    ->withPivot('salary');  // Include the salary field
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
}
