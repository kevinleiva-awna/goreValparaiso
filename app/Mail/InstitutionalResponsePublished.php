<?php

namespace App\Mail;

use App\Models\InstitutionalResponse;
use App\Models\Observation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Notifica al ciudadano que su observacion recibio una respuesta
 * institucional publicada. Se envia al snapshot_email de la observacion
 * (no al email actual del usuario, por inmutabilidad de la trazabilidad).
 */
class InstitutionalResponsePublished extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Observation $observation,
        public InstitutionalResponse $response,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Respuesta a tu observacion - ' . $this->observation->consultation->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.institutional-responses.published',
            with: [
                'observation' => $this->observation,
                'response' => $this->response,
                'consultation' => $this->observation->consultation,
            ],
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
