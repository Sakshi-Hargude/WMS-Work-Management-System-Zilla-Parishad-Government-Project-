<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class UserRegistered extends Mailable
{
    use Queueable, SerializesModels;

    public $username;
    public $password;

    public $email;
    public $mobileno;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($username, $password,  $email,$mobileno )
    {
        $this->username = $username;
        $this->password = $password;
        $this->mobileno = $mobileno;  // Mobile number
        $this->email = $email;
        //
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome to Our Service')
                    ->view('Email.user_registered')
                    ->with([
                        'username' => $this->username,
                        'email' => $this->email,
                        'mobileno' => $this->mobileno,
                        'password' => $this->password,
                    ]);


    }
}
