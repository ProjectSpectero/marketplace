<?php


namespace App\Mail;

use App\User;

class WelcomeWithEmailValidation extends BaseMail
{

    private $user;
    private $url;
    private $easy;

    public function __construct(User $user, string $url, bool $easy = false)
    {
        $this->user = $user;
        $this->url = $url;
        $this->easy = $easy;
    }

    public function build()
    {
        return $this->subject($this->formatTitle('Welcome!'))
            ->view('emails.WelcomeWithEmailValidation', [
                'url' => $this->url,
                'easy' => $this->easy
            ]);
    }
}