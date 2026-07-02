<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmacion por correo al ciudadano cuando su participacion (una o varias
 * observaciones enviadas juntas) quedo registrada. Implementa ShouldQueue para
 * no bloquear la respuesta HTTP. Todas las observaciones del envio comparten
 * identidad y consulta, asi que el primer elemento basta para los datos comunes.
 */
class ObservationSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Collection $observations)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recibimos tu participacion - ' . $this->observations->first()->consultation->title,
        );
    }

    public function content(): Content
    {
        $first = $this->observations->first();

        return new Content(
            markdown: 'emails.observations.submitted',
            with: [
                'observations' => $this->observations,
                'consultation' => $first->consultation,
                // PJ/Org no tienen nombre de persona; usamos la razon social.
                'author' => $first->display_name,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
