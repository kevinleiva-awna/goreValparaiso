<?php

namespace App\Mail;

use App\Models\Observation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Confirmacion por correo al ciudadano cuando su observacion fue
 * registrada con exito. Implementa ShouldQueue para no bloquear
 * la respuesta HTTP.
 */
class ObservationSubmitted extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Observation $observation)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Recibimos tu observacion - ' . $this->observation->consultation->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.observations.submitted',
            with: [
                'observation' => $this->observation,
                'consultation' => $this->observation->consultation,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
