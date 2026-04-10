<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    orden: Object,
});

// Calculamos la fecha de hoy en formato YYYY-MM-DD para bloquear fechas anteriores
const hoy = new Date();
const fechaMinima = hoy.toISOString().split('T')[0];

const form = useForm({
    token: new URLSearchParams(window.location.search).get('token'),
    fecha_entrega: '', 
    detalles: props.orden.detalles.map(detalle => ({
        id: detalle.id,
        producto: detalle.producto.nombre,
        codigo_barras: detalle.producto.codigo_barras,
        cantidad_original: detalle.cantidad_pedida,
        cantidad_pedida: detalle.cantidad_pedida,
        costo_unitario_estimado: detalle.costo_unitario_estimado
    }))
});

const totalCotizacion = computed(() => {
    return form.detalles.reduce((total, item) => total + (item.cantidad_pedida * item.costo_unitario_estimado), 0);
});

const submitCotizacion = () => {
    form.post(route('cotizar.guardar', props.orden.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Cotización de Pedido | VendAR" />

    <div class="min-h-screen bg-slate-50 font-sans py-12 px-4 sm:px-6 lg:px-8 relative overflow-hidden">
        
        <div class="absolute top-0 w-full h-64 bg-[#0b1221] left-0 z-0 border-b border-slate-800"></div>
        <div class="absolute top-[-10%] left-[-10%] w-96 h-96 bg-sky-600/20 rounded-full mix-blend-multiply filter blur-[128px] z-0"></div>

        <div class="max-w-4xl mx-auto relative z-10 mt-4">
            
            <div class="flex flex-col items-start justify-end mb-12">
                <img src="/img/LogoVendar-Sidebar.png" alt="VendAR" class="max-w-[200px] w-auto h-auto mb-6 brightness-150 drop-shadow-md">
                <h1 class="text-4xl font-black text-white tracking-tight">Cotización de Pedido</h1>
                <p class="text-slate-300 mt-2 text-lg">N° de Orden: <span class="font-bold text-sky-400">#OC-{{ String(orden.id).padStart(4, '0') }}</span></p>
            </div>

            <div v-if="orden.estado === 'Cotizada'" class="bg-white rounded-2xl shadow-xl p-12 text-center border border-slate-100 animate-in fade-in zoom-in duration-500">
                <div class="w-20 h-20 bg-emerald-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <svg class="w-10 h-10 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <h2 class="text-2xl font-black text-slate-800 mb-2">¡Cotización Enviada Exitosamente!</h2>
                <p class="text-slate-500">Hemos registrado sus precios y fecha de entrega. El comercio se pondrá en contacto para finalizar el pedido.</p>
            </div>

            <div v-else class="bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-100">
                
                <div class="p-6 bg-slate-800 text-white flex justify-between items-center border-b border-slate-700">
                    <div>
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Proveedor</p>
                        <p class="font-bold text-lg">{{ orden.proveedor.razon_social }}</p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-400 font-bold uppercase tracking-widest">Sucursal de Entrega</p>
                        <p class="font-bold">{{ orden.sucursal.nombre }}</p>
                    </div>
                </div>

                <div class="p-8">
                    <p class="text-slate-600 mb-6">Por favor, confirme el stock disponible, los precios unitarios y la fecha en la que podría entregar este pedido.</p>

                    <form @submit.prevent="submitCotizacion">
                        
                        <div class="mb-8 p-5 bg-sky-50/50 border border-sky-100 rounded-xl flex items-center gap-4">
                            <div class="p-3 bg-sky-100 text-sky-600 rounded-lg">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            </div>
                            <div class="flex-1">
                                <label class="block text-sm font-bold text-slate-700 mb-1">Fecha Estimada de Entrega *</label>
                                <input 
                                    type="date" 
                                    :min="fechaMinima"
                                    v-model="form.fecha_entrega" 
                                    class="w-full sm:w-64 border-slate-300 rounded-lg text-sm focus:ring-sky-500 focus:border-sky-500"
                                    required
                                >
                            </div>
                        </div>

                        <div class="overflow-x-auto mb-8">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="border-b-2 border-slate-200">
                                        <th class="py-3 px-4 text-xs font-black text-slate-500 uppercase tracking-wider">Producto</th>
                                        <th class="py-3 px-4 text-xs font-black text-orange-600 uppercase tracking-wider w-40 text-center">Cant. Confirmada</th>
                                        <th class="py-3 px-4 text-xs font-black text-sky-600 uppercase tracking-wider w-40 text-center">Precio Unitario ($)</th>
                                        <th class="py-3 px-4 text-xs font-black text-slate-500 uppercase tracking-wider text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(item, index) in form.detalles" :key="item.id" class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                        <td class="py-4 px-4 align-top">
                                            <p class="font-bold text-slate-800">{{ item.producto }}</p>
                                            <p class="text-xs text-slate-500 font-mono">{{ item.codigo_barras }}</p>
                                        </td>
                                        
                                        <td class="py-4 px-4 align-top">
                                            <input 
                                                type="number" 
                                                min="0"
                                                :max="item.cantidad_original"
                                                v-model="item.cantidad_pedida" 
                                                class="w-full border-slate-300 rounded-lg text-sm focus:ring-orange-500 focus:border-orange-500 font-bold text-center text-orange-700 bg-orange-50"
                                                required
                                            >
                                            <p v-if="item.cantidad_pedida < item.cantidad_original" class="text-[10px] font-bold text-orange-600 mt-2 text-center uppercase tracking-tighter leading-tight">
                                                Solo se entregarán {{ item.cantidad_pedida }} de {{ item.cantidad_original }} solicitados.
                                            </p>
                                        </td>

                                        <td class="py-4 px-4 align-top">
                                            <input 
                                                type="number" 
                                                step="0.01"
                                                v-model="item.costo_unitario_estimado" 
                                                class="w-full border-slate-300 rounded-lg text-sm focus:ring-sky-500 focus:border-sky-500 font-bold text-center text-sky-700 bg-sky-50"
                                                required
                                            >
                                        </td>
                                        <td class="py-4 px-4 text-right font-black text-slate-700 align-top pt-6">
                                            ${{ (item.cantidad_pedida * item.costo_unitario_estimado).toFixed(2) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex flex-col sm:flex-row justify-between items-center bg-slate-50 p-6 rounded-xl border border-slate-200">
                            <div>
                                <p class="text-sm text-slate-500 font-bold uppercase tracking-widest">Total Estimado</p>
                                <p class="text-3xl font-black text-slate-800">${{ totalCotizacion.toFixed(2) }}</p>
                            </div>
                            
                            <button 
                                type="submit" 
                                class="mt-4 sm:mt-0 bg-sky-600 hover:bg-sky-700 text-white font-bold py-3 px-8 rounded-xl shadow-lg shadow-sky-500/30 transition-all flex items-center gap-2 active:scale-95"
                                :disabled="form.processing"
                            >
                                <span v-if="form.processing">Enviando...</span>
                                <span v-else>Enviar Cotización Oficial</span>
                                <svg v-if="!form.processing" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-12 text-center text-slate-500 text-xs font-medium pb-8">
                Esta es una cotización segura enviada a través de la plataforma <strong>VendAR</strong>.
            </div>

        </div>
    </div>
</template>