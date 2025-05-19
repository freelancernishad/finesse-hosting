<?php

namespace App\Models;


use Illuminate\Support\Facades\Log;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
{
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'active_profile',
        'profile_picture',
        'password',
        'email_verified_at',
        'email_verification_hash',
        'otp',
        'otp_expires_at',




        // Address fields
        'country',
        'state',
        'city',
        'region',
        'street_address',
        'zip_code',
        'full_address',

        // 'phone',
        // 'business_name',
        // 'country',
        // 'state',
        // 'city',
        // 'region',
        // 'zip_code',
        // 'stripe_customer_id'


    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'email_verification_hash',
        'email_verified_at',
        'otp',
        'otp_expires_at',
        'stripe_customer_id',
        'business_name'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];


public function getProfileCompletionAttribute(): int
{
    $filled = 0;
    $fields = [];

    // Common user fields
    $userFields = [
        'name',
        'email',
        'profile_picture',
        'street_address',
        'city',
        'state',
        'country',
    ];

    if ($this->active_profile == 'Employer') {
        $employerFields = [
            'company_name',
            'industry',
            'website',
            'company_size',
            'business_location',
            'years_in_operation',
            'company_description',
            'designation',
            'bio',
            'preferred_contact_time',
            'preferred_contact_via',
            'hired_before'
        ];

        foreach ($userFields as $field) {
            if (!empty($this->{$field})) {
                $filled++;
            }
        }

        foreach ($employerFields as $field) {
            if (!empty($this->employer->{$field})) {
                $filled++;
            }
        }

        $fields = array_merge($userFields, $employerFields);

    } elseif ($this->active_profile == 'JobSeeker') {
        $jobSeekerFields = [
            'phone_number',
            'resume',
            'location',
            'post_code',
            'join_date'
        ];

        foreach ($userFields as $field) {
            if (!empty($this->{$field})) {
                $filled++;
            }
        }

        foreach ($jobSeekerFields as $field) {
            if (!empty($this->jobSeeker->{$field})) {
                $filled++;
            }
        }

        $fields = array_merge($userFields, $jobSeekerFields);
    }

    return count($fields) ? intval(($filled / count($fields)) * 100) : 0;
}



    public static function booted()
    {
        static::saving(function ($user) {
            $user->full_address = trim(collect([
                $user->street_address,
                $user->region,
                $user->city,
                $user->state,
                $user->zip_code,
                $user->country
            ])->filter()->implode(', '));
        });
    }



    public function jobSeeker()
    {
        return $this->hasOne(JobSeeker::class);
    }

    public function employer()
    {
        return $this->hasOne(Employer::class);
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
            'active_profile' => $this->active_profile,
        ];
    }


    public function userPackage()
    {
        return $this->hasOne(UserPackage::class);
    }

    public function userPackagePackagesHistory()
    {
        return $this->hasMany(UserPackage::class);
    }

    public function currentPackage()
    {
        return $this->userPackage ? $this->userPackage->package : null;
    }

    public function hasFeature($feature)
    {
        $package = $this->currentPackage();
        return $package && in_array($feature, $package->features);
    }


    public function saveProfilePicture($file)
    {
        $filePath = uploadFileToS3($file, 'profile_pictures'); // Define the S3 directory
        $this->profile_picture = $filePath;
        $this->save();

        return $filePath;
    }



    public function getBusinessNameAttribute($value)
    {
        return json_decode($value, true) ?? [];
    }

    public function setBusinessNameAttribute($value)
    {
        $this->attributes['business_name'] = $value;
    }

        public function hiringRequests()
    {
        return $this->hasMany(HiringRequest::class);
    }


}
