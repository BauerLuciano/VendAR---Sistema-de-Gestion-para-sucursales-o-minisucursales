<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    comercios: Array,
    modulosDisponibles: Array
});

const busqueda = ref('');
const mostrarModal = ref(false);
const comercioSeleccionado = ref(null);

// Filtrado de comercios
const comerciosFiltrados = computed(() => {
    if (!busqueda.value) return props.comercios;
    return props.comercios.filter(c => 
        c.nombre.toLowerCase().includes(busqueda.value.toLowerCase())
    );
});

// Formulario para Crear/Editar
const form = useForm({
    nombre: '',
    plan: 'basico',
    status: 'trial',
    limite_sucursales: 1,
    vencimiento_pago: '',
    modulos_habilitados: { pos: true } // POS siempre activo por defecto
});

const abrirModal = (comercio = null) => {
    comercioSeleccionado.value = comercio;
    if (comercio) {
        form.nombre = comercio.nombre;
        form.plan = comercio.plan;
        form.status = comercio.status;
        form.limite_sucursales = comercio.limite_sucursales;
        form.vencimiento_pago = comercio.vencimiento_pago;
        form.modulos_habilitados = comercio.modulos_habilitados || { pos: true };
    } else {
        form.reset();
    }
    mostrarModal.value = true;
};

const guardar = () => {
    if (comercioSeleccionado.value) {
        form.put(route('admin.comercios.update', comercioSeleccionado.value.id), {
            onSuccess: () => {
                mostrarModal.value = false;
                Swal.fire('Actualizado', 'Configuración de comercio guardada.', 'success');
            }
        });
    } else {
        form.post(route('admin.comercios.store'), {
            onSuccess: () => {
                mostrarModal.value = false;
                Swal.fire('Registrado', 'Nuevo comercio añadido al sistema.', 'success');
            }
        });
    }
};

// Formatear fecha
const formatearFecha = (fecha) => {
    if (!fecha) return 'N/A';
    return new Date(fecha).toLocaleDateString();
};
</script>

<template>
    <Head title="Panel Global - Comercios" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-screen">
            
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 gap-4">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Administración Global</h1>
                    <p class="text-sm text-slate-500 font-medium italic">Gestión de Clientes SaaS de VendAR</p>
                </div>
                <button @click="abrirModal()" class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2.5 px-6 rounded-2xl shadow-lg shadow-indigo-200 transition-all flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
                    </svg>
                    Nuevo Comercio
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Total Clientes</p>
                    <h2 class="text-3xl font-black text-slate-800">{{ comercios.length }}</h2>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm border-l-4 border-l-emerald-500">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Activos</p>
                    <h2 class="text-3xl font-black text-emerald-600">{{ comercios.filter(c => c.status === 'activo').length }}</h2>
                </div>
                <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm border-l-4 border-l-rose-500">
                    <p class="text-xs font-black text-slate-400 uppercase tracking-widest">Suspendidos</p>
                    <h2 class="text-3xl font-black text-rose-600">{{ comercios.filter(c => c.status === 'suspendido').length }}</h2>
                </div>
            </div>

            <div class="mb-6 relative">
                <input v-model="busqueda" type="text" placeholder="Buscar comercio por nombre..." class="w-full bg-white border-none rounded-2xl px-12 py-4 shadow-sm focus:ring-2 focus:ring-indigo-500 font-medium">
                <svg class="w-6 h-6 text-slate-300 absolute left-4 top-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100 text-[10px] uppercase tracking-[0.2em] text-slate-400 font-black">
                            <th class="p-5">Comercio / Kiosco</th>
                            <th class="p-5">Plan</th>
                            <th class="p-5">Módulos</th>
                            <th class="p-5">Vencimiento</th>
                            <th class="p-5 text-center">Estado</th>
                            <th class="p-5 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="comercio in comerciosFiltrados" :key="comercio.id" class="hover:bg-slate-50/50 transition-colors">
                            <td class="p-5">
                                <div class="font-bold text-slate-800">{{ comercio.nombre }}</div>
                                <div class="text-[10px] text-slate-400 font-mono">{{ comercio.slug }}</div>
                            </td>
                            <td class="p-5">
                                <span class="px-3 py-1 rounded-lg text-[10px] font-black uppercase border"
                                    :class="{
                                        'bg-slate-100 border-slate-200 text-slate-600': comercio.plan === 'basico',
                                        'bg-blue-50 border-blue-100 text-blue-600': comercio.plan === 'pro',
                                        'bg-amber-50 border-amber-100 text-amber-600': comercio.plan === 'premium',
                                    }">
                                    {{ comercio.plan }}
                                </span>
                            </td>
                            <td class="p-5">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="(val, mod) in comercio.modulos_habilitados" :key="mod" 
                                          v-show="val" class="bg-indigo-50 text-indigo-600 text-[9px] font-bold px-2 py-0.5 rounded border border-indigo-100 uppercase">
                                        {{ mod }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-5 text-xs font-bold text-slate-600">
                                {{ formatearFecha(comercio.vencimiento_pago) }}
                            </td>
                            <td class="p-5 text-center">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold"
                                    :class="{
                                        'bg-emerald-100 text-emerald-800': comercio.status === 'activo',
                                        'bg-rose-100 text-rose-800': comercio.status === 'suspendido',
                                        'bg-amber-100 text-amber-800': comercio.status === 'trial',
                                    }">
                                    {{ comercio.status }}
                                </span>
                            </td>
                            <td class="p-5 text-center">
                                <button @click="abrirModal(comercio)" class="text-indigo-600 hover:text-indigo-900 font-black text-xs uppercase tracking-widest">Configurar</button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div v-if="mostrarModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 bg-slate-50 flex justify-between items-center">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">
                        {{ comercioSeleccionado ? 'Configurar Comercio' : 'Registrar Nuevo Comercio' }}
                    </h3>
                    <button @click="mostrarModal = false" class="text-slate-400 hover:text-slate-600">✖</button>
                </div>
                
                <form @submit.prevent="guardar" class="p-8 grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Nombre del Comercio</label>
                        <input v-model="form.nombre" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold" required>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Plan SaaS</label>
                        <select v-model="form.plan" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold">
                            <option value="basico">Plan Básico</option>
                            <option value="pro">Plan Profesional</option>
                            <option value="premium">Plan Premium / Enterprise</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Estado de Cuenta</label>
                        <select v-model="form.status" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold">
                            <option value="activo">Activo (Al día)</option>
                            <option value="suspendido">Suspendido (Falta de pago)</option>
                            <option value="trial">Período de Prueba</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Límite de Sucursales</label>
                        <input v-model="form.limite_sucursales" type="number" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold">
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Próximo Vencimiento</label>
                        <input v-model="form.vencimiento_pago" type="date" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-bold">
                    </div>

                    <div class="md:col-span-2">
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3">Módulos Habilitados (A la carta)</label>
                        <div class="grid grid-cols-2 gap-3">
                            <div v-for="mod in modulosDisponibles" :key="mod.id" class="flex items-center p-3 bg-slate-50 rounded-xl border border-slate-100 hover:border-indigo-200 transition-colors">
                                <input type="checkbox" :id="mod.id" v-model="form.modulos_habilitados[mod.id]" class="rounded text-indigo-600 focus:ring-indigo-500 mr-3">
                                <label :for="mod.id" class="text-xs font-bold text-slate-700 cursor-pointer">{{ mod.nombre }}</label>
                            </div>
                        </div>
                    </div>

                    <div class="md:col-span-2 flex justify-end gap-3 pt-6 border-t border-slate-100">
                        <button type="button" @click="mostrarModal = false" class="px-6 py-2.5 text-xs font-black text-slate-400 uppercase tracking-widest">Cancelar</button>
                        <button type="submit" class="bg-slate-900 text-white px-8 py-2.5 rounded-2xl text-xs font-black uppercase tracking-widest shadow-lg shadow-slate-200">
                            {{ comercioSeleccionado ? 'Actualizar Cliente' : 'Crear Comercio' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>