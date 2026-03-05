<?php

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class WelcomeUserMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public User $user;
    public string $password;
    public string $roleName;
    public array $productNames;
    public string $loginUrl;

    /**
     * Create a new message instance.
     */
    public function __construct(User $user, string $password, string $roleName, array $productNames)
    {
        $this->user = $user;
        $this->password = $password;
        $this->roleName = $roleName;
        $this->productNames = $productNames;
        $this->loginUrl = config('app.url') . '/login';
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Welcome to Vitalytics - Your Account Details',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        // Build dashboard access string
        $dashboardAccess = [];
        if ($this->user->has_health_access) {
            $dashboardAccess[] = 'Health';
        }
        if ($this->user->has_analytics_access) {
            $dashboardAccess[] = 'Analytics';
        }

        return new Content(
            markdown: 'emails.welcome-user',
            with: [
                'user' => $this->user,
                'password' => $this->password,
                'roleName' => $this->roleName,
                'productNames' => $this->productNames,
                'dashboardAccess' => $dashboardAccess,
                'loginUrl' => $this->loginUrl,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
