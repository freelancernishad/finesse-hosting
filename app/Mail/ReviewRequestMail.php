<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\HiringRequest;

class ReviewRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $HiringRequest;

    public function __construct(HiringRequest $HiringRequest)
    {
        $this->HiringRequest = $HiringRequest;
    }

    public function build()
    {
        return $this->subject('Review Your Job Seekers')
                    ->view('emails.review_request')
                    ->with([
                        'name' => $this->HiringRequest->name,
                        'jobSeekers' => $this->HiringRequest->jobSeekers,
                    ]);
    }
}
