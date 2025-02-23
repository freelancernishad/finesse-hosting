<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class JobSeeker extends Authenticatable implements JWTSubject
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'member_id', 'id_no', 'phone_number', 'email',
        'password', 'location', 'join_date', 'resume', 'profile_picture'
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
    ];

    // Append this field automatically when fetching the JobSeeker model
    protected $appends = [
        'average_review_rating',
         'review_summary',
        'total_reviews',
        'approved_job_roles'
    ];

    /**
     * Accessor: Get the unique job categories where the job application is approved.
     *
     * @return array
     */
    public function getApprovedJobRolesAttribute()
    {
        return $this->appliedJobs()
            ->where('status', 'approved') // Only fetch approved applications
            ->pluck('category') // Get the category field directly
            ->unique()
            ->values()
            ->toArray();
    }

    /**
     * Relationship: JobSeekers reviews (Ratings and Comments)
     */
    public function reviews()
    {
        return $this->hasMany(Review::class, 'job_seeker_id');
    }

    /**
     * Accessor: Get the average rating of the job seeker
     *
     * @return float|null
     */

    public function getAverageReviewRatingAttribute()
    {
        // Get the average rating of all reviews (rounding to 1 decimal place)
        $averageRating = $this->reviews()->avg('rating');

        return $averageRating ? round($averageRating, 1) : 0; // Return null if no reviews exist
    }


     /**
     * Accessor: Get the count of each review rating (1 to 5 stars)
     */
    public function getReviewSummaryAttribute()
    {
        // Group by rating and count occurrences
        $reviewCounts = $this->reviews()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        // Ensure all ratings (1-5) are included, even if they are 0
        return [
            '1_star' => $reviewCounts->get(1, 0),
            '2_star' => $reviewCounts->get(2, 0),
            '3_star' => $reviewCounts->get(3, 0),
            '4_star' => $reviewCounts->get(4, 0),
            '5_star' => $reviewCounts->get(5, 0),
        ];
    }

    /**
     * Accessor: Get the total number of reviews
     */
    public function getTotalReviewsAttribute()
    {
        return $this->reviews()->count();
    }

    public function saveProfilePicture($file)
    {
        $folderPath = $this->member_id; // Use member_id as the folder name

        $filePath = uploadFileToS3($file, 'profile_pictures/' . $folderPath); // Define the S3 directory with member_id
        $this->profile_picture = $filePath;
        $this->save();

        return $filePath;
    }

    /**
     * Save the resume file to a storage location (e.g., S3).
     *
     * @param  mixed  $file
     * @return string
     */
    public function saveResume($file)
    {
        $folderPath = $this->member_id; // Use member_id as the folder name

        $filePath = uploadFileToS3($file, 'resumes/' . $folderPath); // Define the S3 directory for resumes with member_id
        $this->resume = $filePath;
        $this->save();

        return $filePath;
    }

    /**
     * Generate a unique member_id before creating the user.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobSeeker) {
            $jobSeeker->member_id = static::generateUniqueMemberId();
        });
    }

    /**
     * Generate a unique numeric member_id.
     *
     * @return int
     */
    protected static function generateUniqueMemberId(): int
    {
        do {
            $memberId = mt_rand(100000, 999999); // Generate a random 6-digit number
        } while (static::where('member_id', $memberId)->exists()); // Ensure it's unique

        return $memberId;
    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key-value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => !is_null($this->email_verified_at),
        ];
    }

    public function appliedJobs()
    {
        return $this->hasMany(AppliedJob::class);
    }


}
