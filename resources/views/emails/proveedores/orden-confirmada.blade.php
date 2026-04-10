<x-mail::message>
# ¡Pedido Confirmado!

Hola, **{{ $orden->proveedor->razon_social }}**.

Le informamos que hemos aceptado la cotización enviada y confirmamos el pedido.
Quedamos a la espera de la mercadería según la fecha pactada: **{{ \Carbon\Carbon::parse($orden->fecha_entrega_esperada)->format('d/m/Y') }}**.

**Resumen del Pedido:**
- **N° de Orden:** #OC-{{ str_pad($orden->id, 4, '0', STR_PAD_LEFT) }}
- **Total:** ${{ number_format($orden->total_estimado, 2, ',', '.') }}

Gracias por su servicio,<br>
{{ config('app.name') }}
</x-mail::message>