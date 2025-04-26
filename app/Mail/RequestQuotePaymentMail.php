<?php

namespace App\Mail;

use App\Models\HiringRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class HiringRequestPaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $HiringRequest;
    public $paymentLink;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(HiringRequest $HiringRequest, $paymentLink)
    {
        $this->HiringRequest = $HiringRequest;
        $this->paymentLink = $paymentLink;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->from('support@yourcompany.com', 'Your Company Name')
                    ->subject('Confirm Your HiringRequest - Secure Payment Link')
                    ->view('emails.request_quote_payment')
                    ->with([
                        'HiringRequest' => $this->HiringRequest,
                        'paymentLink' => $this->paymentLink,
                    ]);
    }
}
