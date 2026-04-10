<?php

namespace App\Mail;

use App\Models\OrdenCompra;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class OrdenConfirmadaProveedor extends Mailable
{
    use Queueable, SerializesModels;

    public $orden;

    public function __construct(OrdenCompra $orden)
    {
        $this->orden = $orden;
    }

    public function build()
    {
        return $this->markdown('emails.proveedores.orden-confirmada')
                    ->subject('Confirmación de Pedido - #OC-' . str_pad($this->orden->id, 4, '0', STR_PAD_LEFT));
    }
}