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
    ];

    protected $casts = [
        'categories' => 'array', // Store job categories as JSON
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

        // Many-to-many relationship with JobSeeker
        public function jobSeekers()
        {
            return $this->belongsToMany(JobSeeker::class, 'job_seeker_request_quote', 'request_quote_id', 'job_seeker_id');
        }


        // Update status and assign JobSeekers
        public function assignJobSeekers($jobSeekerIds)
        {
            $this->jobSeekers()->sync($jobSeekerIds);  // Sync job seekers with the request quote
            $this->status = 'assigned';  // Set status to assigned
            $this->save();
        }
}
