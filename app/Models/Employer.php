<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Employer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'profile_picture',
        'company_name',
        'industry',
        'website',
        'company_size',
        'business_location',
        'years_in_operation',
        'company_description',
        'social_links',
        'designation',
        'bio',
        'preferred_contact_time',
        'preferred_contact_via',
        'hired_before',
    ];

protected $casts = [
    'social_links' => 'array',
];

    protected $appends = [
        'name',
        'email', // <-- Add this

    ];
    public function getNameAttribute()
    {
        return $this->user->name ?? null;
    }

    public function getEmailAttribute()
    {
        return $this->user->email ?? null;
    }

    // Define the inverse relationship with User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
