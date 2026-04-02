<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({ mostrar: Boolean, marca: Object });
const emit = defineEmits(['cerrar']);
const imagenPreview = ref(null);

const formulario = useForm({
    id: null,
    nombreMarca: '',
    imagen: null,
});

watch(() => props.marca, (nuevo) => {
    if (nuevo) {
        formulario.id = nuevo.id;
        formulario.nombreMarca = nuevo.nombreMarca;
        imagenPreview.value = nuevo.url_imagen;
    } else {
        formulario.reset();
        imagenPreview.value = null;
    }
}, { immediate: true });

const alSeleccionarImagen = (e) => {
    const archivo = e.target.files[0];
    if (archivo) {
        formulario.imagen = archivo;
        imagenPreview.value = URL.createObjectURL(archivo);
    }
};

const guardar = () => {
    const esEdicion = !!formulario.id;
    const ruta = esEdicion ? route('marcas.update', formulario.id) : route('marcas.store');
    
    formulario.post(ruta, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire('¡Éxito!', `Marca ${esEdicion ? 'actualizada' : 'guardada'} correctamente.`, 'success');
            emit('cerrar');
            formulario.reset();
            imagenPreview.value = null;
        }
    });
};
</script>

<template>
    <div v-if="mostrar" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4 backdrop-blur-sm">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md overflow-hidden">
            <div class="bg-sky-600 p-4 text-white font-bold text-center uppercase tracking-widest">
                {{ formulario.id ? 'Editar Marca' : 'Nueva Marca' }}
            </div>
            <form @submit.prevent="guardar" class="p-6">
                <div class="mb-6">
                    <label class="block text-xs font-bold text-gray-500 uppercase mb-1">Nombre de la Marca</label>
                    <input v-model="formulario.nombreMarca" type="text" class="w-full rounded border-gray-300 shadow-sm uppercase font-bold text-slate-700" required>
                </div>

                <div class="flex flex-col items-center border-t pt-4 mb-4">
                    <div class="w-20 h-20 border rounded mb-2 overflow-hidden bg-gray-50 flex items-center justify-center shadow-inner">
                        <img v-if="imagenPreview" :src="imagenPreview" class="w-full h-full object-cover">
                        <span v-else class="text-gray-300 text-[10px]">Sin logo</span>
                    </div>
                    <label class="bg-sky-50 text-sky-700 px-4 py-1.5 rounded-full text-xs font-bold cursor-pointer hover:bg-sky-100 transition-colors border border-sky-200 uppercase tracking-tighter">
                        Seleccionar Logo
                        <input type="file" @input="alSeleccionarImagen" class="hidden" accept="image/*">
                    </label>
                </div>

                <div class="flex justify-end gap-3 border-t pt-4">
                    <button type="button" @click="$emit('cerrar')" class="text-gray-400 font-bold uppercase text-xs">Cancelar</button>
                    <button type="submit" class="bg-sky-600 text-white px-8 py-2.5 rounded-lg font-bold shadow-lg shadow-sky-600/20 transition-all uppercase text-xs" :disabled="formulario.processing">
                        Confirmar
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>