<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    consumidores: Array
});

// Estado del Modal
const isModalOpen = ref(false);
const isEditing = ref(false);
const currentId = ref(null);

// Formulario de Inertia mapeado con la BD
const form = useForm({
    nombre: '',
    apellido: '',
    documento: '',
    email: '',
    telefono: '',
    direccion: '',
    limite_cuenta_corriente: 0,
    estado: true,
});

// Métodos
const openModal = (cliente = null) => {
    form.clearErrors(); // Limpiar errores previos si los hubiera
    if (cliente) {
        isEditing.value = true;
        currentId.value = cliente.id;
        form.nombre = cliente.nombre;
        form.apellido = cliente.apellido;
        form.documento = cliente.documento || '';
        form.email = cliente.email || '';
        form.telefono = cliente.telefono || '';
        form.direccion = cliente.direccion || '';
        form.limite_cuenta_corriente = cliente.limite_cuenta_corriente;
        form.estado = Boolean(cliente.estado);
    } else {
        isEditing.value = false;
        currentId.value = null;
        form.reset();
        form.estado = true; // Por defecto activo
    }
    isModalOpen.value = true;
};

const closeModal = () => {
    isModalOpen.value = false;
    form.reset();
    form.clearErrors();
};

const submitForm = () => {
    if (isEditing.value) {
        form.put(route('consumidores.update', currentId.value), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('consumidores.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const formatearDinero = (monto) => {
    return new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS' }).format(monto || 0);
};

const calcularDisponible = (limite, deuda) => {
    return limite - (deuda || 0);
};
</script>

<template>
    <Head title="Clientes" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-screen">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Directorio de Clientes</h1>
                    <div class="h-1 w-12 bg-sky-500 mt-1"></div>
                </div>
                <button 
                    @click="openModal()"
                    class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-sm shadow-sky-600/30 transition-all flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path d="M8 9a3 3 0 100-6 3 3 0 000 6zM8 11a6 6 0 016 6H2a6 6 0 016-6zM16 7a1 1 0 10-2 0v1h-1a1 1 0 100 2h1v1a1 1 0 102 0v-1h1a1 1 0 100-2h-1V7z" /></svg>
                    Nuevo Cliente
                </button>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs uppercase tracking-widest text-slate-400">
                                <th class="p-4 font-black">ID</th>
                                <th class="p-4 font-black">Cliente</th>
                                <th class="p-4 font-black">Contacto</th>
                                <th class="p-4 font-black text-right">Límite Cta. Cte.</th>
                                <th class="p-4 font-black text-right">Deuda Actual</th>
                                <th class="p-4 font-black text-right">Disponible</th>
                                <th class="p-4 font-black text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="consumidores.length === 0">
                                <td colspan="7" class="p-8 text-center text-slate-400 font-bold">No hay clientes registrados.</td>
                            </tr>
                            <tr v-for="cliente in consumidores" :key="cliente.id" class="hover:bg-slate-50/50 transition-colors group" :class="{'opacity-50': !cliente.estado}">
                                <td class="p-4 font-bold text-slate-400">#{{ cliente.id }}</td>
                                <td class="p-4">
                                    <div class="font-bold text-slate-800">{{ cliente.nombre }} {{ cliente.apellido }}</div>
                                    <div class="text-xs font-medium mt-1" :class="cliente.estado ? 'text-emerald-500' : 'text-rose-500'">
                                        {{ cliente.estado ? 'Activo' : 'Inactivo' }}
                                    </div>
                                </td>
                                <td class="p-4">
                                    <div class="text-xs text-slate-500 font-medium space-y-1">
                                        <div v-if="cliente.documento" title="Documento"><span class="font-bold text-slate-400">DOC:</span> {{ cliente.documento }}</div>
                                        <div v-if="cliente.telefono" title="Teléfono"><span class="font-bold text-slate-400">TEL:</span> {{ cliente.telefono }}</div>
                                        <div v-if="cliente.email" title="Email"><span class="font-bold text-slate-400">MAIL:</span> {{ cliente.email }}</div>
                                    </div>
                                </td>
                                
                                <td class="p-4 font-bold text-slate-600 text-right">
                                    {{ formatearDinero(cliente.limite_cuenta_corriente) }}
                                </td>
                                
                                <td class="p-4 font-black text-right" :class="cliente.cuenta_corriente?.saldo_deudor > 0 ? 'text-rose-600' : 'text-slate-400'">
                                    {{ formatearDinero(cliente.cuenta_corriente?.saldo_deudor) }}
                                </td>
                                
                                <td class="p-4 font-black text-right" :class="calcularDisponible(cliente.limite_cuenta_corriente, cliente.cuenta_corriente?.saldo_deudor) <= 0 ? 'text-rose-500' : 'text-emerald-600'">
                                    {{ formatearDinero(calcularDisponible(cliente.limite_cuenta_corriente, cliente.cuenta_corriente?.saldo_deudor)) }}
                                </td>

                                <td class="p-4 text-center">
                                    <button @click="openModal(cliente)" class="text-slate-400 hover:text-sky-500 transition-colors p-2" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-xl w-full max-w-2xl overflow-hidden transform transition-all">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">
                        {{ isEditing ? 'Editar Cliente' : 'Nuevo Cliente' }}
                    </h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Nombre</label>
                            <input 
                                v-model="form.nombre" 
                                @input="form.nombre = form.nombre.replace(/[0-9]/g, '')"
                                maxlength="50"
                                type="text" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700" 
                                :class="{'border-rose-500': form.errors.nombre}" 
                                required
                            >
                            <p v-if="form.errors.nombre" class="mt-1 text-xs text-rose-500 font-bold">{{ form.errors.nombre }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Apellido</label>
                            <input 
                                v-model="form.apellido" 
                                @input="form.apellido = form.apellido.replace(/[0-9]/g, '')"
                                maxlength="50"
                                type="text" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700" 
                                :class="{'border-rose-500': form.errors.apellido}" 
                                required
                            >
                            <p v-if="form.errors.apellido" class="mt-1 text-xs text-rose-500 font-bold">{{ form.errors.apellido }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Documento</label>
                            <input 
                                v-model="form.documento" 
                                @input="form.documento = form.documento.replace(/\D/g, '')"
                                maxlength="8"
                                type="text" 
                                placeholder="Ej: 30123456" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700" 
                                :class="{'border-rose-500': form.errors.documento}"
                            >
                            <p v-if="form.errors.documento" class="mt-1 text-xs text-rose-500 font-bold">{{ form.errors.documento }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Email</label>
                            <input 
                                v-model="form.email" 
                                maxlength="255"
                                type="email" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700" 
                                :class="{'border-rose-500': form.errors.email}"
                            >
                            <p v-if="form.errors.email" class="mt-1 text-xs text-rose-500 font-bold">{{ form.errors.email }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Teléfono</label>
                            <input 
                                v-model="form.telefono" 
                                @input="form.telefono = form.telefono.replace(/\D/g, '')"
                                maxlength="15"
                                type="text" 
                                placeholder="Ej: 3758445566"
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700" 
                                :class="{'border-rose-500': form.errors.telefono}"
                            >
                            <p v-if="form.errors.telefono" class="mt-1 text-xs text-rose-500 font-bold">{{ form.errors.telefono }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Dirección</label>
                            <input 
                                v-model="form.direccion" 
                                maxlength="255"
                                type="text" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700" 
                                :class="{'border-rose-500': form.errors.direccion}"
                            >
                            <p v-if="form.errors.direccion" class="mt-1 text-xs text-rose-500 font-bold">{{ form.errors.direccion }}</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Límite Cta. Corriente ($)</label>
                            <input 
                                v-model="form.limite_cuenta_corriente" 
                                type="number" 
                                step="100" 
                                min="0" 
                                class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700" 
                                :class="{'border-rose-500': form.errors.limite_cuenta_corriente}" 
                                required
                            >
                            <p v-if="form.errors.limite_cuenta_corriente" class="mt-1 text-xs text-rose-500 font-bold">{{ form.errors.limite_cuenta_corriente }}</p>
                        </div>
                        <div>
                            <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Estado Operativo</label>
                            <select v-model="form.estado" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700" :class="{'border-rose-500': form.errors.estado}">
                                <option :value="true">Activo (Habilitado)</option>
                                <option :value="false">Inactivo (Suspendido)</option>
                            </select>
                            <p v-if="form.errors.estado" class="mt-1 text-xs text-rose-500 font-bold">{{ form.errors.estado }}</p>
                        </div>
                    </div>

                    <div class="pt-6 flex justify-end gap-3 border-t border-slate-100 mt-4">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="bg-sky-600 hover:bg-sky-700 disabled:bg-slate-300 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm shadow-sky-600/30 transition-all flex items-center gap-2">
                            <span v-if="form.processing">Guardando...</span>
                            <span v-else>{{ isEditing ? 'Guardar Cambios' : 'Registrar Cliente' }}</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>