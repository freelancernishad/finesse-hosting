<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'hiring_request_id',
        'title',
        'category',
        'model',
        'experience',
        'salary_type',
        'min_salary',
        'max_salary',
        'location',
        'description',
        'status',
    ];

    protected $casts = [
        'min_salary' => 'decimal:2',
        'max_salary' => 'decimal:2',
        // 'category' => 'array',
    ];

    protected $appends = ['total_job_applicantions'];


    public function getTotalJobApplicantionsAttribute()
    {
        return $this->jobApplicaions()->count();
    }


    // Relationships
    public function hiringRequest()
    {
        return $this->belongsTo(HiringRequest::class);
    }


    public function jobApplicaions()
    {
        return $this->hasMany(AppliedJob::class, 'post_job_id');
    }


}
