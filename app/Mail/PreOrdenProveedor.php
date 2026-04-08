<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PreOrdenProveedor extends Mailable
{
    use Queueable, SerializesModels;

    public $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud de Cotización - VendAR',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.proveedores.preorden',
        );
    }
}