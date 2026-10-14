<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/** Ported from email_helper.php's send_welcome_email(). */
class WelcomeEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public User $user, public string $loginUrl)
    {
    }

    public function build()
    {
        return $this->subject('Welcome to '.config('app.name').'!')
            ->markdown('emails.welcome');
    }
}
