<?php

namespace App\Mail;

use App\Models\RequestQuote;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestQuotePaymentMail extends Mailable
{
    use Queueable, SerializesModels;

    public $requestQuote;
    public $paymentLink;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(RequestQuote $requestQuote, $paymentLink)
    {
        $this->requestQuote = $requestQuote;
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
                    ->subject('Confirm Your RequestQuote - Secure Payment Link')
                    ->view('emails.request_quote_payment')
                    ->with([
                        'requestQuote' => $this->requestQuote,
                        'paymentLink' => $this->paymentLink,
                    ]);
    }
}
