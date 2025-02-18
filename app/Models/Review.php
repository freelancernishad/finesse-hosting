<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'job_seeker_id',
        'applied_job_id',
        'reviewer_id',
        'reviewer_name',
        'reviewer_email',
        'reviewer_phone',
        'rating',
        'comment',
        'title',
        'reviewer_type'
    ];

    // Relationship: The JobSeeker being reviewed
    public function jobSeeker()
    {
        return $this->belongsTo(JobSeeker::class, 'job_seeker_id');
    }

    // Relationship: The job application related to this review
    public function appliedJob()
    {
        return $this->belongsTo(AppliedJob::class, 'applied_job_id');
    }

    // Relationship: The user who wrote the review (if registered)
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewer_id');
    }

    // Accessor: Get formatted rating with stars
    public function getFormattedRatingAttribute()
    {
        return str_repeat('⭐', $this->rating);
    }
}
