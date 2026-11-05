<?php

namespace App\Notifications;

use App\Models\ContactMessage;
use App\Models\DemoRequest;
use App\Models\DiagnosticRequest;
use App\Models\Lead;
use App\Models\QuoteRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso al equipo comercial. Incluye solo lo necesario para atender la
 * solicitud (tipo, nombre y empresa) y un enlace al panel; el resto de los
 * datos personales se consulta en el panel, con autenticación.
 */
class NewLeadSubmission extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Lead|ContactMessage|QuoteRequest|DemoRequest|DiagnosticRequest $record,
    ) {}

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $lead = $this->record instanceof Lead ? $this->record : $this->record->lead;

        return (new MailMessage)
            ->subject($this->typeLabel().' · '.config('app.name'))
            ->greeting($this->typeLabel())
            ->line('Llegó una nueva solicitud desde el sitio web.')
            ->line('Nombre: '.$lead->name)
            ->lineIf(filled($lead->company), 'Empresa: '.$lead->company)
            ->action('Ver en el panel', url('/admin'))
            ->line('Por seguridad, los datos completos se consultan en el panel.');
    }

    public function typeLabel(): string
    {
        return match (true) {
            $this->record instanceof QuoteRequest => 'Nueva solicitud de cotización',
            $this->record instanceof DemoRequest => 'Nueva solicitud de demostración',
            $this->record instanceof DiagnosticRequest => 'Nueva solicitud de diagnóstico',
            $this->record instanceof ContactMessage => 'Nuevo mensaje de contacto',
            default => 'Nuevo prospecto',
        };
    }
}
