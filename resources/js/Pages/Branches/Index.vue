<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    branches: Array
});

// Estado del Modal
const isModalOpen = ref(false);
const isEditing = ref(false);
const currentBranchId = ref(null);

// Formulario de Inertia
const form = useForm({
    name: '',
    address: '',
    phone: '',
    type: 'punto_de_venta',
});

// Métodos
const openModal = (branch = null) => {
    if (branch) {
        isEditing.value = true;
        currentBranchId.value = branch.id;
        form.name = branch.name;
        form.address = branch.address;
        form.phone = branch.phone;
        form.type = branch.type;
    } else {
        isEditing.value = false;
        currentBranchId.value = null;
        form.reset();
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
        form.put(route('branches.update', currentBranchId.value), {
            onSuccess: () => closeModal()
        });
    } else {
        form.post(route('branches.store'), {
            onSuccess: () => closeModal()
        });
    }
};

const toggleStatus = (branch) => {
    if (confirm(`¿Estás seguro de cambiar el estado de ${branch.name}?`)) {
        router.put(route('branches.status', branch.id));
    }
};
</script>

<template>
    <Head title="Sucursales" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-screen">
            <div class="flex justify-between items-end mb-8">
                <div>
                    <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Gestión de Sucursales</h1>
                    <div class="h-1 w-12 bg-sky-500 mt-1"></div>
                </div>
                <button 
                    @click="openModal()"
                    class="bg-sky-600 hover:bg-sky-700 text-white font-bold py-2.5 px-5 rounded-xl shadow-sm shadow-sky-600/30 transition-all flex items-center gap-2"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" /></svg>
                    Nueva Sucursal
                </button>
            </div>

            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-xs uppercase tracking-widest text-slate-400">
                                <th class="p-4 font-black">Nombre</th>
                                <th class="p-4 font-black">Dirección</th>
                                <th class="p-4 font-black">Teléfono</th>
                                <th class="p-4 font-black">Tipo</th>
                                <th class="p-4 font-black">Estado</th>
                                <th class="p-4 font-black text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <tr v-if="branches.length === 0">
                                <td colspan="6" class="p-8 text-center text-slate-400 font-bold">No hay sucursales registradas.</td>
                            </tr>
                            <tr v-for="branch in branches" :key="branch.id" class="hover:bg-slate-50/50 transition-colors group">
                                <td class="p-4 font-bold text-slate-800">{{ branch.name }}</td>
                                <td class="p-4 text-slate-500 text-sm">{{ branch.address }}</td>
                                <td class="p-4 text-slate-500 text-sm">{{ branch.phone }}</td>
                                <td class="p-4 text-sm font-bold text-slate-500 uppercase">{{ branch.type.replace('_', ' ') }}</td>
                                <td class="p-4">
                                    <span 
                                        class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest"
                                        :class="branch.is_active ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600'"
                                    >
                                        {{ branch.is_active ? 'Activa' : 'Inactiva' }}
                                    </span>
                                </td>
                                <td class="p-4 text-right space-x-2">
                                    <button @click="openModal(branch)" class="text-slate-400 hover:text-sky-500 transition-colors p-2" title="Editar">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                    </button>
                                    <button @click="toggleStatus(branch)" class="text-slate-400 hover:text-rose-500 transition-colors p-2" title="Cambiar Estado">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                    </button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div v-if="isModalOpen" class="fixed inset-0 z-50 overflow-y-auto flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl shadow-xl w-full max-w-md overflow-hidden transform transition-all">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="text-lg font-black text-slate-800 uppercase tracking-tight">
                        {{ isEditing ? 'Editar Sucursal' : 'Nueva Sucursal' }}
                    </h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="submitForm" class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Nombre</label>
                        <input v-model="form.name" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700" required>
                        <div v-if="form.errors.name" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.name }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Dirección</label>
                        <input v-model="form.address" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700" required>
                        <div v-if="form.errors.address" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.address }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Teléfono</label>
                        <input v-model="form.phone" type="text" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700" required>
                        <div v-if="form.errors.phone" class="text-rose-500 text-xs mt-1 font-bold">{{ form.errors.phone }}</div>
                    </div>

                    <div>
                        <label class="block text-xs font-black text-slate-500 uppercase tracking-widest mb-1">Tipo de Local</label>
                        <select v-model="form.type" class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5 focus:ring-sky-500 focus:border-sky-500 font-medium text-slate-700">
                            <option value="punto_de_venta">Punto de Venta</option>
                            <option value="deposito">Depósito Central</option>
                            <option value="franquicia">Franquicia</option>
                        </select>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors">
                            Cancelar
                        </button>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="bg-sky-600 hover:bg-sky-700 disabled:bg-slate-300 text-white font-bold py-2.5 px-6 rounded-xl shadow-sm shadow-sky-600/30 transition-all flex items-center gap-2"
                        >
                            {{ isEditing ? 'Guardar Cambios' : 'Crear Sucursal' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>