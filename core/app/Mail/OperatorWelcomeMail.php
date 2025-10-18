<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OperatorWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public $credentials;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($credentials)
    {
        $this->credentials = $credentials;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Welcome to ' . env('APP_NAME') . ' - Your Operator Account is Ready!')
            ->html($this->getHtmlContent())
            ->with('credentials', $this->credentials);
    }

    private function getHtmlContent()
    {
        return '
        <!DOCTYPE html>
        <html>
        <head>
            <title>Welcome to ' . env('APP_NAME') . '</title>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { color: #007bff; font-size: 24px; margin-bottom: 20px; }
                .credentials { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #007bff; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">Welcome to ' . env('APP_NAME') . '!</div>
                <p>Dear <strong>' . $this->credentials['name'] . '</strong>,</p>
                <p>Your operator account has been successfully created. Here are your login credentials:</p>
                <div class="credentials">
                    <p><strong>Login URL:</strong> <a href="' . $this->credentials['login_url'] . '">' . $this->credentials['login_url'] . '</a></p>
                    <p><strong>Email:</strong> ' . $this->credentials['email'] . '</p>
                    <p><strong>Password:</strong> ' . $this->credentials['password'] . '</p>
                </div>
                <p>Please change your password after first login for security.</p>
                <p>Best regards,<br>The ' . env('APP_NAME') . ' Team</p>
            </div>
        </body>
        </html>';
    }
}