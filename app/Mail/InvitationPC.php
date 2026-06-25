<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class InvitationPC extends Mailable
{
    use Queueable, SerializesModels;

    public string $loginUrl;

    public function __construct(
        public string $name,
        public string $username,
        public string $password,
    ) {
        $this->loginUrl = config('app.url') . '/login';
    }

    public function build(): static
    {
        return $this
            ->subject('Your CFIP Programme Coordinator Account — Login Credentials')
            ->view('emails.invitation_pc');
    }
}
