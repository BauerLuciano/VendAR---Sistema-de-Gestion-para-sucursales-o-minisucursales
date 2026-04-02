<script setup>
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    mostrar: Boolean,
    categoria: Object,
});

const emit = defineEmits(['cerrar']);

const formulario = useForm({
    id: null,
    nombreCategoria: '',
});

watch(() => props.categoria, (nuevoValor) => {
    if (nuevoValor) {
        formulario.id = nuevoValor.id;
        formulario.nombreCategoria = nuevoValor.nombreCategoria;
    } else {
        formulario.reset();
    }
}, { immediate: true });

const guardar = () => {
    const esEdicion = !!formulario.id;
    const ruta = esEdicion ? route('categorias.update', formulario.id) : route('categorias.store');
    const metodo = esEdicion ? 'put' : 'post';
    
    formulario[metodo](ruta, {
        onSuccess: () => {
            Swal.fire({
                title: '¡Éxito!',
                text: `Categoría ${esEdicion ? 'actualizada' : 'guardada'} correctamente.`,
                icon: 'success',
                confirmButtonColor: '#0284c7',
            });
            emit('cerrar');
            formulario.reset();
        },
        onError: () => Swal.fire('Error', 'Hubo un problema. Revisá que el nombre no esté duplicado.', 'error')
    });
};
</script>

<template>
    <div v-if="mostrar" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden transition-all">
            <div class="bg-sky-600 p-4 text-white font-bold text-center uppercase tracking-widest">
                {{ formulario.id ? 'Editar Categoría' : 'Registrar Nueva Categoría' }}
            </div>
            
            <form @submit.prevent="guardar" class="p-6 space-y-4">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre de la Categoría</label>
                    <input 
                        v-model="formulario.nombreCategoria" 
                        type="text" 
                        class="w-full rounded border-gray-300 shadow-sm focus:ring-sky-500 focus:border-sky-500 transition-all uppercase font-bold text-slate-700" 
                        placeholder="EJ: BEBIDAS, LIMPIEZA..."
                        required
                    >
                    <div v-if="formulario.errors.nombreCategoria" class="text-red-500 text-[10px] mt-1 font-bold">
                        {{ formulario.errors.nombreCategoria }}
                    </div>
                </div>

                <div class="flex justify-end gap-3 border-t pt-6 mt-4">
                    <button 
                        type="button" 
                        @click="$emit('cerrar')" 
                        class="text-gray-400 font-bold hover:text-gray-600 transition-colors uppercase text-xs tracking-widest"
                    >
                        Cancelar
                    </button>
                    <button 
                        type="submit" 
                        class="bg-sky-600 text-white px-8 py-2.5 rounded-lg font-bold hover:bg-sky-700 shadow-lg shadow-sky-600/20 transition-all uppercase text-xs tracking-widest active:scale-95" 
                        :disabled="formulario.processing"
                    >
                        {{ formulario.processing ? 'Enviando...' : 'Confirmar Datos' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>