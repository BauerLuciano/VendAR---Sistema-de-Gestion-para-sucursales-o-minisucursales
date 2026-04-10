<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DetalleIngreso from './Componentes/DetalleIngreso.vue';
import ModalIngreso from './Componentes/ModalIngreso.vue';
import { Head, usePage } from '@inertiajs/vue3'; // Importamos usePage
import { ref, onMounted } from 'vue'; // Importamos onMounted
import Swal from 'sweetalert2'; // Importamos SweetAlert2

const props = defineProps({ 
    ingresos: Array,
    productos: Array,
    proveedores: Array,
    sucursales: Array
});

const page = usePage();
const verDetalle = ref(false);
const verModal = ref(false);
const seleccionado = ref(null);

const abrirDetalle = (ingreso) => {
    seleccionado.value = ingreso;
    verDetalle.value = true;
};

const abrirNuevo = () => {
    verModal.value = true;
};

// --- LÓGICA DE ALERTAS PREMIUM ---
onMounted(() => {
    // 1. Alerta de Inflación (Aumentos detectados)
    if (page.props.flash.alertas_inflacion && page.props.flash.alertas_inflacion.length > 0) {
        let listaProductos = page.props.flash.alertas_inflacion.map(p => 
            `<li class="flex justify-between items-center border-b border-slate-100 py-2">
                <span class="font-bold text-slate-700">${p.producto}</span>
                <span class="bg-rose-100 text-rose-600 px-2 py-0.5 rounded-lg text-xs font-black">+${p.porcentaje}%</span>
            </li>`
        ).join('');

        Swal.fire({
            title: '<span class="text-2xl font-black text-slate-800">¡Alerta de Aumentos!</span>',
            html: `
                <div class="text-left mt-4">
                    <p class="text-slate-500 text-sm mb-4">Los siguientes productos entraron con un costo mayor al registrado anteriormente:</p>
                    <ul class="bg-slate-50 p-4 rounded-2xl border border-slate-200 max-h-60 overflow-y-auto">
                        ${listaProductos}
                    </ul>
                    <p class="mt-4 text-[11px] text-slate-400 italic text-center text-balance">
                        Los costos han sido actualizados automáticamente en tu catálogo de productos.
                    </p>
                </div>
            `,
            icon: 'warning',
            confirmButtonColor: '#4f46e5',
            confirmButtonText: 'Entendido, voy a revisar mis precios',
            customClass: {
                popup: 'rounded-3xl',
                confirmButton: 'rounded-xl font-bold uppercase text-xs tracking-widest py-3 px-6'
            }
        });
    }
    
    // 2. Mensaje de Éxito General
    if (page.props.flash.exito) {
        Swal.fire({
            title: '¡Éxito!',
            text: page.props.flash.exito,
            icon: 'success',
            timer: 3000,
            showConfirmButton: false,
            timerProgressBar: true,
            customClass: {
                popup: 'rounded-3xl'
            }
        });
    }

    // 3. Mensaje de Error (por si algo falla)
    if (page.props.flash.error) {
        Swal.fire({
            title: 'Hubo un problema',
            text: page.props.flash.error,
            icon: 'error',
            confirmButtonColor: '#e11d48',
            customClass: {
                popup: 'rounded-3xl'
            }
        });
    }
});
</script>

<template>
    <Head title="Historial de Ingresos" />

    <AuthenticatedLayout>
        <template #header><h2 class="font-semibold text-xl text-gray-800 leading-tight">Historial de Stock</h2></template>

        <div class="py-12 max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-bold text-gray-600 uppercase">Ingresos Registrados</h3>
                <button @click="abrirNuevo" class="bg-sky-600 text-white px-6 py-2.5 rounded-lg font-bold shadow-lg hover:bg-sky-700 transition-all uppercase text-xs tracking-widest">
                    + Cargar Remito
                </button>
            </div>

            <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100 p-4">
                <table class="w-full text-left border-separate border-spacing-y-2">
                    <thead>
                        <tr class="bg-sky-50 text-sky-900 uppercase text-xs font-black">
                            <th class="p-4 rounded-l-xl">Fecha</th>
                            <th class="p-4">Nro Remito</th>
                            <th class="p-4">Sucursal</th>
                            <th class="p-4">Proveedor</th>
                            <th class="p-4 text-right">Total Costo</th>
                            <th class="p-4 text-center rounded-r-xl">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="ingresos.length === 0">
                            <td colspan="6" class="p-10 text-center text-slate-400 italic bg-slate-50 rounded-xl">No hay ingresos registrados.</td>
                        </tr>
                        <tr v-for="i in ingresos" :key="i.id" class="bg-white border-b hover:bg-sky-50 transition-colors shadow-sm">
                            <td class="p-4 font-bold text-slate-700">{{ i.fecha_ingreso }}</td>
                            <td class="p-4 font-mono font-bold text-sky-800">{{ i.numero_remito || 'S/N' }}</td>
                            <td class="p-4 text-sm text-slate-600 font-bold">{{ i.sucursal?.nombre }}</td>
                            <td class="p-4 text-sm text-slate-600">{{ i.proveedor?.razon_social || 'Variado / Ninguno' }}</td>
                            <td class="p-4 text-right font-black text-rose-600 font-mono">${{ i.total_costo }}</td>
                            <td class="p-4 text-center">
                                <button @click="abrirDetalle(i)" class="bg-slate-800 text-white px-4 py-1.5 rounded-lg text-[10px] font-black uppercase tracking-widest hover:bg-slate-700 shadow-sm">Ver Detalle</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <DetalleIngreso :mostrar="verDetalle" :ingreso="seleccionado" @cerrar="verDetalle = false" />
        
        <ModalIngreso 
            :mostrar="verModal" 
            :productos="productos" 
            :proveedores="proveedores" 
            :sucursales="sucursales" 
            @cerrar="verModal = false" 
        />
    </AuthenticatedLayout>
</template>