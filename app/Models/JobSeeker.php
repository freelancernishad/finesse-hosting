<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Contracts\Auth\MustVerifyEmail;

class JobSeeker extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'member_id', 'id_no', 'phone_number', 'email',
        'password', 'location', 'post_code', 'city', 'country', 'join_date', 'resume', 'profile_picture',
        'email_verified_at', 'verification_token','otp', 'email_verified'
    ];

    protected $hidden = [
        'password', 'remember_token', 'verification_token','otp'
    ];

    protected $casts = [
        'password' => 'hashed',
        'email_verified_at' => 'datetime',
        'email_verified' => 'boolean',
    ];

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


    // Accessor to check if the email is verified
    public function getEmailVerifiedAttribute()
    {
        return !is_null($this->email_verified_at);
    }

    public function appliedJobs()
    {
        return $this->hasMany(AppliedJob::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'job_seeker_id');
    }

    public function getAverageReviewRatingAttribute()
    {
        $averageRating = $this->reviews()->avg('rating');
        return $averageRating ? round($averageRating, 1) : 0;
    }

    public function getReviewSummaryAttribute()
    {
        $reviewCounts = $this->reviews()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating');

        return [
            '1_star' => $reviewCounts->get(1, 0),
            '2_star' => $reviewCounts->get(2, 0),
            '3_star' => $reviewCounts->get(3, 0),
            '4_star' => $reviewCounts->get(4, 0),
            '5_star' => $reviewCounts->get(5, 0),
        ];
    }

    public function getTotalReviewsAttribute()
    {
        return $this->reviews()->count();
    }

    public function saveProfilePicture($file)
    {
        $folderPath = $this->member_id;
        $filePath = uploadFileToS3($file, 'profile_pictures/' . $folderPath);
        $this->profile_picture = $filePath;
        $this->save();

        return $filePath;
    }

    public function saveResume($file)
    {
        $folderPath = $this->member_id;
        $filePath = uploadFileToS3($file, 'resumes/' . $folderPath);
        $this->resume = $filePath;
        $this->save();

        return $filePath;
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($jobSeeker) {
            $jobSeeker->member_id = static::generateUniqueMemberId();
            $jobSeeker->verification_token = \Str::random(60);
        });
    }

    protected static function generateUniqueMemberId(): int
    {
        do {
            $memberId = mt_rand(100000, 999999);
        } while (static::where('member_id', $memberId)->exists());

        return $memberId;
    }

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims(): array
    {
        return [
            'name' => $this->name,
            'email' => $this->email,
            'email_verified' => !is_null($this->email_verified_at),
        ];
    }
}
