<script setup>
import { ref } from 'vue';
import { router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Swal from 'sweetalert2'; // Importamos SweetAlert2

const props = defineProps({
    ordenes: Array,
    proveedores: Array,
    sucursales: Array,
});

// Estado para el Modal de Detalles
const showModal = ref(false);
const ordenSeleccionada = ref(null);

// Función para formatear plata
const formatearDinero = (monto) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(monto);
};

// Función para formatear fecha
const formatearFecha = (fecha) => {
    if (!fecha) return '-';
    return new Date(fecha).toLocaleDateString('es-AR');
};

// Acciones principales
const generarSugerencias = () => {
    Swal.fire({
        title: '¿Generar sugerencias?',
        text: "El sistema analizará el stock y armará los pedidos automáticamente.",
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Sí, generar',
        cancelButtonText: 'Cancelar',
        confirmButtonColor: '#4f46e5',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('ordenes-compra.sugerencias'), {}, { preserveScroll: true });
        }
    });
};

// --- PASO 1: ACEPTAR COTIZACIÓN (Cambia a Aprobada) ---
const aceptarCotizacion = (orden) => {
    Swal.fire({
        title: '¿Aceptar esta cotización?',
        text: "Se le enviará un correo al proveedor confirmando que aceptás sus precios y cantidades.",
        icon: 'info',
        showCancelButton: true,
        confirmButtonText: 'Sí, confirmar pedido',
        cancelButtonText: 'Volver',
        confirmButtonColor: '#4f46e5',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('ordenes-compra.confirmar', orden.id), {}, {
                preserveScroll: true,
                onSuccess: () => {
                    cerrarModal();
                    Swal.fire('¡Confirmado!', 'Pedido enviado al proveedor.', 'success');
                }
            });
        }
    });
};

// --- PASO 2: RECIBIR MERCADERÍA (Suma Stock y genera Remito) ---
const registrarRecepcion = (orden) => {
    Swal.fire({
        title: '¿Llegó la mercadería?',
        text: "Al confirmar, el stock físico se sumará automáticamente y se generará el remito en el historial.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Sí, recibir stock',
        cancelButtonText: 'No todavía',
        confirmButtonColor: '#10b981',
    }).then((result) => {
        if (result.isConfirmed) {
            router.post(route('ordenes-compra.aprobar', orden.id), {}, { 
                preserveScroll: true,
                onSuccess: () => cerrarModal() 
            });
        }
    });
};

const eliminarOrden = (orden) => {
    Swal.fire({
        title: '¿Eliminar orden?',
        text: "Esta acción no se puede deshacer.",
        icon: 'error',
        showCancelButton: true,
        confirmButtonText: 'Sí, eliminar',
        confirmButtonColor: '#e11d48',
    }).then((result) => {
        if (result.isConfirmed) {
            router.delete(route('ordenes-compra.destroy', orden.id), { preserveScroll: true });
        }
    });
};

// Manejo del Modal
const abrirDetalles = (orden) => {
    ordenSeleccionada.value = orden;
    showModal.value = true;
};

const cerrarModal = () => {
    showModal.value = false;
    ordenSeleccionada.value = null;
};

// Clases dinámicas para los "Badges" de estado
const badgeClases = (estado) => {
    const clases = {
        'Sugerida': 'bg-slate-100 text-slate-600',
        'Borrador': 'bg-amber-100 text-amber-700',
        'Enviada': 'bg-sky-100 text-sky-700',
        'Cotizada': 'bg-purple-100 text-purple-700 font-black ring-2 ring-purple-300 ring-offset-1',
        'Aprobada': 'bg-indigo-600 text-white shadow-lg shadow-indigo-600/30', // Nuevo estado
        'Recepcionada': 'bg-emerald-100 text-emerald-700',
        'Cancelada': 'bg-rose-100 text-rose-700',
    };
    return clases[estado] || 'bg-slate-100 text-slate-600';
};
</script>

<template>
    <Head title="Órdenes de Compra" />

    <AuthenticatedLayout>
        <div class="py-8 px-4 sm:px-6 lg:px-8 max-w-7xl mx-auto bg-slate-50 min-h-screen">
            
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-3xl font-black text-slate-800 tracking-tight">Órdenes de Compra</h1>
                    <p class="text-slate-500 font-medium text-sm mt-1">Gestión y reposición de mercadería a proveedores</p>
                </div>
                
                <button @click="generarSugerencias" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-3 rounded-2xl font-black text-sm shadow-xl shadow-indigo-600/30 transition-all flex items-center gap-2 group">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 group-hover:animate-pulse" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 10V3L4 14h7v7l9-11h-7z" />
                    </svg>
                    Generar Sugerencias (Automático)
                </button>
            </div>

            <div class="bg-white border border-slate-200 shadow-xl shadow-slate-200/40 rounded-3xl overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="py-4 px-6 text-[10px] font-black tracking-widest uppercase text-slate-400">ID / Fecha</th>
                            <th class="py-4 px-6 text-[10px] font-black tracking-widest uppercase text-slate-400">Proveedor</th>
                            <th class="py-4 px-6 text-[10px] font-black tracking-widest uppercase text-slate-400">Estado</th>
                            <th class="py-4 px-6 text-[10px] font-black tracking-widest uppercase text-slate-400 text-right">Total Est.</th>
                            <th class="py-4 px-6 text-[10px] font-black tracking-widest uppercase text-slate-400 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <tr v-for="orden in ordenes" :key="orden.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="py-4 px-6">
                                <div class="font-black text-slate-700">#OC-{{ orden.id.toString().padStart(4, '0') }}</div>
                                <div class="text-xs font-bold text-slate-400">{{ formatearFecha(orden.fecha_emision) }}</div>
                            </td>
                            <td class="py-4 px-6 font-bold text-slate-700">{{ orden.proveedor?.razon_social }}</td>
                            <td class="py-4 px-6">
                                <span :class="badgeClases(orden.estado)" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest">
                                    {{ orden.estado }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right font-black text-slate-800">{{ formatearDinero(orden.total_estimado) }}</td>
                            <td class="py-4 px-6 flex justify-center gap-2">
                                <button @click="abrirDetalles(orden)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-sky-100 hover:text-sky-600 transition-colors">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                </button>
                                <button v-if="!['Enviada', 'Cotizada', 'Aprobada', 'Recepcionada'].includes(orden.estado)" @click="eliminarOrden(orden)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-rose-100 hover:text-rose-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
                <div class="bg-white rounded-3xl w-full max-w-4xl shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200 flex flex-col max-h-[90vh]">
                    
                    <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                        <div>
                            <h3 class="font-black text-xl text-slate-800 flex items-center gap-3">
                                Detalle de Orden #OC-{{ ordenSeleccionada?.id.toString().padStart(4, '0') }}
                                <span :class="badgeClases(ordenSeleccionada?.estado)" class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-widest align-middle">
                                    {{ ordenSeleccionada?.estado }}
                                </span>
                            </h3>
                            <p class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest">{{ ordenSeleccionada?.proveedor?.razon_social }}</p>
                        </div>
                        <button @click="cerrarModal" class="w-8 h-8 flex items-center justify-center rounded-full bg-slate-200 text-slate-600 hover:bg-slate-300 transition-colors">✕</button>
                    </div>

                    <div class="p-6 overflow-y-auto flex-1 bg-slate-50">
                        <div v-if="ordenSeleccionada?.fecha_entrega_esperada" class="mb-4 bg-sky-50 text-sky-800 p-4 rounded-xl border border-sky-100 flex items-center gap-3">
                            <svg class="w-6 h-6 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            <div>
                                <p class="text-[10px] uppercase font-black tracking-widest text-sky-600">Fecha de Entrega Confirmada</p>
                                <p class="font-bold">{{ formatearFecha(ordenSeleccionada.fecha_entrega_esperada) }}</p>
                            </div>
                        </div>

                        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
                            <table class="w-full text-left">
                                <thead>
                                    <tr class="bg-slate-100 border-b">
                                        <th class="py-3 px-4 text-[10px] font-black uppercase text-slate-500">Producto</th>
                                        <th class="py-3 px-4 text-[10px] font-black uppercase text-center text-slate-500">Cant.</th>
                                        <th class="py-3 px-4 text-[10px] font-black uppercase text-right text-slate-500">Costo</th>
                                        <th class="py-3 px-4 text-[10px] font-black uppercase text-right text-slate-500">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="detalle in ordenSeleccionada?.detalles" :key="detalle.id" class="border-b border-slate-100">
                                        <td class="py-3 px-4 font-bold text-slate-700">{{ detalle.producto?.nombre }}</td>
                                        <td class="py-3 px-4 text-center">
                                            <span class="bg-sky-100 text-sky-700 px-2 py-1 rounded-lg font-black text-sm">{{ detalle.cantidad_pedida }}</span>
                                        </td>
                                        <td class="py-3 px-4 text-right text-slate-500 font-medium">{{ formatearDinero(detalle.costo_unitario_estimado) }}</td>
                                        <td class="py-3 px-4 text-right font-black text-slate-800">{{ formatearDinero(detalle.subtotal_estimado) }}</td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-slate-50 border-t-2 border-slate-200">
                                        <td colspan="3" class="py-4 px-4 text-right font-black text-slate-500 uppercase tracking-widest text-xs">Total de la Orden:</td>
                                        <td class="py-4 px-4 text-right font-black text-xl text-slate-900">{{ formatearDinero(ordenSeleccionada?.total_estimado) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    
                    <div class="p-6 bg-white border-t border-slate-100 flex justify-end gap-3">
                        <button v-if="ordenSeleccionada?.estado === 'Cotizada'" 
                            @click="aceptarCotizacion(ordenSeleccionada)" 
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3 px-6 rounded-xl shadow-lg flex items-center gap-2 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            Aceptar Cotización y Pedir
                        </button>

                        <button v-if="ordenSeleccionada?.estado === 'Aprobada'" 
                            @click="registrarRecepcion(ordenSeleccionada)" 
                            class="bg-emerald-500 hover:bg-emerald-600 text-white font-black py-3 px-6 rounded-xl shadow-lg flex items-center gap-2 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"></path></svg>
                            Registrar Recepción de Camión
                        </button>
                    </div>

                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>