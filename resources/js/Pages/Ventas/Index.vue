<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';
import ModalDetalleVenta from './Componentes/ModalDetalleVenta.vue'; 

const props = defineProps({
    ventas: Array
});

const verDetalle = ref(false);
const ventaSeleccionada = ref(null);

const formatearFecha = (fecha) => {
    return new Date(fecha).toLocaleString('es-AR', {
        day: '2-digit', month: '2-digit', year: 'numeric',
        hour: '2-digit', minute: '2-digit', hour12: false
    });
};

const abrirDetalle = (v) => {
    ventaSeleccionada.value = v;
    verDetalle.value = true;
};

const realizarAnulacion = (id, motivoFinal) => {
    router.post(route('ventas.cancelar', id), {
        motivo: motivoFinal
    }, {
        onSuccess: () => {
            Swal.fire({
                title: '¡HECHO!',
                text: 'La venta se anuló, el stock se repuso al inventario y la caja se ajustó.',
                icon: 'success',
                confirmButtonColor: '#0284c7',
            });
        }
    });
};

const confirmarAnulacion = (v) => {
    Swal.fire({
        title: '¿CONFIRMAR ANULACIÓN?',
        text: `Se procederá a anular la venta #${v.id.toString().padStart(6, '0')}. Esta acción reintegrará el stock al inventario y revertirá los saldos de caja o cuenta corriente correspondientes.`,
        icon: 'warning',
        input: 'select',
        inputOptions: {
            'Error en la carga de productos': 'Error en la carga de productos',
            'El cliente desistió de la compra': 'El cliente desistió de la compra',
            'Error en el método de pago': 'Error en el método de pago',
            'Carga duplicada': 'Carga duplicada',
            'otro': 'Otro motivo (especificar)...'
        },
        inputPlaceholder: 'Seleccione el motivo de la anulación',
        showCancelButton: true,
        confirmButtonColor: '#ef4444', // Rojo para advertencia
        cancelButtonColor: '#6b7280', // Gris neutro
        confirmButtonText: 'Sí, confirmar anulación',
        cancelButtonText: 'Cancelar',
        inputValidator: (value) => {
            if (!value) {
                return 'Debe seleccionar un motivo para continuar';
            }
        }
    }).then((result) => {
        if (result.isConfirmed) {
            if (result.value === 'otro') {
                // Paso 2: Si elige "otro", pide el detalle
                Swal.fire({
                    title: 'Detalle del motivo',
                    input: 'text',
                    inputPlaceholder: 'Explique brevemente la razón...',
                    showCancelButton: true,
                    confirmButtonText: 'Finalizar Anulación',
                    confirmButtonColor: '#ef4444',
                    cancelButtonText: 'Volver',
                    inputValidator: (value) => {
                        if (!value) {
                            return 'El detalle es obligatorio para este motivo';
                        }
                    }
                }).then((subResult) => {
                    if (subResult.isConfirmed) {
                        realizarAnulacion(v.id, subResult.value);
                    }
                });
            } else {
                // Procesa con la opción elegida del select
                realizarAnulacion(v.id, result.value);
            }
        }
    });
};
</script>

<template>
    <Head title="Historial de Ventas" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-screen">
            
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Historial de Ventas</h1>
                    <div class="h-1 w-12 bg-sky-500 mt-1"></div>
                </div>
                <Link :href="route('pos.index')" class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-6 rounded-xl shadow-lg hover:-translate-y-0.5 transition-all active:scale-95 flex items-center gap-2 text-sm uppercase tracking-widest">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                    Nueva Venta (POS)
                </Link>
            </div>

            <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-slate-100 p-4">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-separate border-spacing-y-2">
                        <thead>
                            <tr class="bg-sky-50 text-sky-900 uppercase text-[10px] font-black tracking-widest">
                                <th class="p-4 rounded-l-xl text-center">N° Ticket</th>
                                <th class="p-4">Fecha y Hora</th>
                                <th class="p-4">Cliente</th>
                                <th class="p-4 text-center">Estado</th>
                                <th class="p-4 text-right">Monto Total</th>
                                <th class="p-4 text-center rounded-r-xl">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="v in ventas" :key="v.id" 
                                class="bg-white border-b hover:bg-sky-50 transition-all duration-200 group shadow-sm"
                                :class="{'opacity-60 grayscale-[0.5]': v.estado === 'anulada'}"
                            >
                                <td class="p-4 text-center font-mono font-bold text-sky-800">
                                    #{{ v.id.toString().padStart(6, '0') }}
                                </td>
                                <td class="p-4 text-slate-600 font-medium">
                                    {{ formatearFecha(v.created_at) }}
                                </td>
                                <td class="p-4 font-bold text-slate-700">
                                    {{ v.consumidor?.nombre || 'Consumidor Final' }}
                                </td>
                                <td class="p-4 text-center">
                                    <span 
                                        :class="v.estado === 'activa' ? 'bg-emerald-100 text-emerald-700 border-emerald-200' : 'bg-rose-100 text-rose-700 border-rose-200'"
                                        class="px-3 py-1 rounded-full text-[9px] font-black uppercase tracking-widest border shadow-sm"
                                    >
                                        {{ v.estado }}
                                    </span>
                                </td>
                                <td class="p-4 text-right font-black text-slate-900 font-mono text-lg">
                                    ${{ v.total }}
                                </td>
                                <td class="p-4">
                                    <div class="flex justify-center gap-3">
                                        <button @click="abrirDetalle(v)" class="bg-sky-100 text-sky-600 p-2 rounded-xl hover:bg-sky-600 hover:text-white transition-all shadow-sm group/btn" title="Ver Detalle de Venta">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                        </button>

                                        <button v-if="v.estado === 'activa'" @click="confirmarAnulacion(v)" class="bg-rose-100 text-rose-600 p-2 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm" title="Anular Venta">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                        <div v-else class="p-2 text-slate-300" title="Venta ya anulada">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <ModalDetalleVenta 
            :mostrar="verDetalle" 
            :venta="ventaSeleccionada" 
            @cerrar="verDetalle = false" 
        />
    </AuthenticatedLayout>
</template>