<script setup>
import { ref } from 'vue';
import { useForm, router, Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const props = defineProps({
    cajas: Array,
    sucursales: Array
});

// Estado del Modal
const showModal = ref(false);
const isEditing = ref(false);

// Formulario
const form = useForm({
    id: null,
    nombre: '',
    sucursal_id: '',
});

// Abrir Modal para Crear o Editar
const openModal = (caja = null) => {
    if (caja) {
        isEditing.value = true;
        form.id = caja.id;
        form.nombre = caja.nombre;
        form.sucursal_id = caja.sucursal_id;
    } else {
        isEditing.value = false;
        form.reset();
        // Si el usuario pertenece a una sucursal, la pre-seleccionamos
        if (props.sucursales.length === 1) {
            form.sucursal_id = props.sucursales[0].id;
        }
    }
    showModal.value = true;
};

const closeModal = () => {
    showModal.value = false;
    form.reset();
    form.clearErrors();
};

// Guardar (Crear o Actualizar)
const saveCaja = () => {
    if (isEditing.value) {
        form.put(route('cajas.update', form.id), {
            onSuccess: () => closeModal(),
        });
    } else {
        form.post(route('cajas.store'), {
            onSuccess: () => closeModal(),
        });
    }
};

// Cambiar Estado (Activa/Inactiva)
const toggleEstado = (caja) => {
    router.patch(route('cajas.status', caja.id), {}, { preserveScroll: true });
};

// Eliminar Caja
const deleteCaja = (caja) => {
    if (confirm(`¿Estás seguro de que deseas eliminar la caja "${caja.nombre}"?`)) {
        router.delete(route('cajas.destroy', caja.id), { preserveScroll: true });
    }
};
</script>

<template>
	<AuthenticatedLayout>
    <Head title="Cajas Físicas" />

    <div class="p-8 max-w-7xl mx-auto ml-64">
        
        <div class="flex justify-between items-end mb-8">
            <div>
                <h1 class="text-3xl font-black text-slate-800 tracking-tight">Cajas Físicas</h1>
                <p class="text-slate-500 font-medium text-sm mt-1">Gestión de terminales de cobro por sucursal</p>
            </div>
            <button @click="openModal()" class="bg-sky-500 hover:bg-sky-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-sky-500/30 transition-all flex items-center gap-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                Nueva Caja
            </button>
        </div>

        <div class="bg-white border border-slate-100 shadow-xl shadow-slate-200/50 rounded-2xl overflow-hidden">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-100">
                        <th class="py-4 px-6 text-[10px] font-black tracking-widest uppercase text-slate-400">Nombre de la Caja</th>
                        <th class="py-4 px-6 text-[10px] font-black tracking-widest uppercase text-slate-400">Sucursal</th>
                        <th class="py-4 px-6 text-[10px] font-black tracking-widest uppercase text-slate-400">Estado</th>
                        <th class="py-4 px-6 text-[10px] font-black tracking-widest uppercase text-slate-400 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <tr v-for="caja in cajas" :key="caja.id" class="hover:bg-slate-50/50 transition-colors">
                        <td class="py-4 px-6">
                            <div class="font-bold text-slate-700">{{ caja.nombre }}</div>
                        </td>
                        <td class="py-4 px-6 text-sm font-medium text-slate-500">
                            {{ caja.sucursal?.nombre || 'Sin Asignar' }}
                        </td>
                        <td class="py-4 px-6">
                            <button @click="toggleEstado(caja)" 
                                :class="caja.estado ? 'bg-emerald-100 text-emerald-700' : 'bg-rose-100 text-rose-700'"
                                class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider transition-colors">
                                {{ caja.estado ? 'Activa' : 'Inactiva' }}
                            </button>
                        </td>
                        <td class="py-4 px-6 flex justify-end gap-2">
                            <button @click="openModal(caja)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-sky-100 hover:text-sky-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L6.832 19.82a4.5 4.5 0 0 1-1.897 1.13l-2.685.8.8-2.685a4.5 4.5 0 0 1 1.13-1.897L16.863 4.487Zm0 0L19.5 7.125" /></svg>
                            </button>
                            <button @click="deleteCaja(caja)" class="w-8 h-8 rounded-lg bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-rose-100 hover:text-rose-600 transition-colors">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-4 h-4"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                            </button>
                        </td>
                    </tr>
                    <tr v-if="cajas.length === 0">
                        <td colspan="4" class="py-12 text-center text-slate-400 font-medium">
                            No hay cajas registradas aún.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div v-if="showModal" class="fixed inset-0 z-[100] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="bg-white rounded-3xl w-full max-w-md shadow-2xl overflow-hidden animate-in zoom-in-95 duration-200">
                <div class="px-6 py-4 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <h3 class="font-black text-slate-800 tracking-tight">{{ isEditing ? 'Editar Caja' : 'Nueva Caja' }}</h3>
                    <button @click="closeModal" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-5 h-5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <form @submit.prevent="saveCaja" class="p-6 space-y-4">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Nombre de la Caja</label>
                        <input v-model="form.nombre" type="text" placeholder="Ej: Caja Principal, Caja Ventana..." class="w-full bg-slate-50 border-none rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-sky-500 transition-all" required>
                        <span v-if="form.errors.nombre" class="text-rose-500 text-xs mt-1 block">{{ form.errors.nombre }}</span>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Sucursal Asignada</label>
                        <select v-model="form.sucursal_id" class="w-full bg-slate-50 border-none rounded-xl text-sm font-bold text-slate-700 focus:ring-2 focus:ring-sky-500 transition-all" required>
                            <option value="" disabled>Seleccione una sucursal...</option>
                            <option v-for="sucursal in sucursales" :key="sucursal.id" :value="sucursal.id">
                                {{ sucursal.nombre }}
                            </option>
                        </select>
                        <span v-if="form.errors.sucursal_id" class="text-rose-500 text-xs mt-1 block">{{ form.errors.sucursal_id }}</span>
                    </div>

                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" @click="closeModal" class="px-5 py-2.5 rounded-xl font-bold text-sm text-slate-500 hover:bg-slate-100 transition-colors">Cancelar</button>
                        <button type="submit" :disabled="form.processing" class="bg-slate-900 hover:bg-sky-600 text-white px-5 py-2.5 rounded-xl font-bold text-sm shadow-lg shadow-slate-900/20 transition-all disabled:opacity-50">
                            {{ isEditing ? 'Guardar Cambios' : 'Crear Caja' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
	</AuthenticatedLayout>
</template>