<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon; // Import Carbon to work with dates

class TokenBlacklist extends Model
{
    protected $fillable = [
        'token',
        'user_id',
        'user_type',
        'date',
    ];

    /**
     * The "booting" method of the model.
     *
     * Here we will automatically set the `date` field to the current date
     * when a new `TokenBlacklist` is being created.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($tokenBlacklist) {
            // Set the `date` field to the current date/time
            $tokenBlacklist->date = Carbon::now(); // Current date and time
        });
    }
}
