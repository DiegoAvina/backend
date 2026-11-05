<?php

namespace App\Listeners;

use App\Enums\SettingKey;
use App\Events\LeadSubmissionReceived;
use App\Notifications\NewLeadSubmission;
use App\Services\Settings\SiteSettings;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

/**
 * Envía el aviso por correo al equipo comercial, en cola.
 * No hace nada si las notificaciones están desactivadas o no hay destinatarios.
 */
class NotifyCommercialTeam implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private SiteSettings $settings) {}

    public function shouldQueue(LeadSubmissionReceived $event): bool
    {
        return (bool) config('leads.notifications.enabled') && $this->recipients() !== [];
    }

    public function handle(LeadSubmissionReceived $event): void
    {
        $recipients = $this->recipients();

        if (! config('leads.notifications.enabled') || $recipients === []) {
            return;
        }

        Notification::route('mail', $recipients)->notify(new NewLeadSubmission($event->record));
    }

    /**
     * @return list<string>
     */
    public function recipients(): array
    {
        $configured = $this->settings->get(SettingKey::NotificationRecipients)
            ?? (string) config('leads.notifications.recipients');

        return array_values(array_filter(
            array_map('trim', preg_split('/[,;\s]+/', $configured) ?: []),
            fn (string $email): bool => filter_var($email, FILTER_VALIDATE_EMAIL) !== false,
        ));
    }
}
