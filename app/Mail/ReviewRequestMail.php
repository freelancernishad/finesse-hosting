<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use App\Models\RequestQuote;

class ReviewRequestMail extends Mailable
{
    use Queueable, SerializesModels;

    public $requestQuote;

    public function __construct(RequestQuote $requestQuote)
    {
        $this->requestQuote = $requestQuote;
    }

    public function build()
    {
        return $this->subject('Review Your Job Seekers')
                    ->view('emails.review_request')
                    ->with([
                        'name' => $this->requestQuote->name,
                        'jobSeekers' => $this->requestQuote->jobSeekers,
                    ]);
    }
}
