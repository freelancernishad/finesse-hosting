<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RequestQuote extends Model
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
        'budget'
    ];

    protected $casts = [
        'categories' => 'array', // Store job categories as JSON
    ];

    protected $appends = ['rating', 'review_comment','categories'];


      // Always ensure categories is an array when retrieved
      public function getCategoriesAttribute()
      {
        $value = $this->attributes['categories'];
          if (is_null($value)) {
              return [];
          }

          if (is_array($value)) {
              return $value;
          }

          // If it's a string, try to json_decode it
          $decoded = json_decode($value, true);
          if (json_last_error() === JSON_ERROR_NONE) {
              return $decoded;
          }

          // If not valid JSON, treat as comma-separated string
          if (is_string($value)) {
              return array_map('trim', explode(',', $value));
          }

          return (array) $value;
      }


    // Relationship: Many-to-many with JobSeekers
    public function jobSeekers()
    {
        return $this->belongsToMany(JobSeeker::class, 'job_seeker_request_quote', 'request_quote_id', 'job_seeker_id')
                    ->withPivot('salary');  // Include the salary field
    }

    // Relationship: Single Review for this RequestQuote
    public function review()
    {
        return $this->hasOne(Review::class, 'request_quote_id');
    }

    // Accessor: Get the rating from the single review
    public function getRatingAttribute()
    {
        return $this->review ? $this->review->rating : null; // Return null if no review
    }

    // Accessor: Get the review comment
    public function getReviewCommentAttribute()
    {
        return $this->review ? $this->review->comment : null; // Return null if no review
    }

    // Update status and assign JobSeekers
    public function assignJobSeekers($jobSeekerIds)
    {
        $this->jobSeekers()->sync($jobSeekerIds);  // Sync job seekers with the request quote
        $this->status = 'assigned';  // Set status to assigned
        $this->save();
    }
}
