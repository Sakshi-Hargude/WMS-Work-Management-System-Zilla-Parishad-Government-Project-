<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoginNotification extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public $user;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
{
    return $this
        ->subject('Login Notification')   // Set the email subject
        ->view('Email.loginnotification') // Specify the view
        ->with([                          // Pass data to the view
            'user' => $this->user,        // Passing user data to the view
        ]);
}


     /**
     * Get the message content definition.
     *
     * @return \Illuminate\Mail\Mailables\Content
     */
    // public function content(): Content
    //     {
    //         return new Content(
    //             view: 'Email.loginnotification', // Correctly specifying the view
    //             with: [
    //                 'user' => $this->user, // Passing data to the view
    //             ]
    //         );
    //     }

}
