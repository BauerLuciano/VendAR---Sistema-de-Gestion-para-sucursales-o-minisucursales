<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ModalMarca from './Componentes/ModalMarca.vue'; 
import DetalleMarca from './Componentes/DetalleMarca.vue'; 
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({ marcas: Array });

const verModal = ref(false);
const verDetalle = ref(false);
const seleccionado = ref(null);

const abrirNuevo = () => { seleccionado.value = null; verModal.value = true; };
const abrirEditar = (m) => { seleccionado.value = m; verModal.value = true; };
const abrirDetalle = (m) => { seleccionado.value = m; verDetalle.value = true; };

const cerrarModal = () => {
    verModal.value = false;
    seleccionado.value = null;
};

const toggleEstado = (m) => {
    const accion = m.estado ? 'desactivar' : 'activar';
    const resultado = m.estado ? 'desactivada' : 'activada';
    const colorConfirm = m.estado ? '#ef4444' : '#10b981';

    Swal.fire({
        title: `¿${accion.toUpperCase()} marca?`,
        text: `La marca "${m.nombreMarca}" cambiará su estado a ${resultado}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: colorConfirm,
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(route('marcas.status', m.id), {}, {
                onSuccess: () => {
                    Swal.fire({
                        title: '¡Listo!',
                        text: `Marca ${resultado} correctamente.`,
                        icon: 'success',
                        confirmButtonColor: '#0284c7',
                    });
                }
            });
        }
    });
};
</script>

<template>
    <Head title="Gestión de Marcas" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Control de Marcas</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-600 uppercase tracking-wider">Listado de Marcas</h3>
                    <button @click="abrirNuevo" class="bg-sky-600 text-white px-6 py-2.5 rounded-lg font-bold shadow-lg hover:bg-sky-700 transition-all active:scale-95">
                        + Nueva Marca
                    </button>
                </div>

                <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100 p-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-separate border-spacing-y-2">
                            <thead>
                                <tr class="bg-sky-50 text-sky-900 uppercase text-xs font-black">
                                    <th class="p-4 rounded-l-xl">ID</th>
                                    <th class="p-4 text-center">Logo</th> <th class="p-4">Nombre de la Marca</th>
                                    <th class="p-4 text-center">Estado</th>
                                    <th class="p-4 text-center rounded-r-xl">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="marcas.length === 0">
                                    <td colspan="5" class="p-10 text-center text-gray-400 italic bg-gray-50 rounded-xl">
                                        No hay marcas registradas todavía.
                                    </td>
                                </tr>

                                <tr v-for="m in marcas" :key="m.id" 
                                    class="bg-white border-b hover:bg-sky-50 transition-all duration-200 group shadow-sm"
                                    :class="{'opacity-50 grayscale': !m.estado}">
                                    
                                    <td class="p-4 font-mono font-bold text-sky-800">#{{ m.id }}</td>
                                    
                                    <td class="p-4">
                                        <div class="flex justify-center items-center">
                                            <img v-if="m.url_imagen" :src="m.url_imagen" class="w-10 h-10 object-contain rounded-lg border shadow-sm bg-white p-1">
                                            <div v-else class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center text-gray-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-4 font-bold text-slate-700 uppercase tracking-tight">{{ m.nombreMarca }}</td>
                                    
                                    <td class="p-4 text-center">
                                        <span :class="m.estado ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50'" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm">
                                            {{ m.estado ? 'Activa' : 'Baja' }}
                                        </span>
                                    </td>
                                    
                                    <td class="p-4">
                                        <div class="flex justify-center gap-3">
                                            <button @click="abrirDetalle(m)" class="bg-sky-100 text-sky-600 p-2 rounded-xl hover:bg-sky-600 hover:text-white transition-all shadow-sm" title="Ver Detalle">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>

                                            <button @click="abrirEditar(m)" class="bg-amber-100 text-amber-600 p-2 rounded-xl hover:bg-amber-500 hover:text-white transition-all shadow-sm" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>

                                            <button @click="toggleEstado(m)" 
                                                :class="m.estado ? 'bg-rose-100 text-rose-600 hover:bg-rose-600' : 'bg-emerald-100 text-emerald-600 hover:bg-emerald-600'" 
                                                class="p-2 rounded-xl hover:text-white transition-all shadow-sm" 
                                                :title="m.estado ? 'Desactivar' : 'Activar'">
                                                <svg v-if="m.estado" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <ModalMarca 
            :mostrar="verModal" 
            :marca="seleccionado" 
            @cerrar="cerrarModal" 
        />

        <DetalleMarca 
            :mostrar="verDetalle" 
            :marca="seleccionado" 
            @cerrar="verDetalle = false" 
        />
    </AuthenticatedLayout>
</template>