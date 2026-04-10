<x-mail::message>
# ¡Hola, {{ $orden->proveedor->razon_social }}!

Tenemos una nueva solicitud de reposición de mercadería para usted.

Por favor, revise los productos solicitados y envíenos su cotización o confirmación del pedido.

**Detalles de la Orden:**
- **N° de Orden:** #OC-{{ str_pad($orden->id, 4, '0', STR_PAD_LEFT) }}
- **Sucursal:** {{ $orden->sucursal->nombre }}
- **Cantidad de Ítems:** {{ $orden->detalles->count() }}

<x-mail::button :url="url('/cotizar/' . $orden->id . '?token=' . $orden->token_cotizacion)">
Ver y Cotizar Pedido
</x-mail::button>

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>