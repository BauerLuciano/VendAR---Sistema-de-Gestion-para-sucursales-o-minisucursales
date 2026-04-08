<x-mail::message>
# ¡Hola, {{ $orden->proveedor->razon_social }}!

Tenemos una nueva solicitud de reposición de mercadería para usted.

Por favor, revise los productos solicitados y envíenos su cotización o confirmación del pedido.

**Detalles de la Orden:**
- **N° de Orden:** #OC-{{ str_pad($orden->id, 4, '0', STR_PAD_LEFT) }}
- **Sucursal:** {{ $orden->sucursal->nombre }}
- **Cantidad de Ítems:** {{ $orden->detalles->count() }}

<x-mail::button :url="url('/reposicion')">
Ver y Cotizar Pedido
</x-mail::button>

*(Nota: Este botón por ahora te lleva al sistema, luego le pondremos el Link Mágico de cotización).*

Gracias,<br>
{{ config('app.name') }}
</x-mail::message>