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

    public function getCategoriesAttribute($value)
    {
        if (is_array($value)) {
            return $value; // Already an array
        }

        // First decode attempt
        $decoded = json_decode($value, true);

        // If decoding returns a string (meaning it was double-encoded), decode again
        if (is_string($decoded)) {
            return json_decode($decoded, true);
        }

        return $decoded ?: [];
    }




    public function user()
    {
        return $this->belongsTo(User::class);
    }

        // Many-to-many relationship with JobSeeker
        // public function jobSeekers()
        // {
        //     return $this->belongsToMany(JobSeeker::class, 'job_seeker_request_quote', 'request_quote_id', 'job_seeker_id');
        // }


        public function jobSeekers()
        {
            return $this->belongsToMany(JobSeeker::class, 'job_seeker_request_quote', 'request_quote_id', 'job_seeker_id')
                        ->withPivot('salary');  // Include the salary field
        }



        // Update status and assign JobSeekers
        public function assignJobSeekers($jobSeekerIds)
        {
            $this->jobSeekers()->sync($jobSeekerIds);  // Sync job seekers with the request quote
            $this->status = 'assigned';  // Set status to assigned
            $this->save();
        }
}
