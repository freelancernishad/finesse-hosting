<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HiringConsultationRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'full_name',
        'email',
        'phone_number',
        'company_name',
        'industry_sector',
        'company_size',
        'company_description',
        'hiring_needs',
        'number_of_positions',
        'hiring_urgency',
        'preferred_consultation_date',
        'additional_info',
        'status',
    ];

    protected $casts = [
        'preferred_consultation_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
