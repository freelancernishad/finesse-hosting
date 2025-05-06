<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;

class JobSeeker extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'user_id', 'member_id', 'id_no', 'phone_number',
        'location', 'post_code', 'city', 'country', 'join_date', 'resume',
    ];

    protected $hidden = [

    ];

    protected $casts = [
    ];

    protected $appends = [
        'average_review_rating',
        'review_summary',
        'total_reviews',
        'approved_job_roles',
        'last_review',
    ];


    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function appliedJobs()
    {
        return $this->hasMany(AppliedJob::class)->where('status', 'approved')->select(['id', 'category', 'area']);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'job_seeker_id');
    }

    public function getApprovedJobRolesAttribute()
    {
        return $this->appliedJobs
            ->pluck('category')
            ->unique()
            ->values()
            ->toArray();
    }


    public function getAverageReviewRatingAttribute()
    {
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }

    public function getReviewSummaryAttribute()
    {
        return $this->reviews()
            ->selectRaw('rating, COUNT(*) as count')
            ->groupBy('rating')
            ->pluck('count', 'rating')
            ->union([1 => 0, 2 => 0, 3 => 0, 4 => 0, 5 => 0])
            ->toArray();
    }

    public function getTotalReviewsAttribute()
    {
        return $this->reviews()->count();
    }

    public function getLastReviewAttribute()
    {
        $review = $this->reviews()->latest()->first();
        if (!$review) return null;

        return [
            'reviewer_name' => $review->reviewer_name,
            'title' => $review->title,
            'comment' => $review->comment,
            'created_at' => Carbon::parse($review->created_at)->diffForHumans()
        ];
    }



    public function saveResume($file)
    {
        $filePath = uploadFileToS3($file, 'resumes/' . $this->member_id);
        return tap($this)->update(['resume' => $filePath])->resume;
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(fn($jobSeeker) => $jobSeeker->fill([
            'member_id' => static::generateUniqueMemberId()
        ]));
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



    public function HiringRequests()
    {
        return $this->belongsToMany(HiringRequest::class, 'job_seeker_request_quote', 'job_seeker_id', 'request_quote_id')
        ->withPivot('hourly_rate', 'total_hours', 'total_amount')
        ->withTimestamps();
    }



}
