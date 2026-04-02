<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ModalCategoria from './Componentes/ModalCategoria.vue'; 
import DetalleCategoria from './Componentes/DetalleCategoria.vue'; 
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({ 
    categorias: Array 
});

const verModal = ref(false);
const verDetalle = ref(false);
const seleccionado = ref(null);

const abrirNuevo = () => { 
    seleccionado.value = null; 
    verModal.value = true; 
};

const abrirEditar = (c) => { 
    seleccionado.value = c; 
    verModal.value = true; 
};

const abrirDetalle = (c) => { 
    seleccionado.value = c; 
    verDetalle.value = true; 
};

const cerrarModal = () => {
    verModal.value = false;
    seleccionado.value = null;
};

const toggleEstado = (c) => {
    const accion = c.estado ? 'desactivar' : 'activar';
    const resultado = c.estado ? 'desactivada' : 'activada'; // En femenino por "Categoría"
    const colorConfirm = c.estado ? '#ef4444' : '#10b981';

    Swal.fire({
        title: `¿${accion.toUpperCase()} categoría?`,
        text: `La categoría "${c.nombreCategoria}" cambiará su estado a ${resultado}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: colorConfirm,
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(route('categorias.status', c.id), {}, {
                onSuccess: () => {
                    Swal.fire({
                        title: '¡Listo!',
                        text: `Categoría ${resultado} correctamente.`,
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
    <Head title="Gestión de Categorías" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Control de Categorías</h2>
        </template>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-600 uppercase tracking-wider">Listado de Categorías</h3>
                    <button @click="abrirNuevo" class="bg-sky-600 text-white px-6 py-2.5 rounded-lg font-bold shadow-lg hover:bg-sky-700 hover:-translate-y-0.5 transition-all active:scale-95">
                        + Nueva Categoría
                    </button>
                </div>

                <div class="bg-white shadow-xl rounded-2xl overflow-hidden border border-gray-100 p-4">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-separate border-spacing-y-2">
                            <thead>
                                <tr class="bg-sky-50 text-sky-900 uppercase text-xs font-black">
                                    <th class="p-4 rounded-l-xl">ID</th>
                                    <th class="p-4">Nombre de la Categoría</th>
                                    <th class="p-4 text-center">Estado</th>
                                    <th class="p-4 text-center rounded-r-xl">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="categorias.length === 0">
                                    <td colspan="4" class="p-10 text-center text-gray-400 italic bg-gray-50 rounded-xl">
                                        No hay categorías registradas todavía.
                                    </td>
                                </tr>

                                <tr v-for="c in categorias" :key="c.id" 
                                    class="bg-white border-b hover:bg-sky-50 transition-all duration-200 group shadow-sm"
                                    :class="{'opacity-50 grayscale': !c.estado}">
                                    
                                    <td class="p-4 font-mono font-bold text-sky-800">#{{ c.id }}</td>
                                    <td class="p-4 font-bold text-slate-700 uppercase">{{ c.nombreCategoria }}</td>
                                    
                                    <td class="p-4 text-center">
                                        <span :class="c.estado ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50'" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm">
                                            {{ c.estado ? 'Activa' : 'Baja' }}
                                        </span>
                                    </td>
                                    
                                    <td class="p-4">
                                        <div class="flex justify-center gap-3">
                                            <button @click="abrirDetalle(c)" class="bg-sky-100 text-sky-600 p-2 rounded-xl hover:bg-sky-600 hover:text-white transition-all shadow-sm" title="Ver Detalle">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                            </button>

                                            <button @click="abrirEditar(c)" class="bg-amber-100 text-amber-600 p-2 rounded-xl hover:bg-amber-500 hover:text-white transition-all shadow-sm" title="Editar">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                            </button>

                                            <button @click="toggleEstado(c)" 
                                                :class="c.estado ? 'bg-rose-100 text-rose-600 hover:bg-rose-600' : 'bg-emerald-100 text-emerald-600 hover:bg-emerald-600'" 
                                                class="p-2 rounded-xl hover:text-white transition-all shadow-sm" 
                                                :title="c.estado ? 'Desactivar' : 'Activar'">
                                                
                                                <svg v-if="c.estado" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
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

        <ModalCategoria 
            :mostrar="verModal" 
            :categoria="seleccionado" 
            @cerrar="cerrarModal" 
        />

        <DetalleCategoria 
            :mostrar="verDetalle" 
            :categoria="seleccionado" 
            @cerrar="verDetalle = false" 
        />
    </AuthenticatedLayout>
</template>