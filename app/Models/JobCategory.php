<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class JobCategory extends Model
{
    use HasFactory;




    // Fillable fields for mass assignment
    protected $fillable = [
        'category_id', // Explicitly defining the custom primary key
        'name',
        'status',
    ];


    // Relationship with AppliedJob
    public function appliedJobs()
    {
        return $this->hasMany(AppliedJob::class, 'job_category_id');
    }

    /**
     * Boot method to generate a unique category_id before saving.
     */
    protected static function booted()
    {
        static::creating(function ($jobCategory) {
            // Generate a unique category_id (using UUID or any other unique method)
            $jobCategory->category_id = (string) Str::uuid();
        });
    }
}
