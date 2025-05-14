<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AppliedJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'phone', 'email', 'date_of_birth', 'country', 'city',
        'post_code', 'address', 'interest_file', 'area', 'category', 'job_seeker_id',
        'status', 'review_comments', 'admin_id', 'unique_job_apply_id', 'job_category_id','post_job_id',
            'describe_yourself', 'resume', 'cover_letter', 'experience',
    'preferred_contact_method', 'on_call_status'
    ];

    protected $hidden = [
        'unique_job_apply_id',
    ];

    // Ensure 'area' is cast to array when accessed
    protected $casts = [
        'area' => 'array',
    ];


    // Automatically generate a unique job apply ID on creation
    protected static function booted()
    {
        static::creating(function ($appliedJob) {
            $appliedJob->unique_job_apply_id = 'JOB-' . strtoupper(Str::random(10)) . '-' . time();
        });
    }

    // Job Seeker relationship
    public function jobSeeker()
    {
        return $this->belongsTo(JobSeeker::class, 'job_seeker_id');
    }

    // Admin relationship
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    // Relationship with JobCategory
    public function jobCategory()
    {
        return $this->belongsTo(JobCategory::class, 'job_category_id');
    }

    /**
     * Save the interest file to a specific folder in S3.
     */
    public function saveInterestFile($file)
    {
        $folderPath = $this->job_seeker_id; // Use job_seeker_id as the folder name

        $filePath = uploadFileToS3($file, 'interest_files/' . $folderPath); // Define the S3 directory with job_seeker_id
        $this->interest_file = $filePath;
        $this->save();

        return $filePath;
    }


    public function postJob()
    {
        return $this->belongsTo(PostJob::class, 'post_job_id');
    }
}
