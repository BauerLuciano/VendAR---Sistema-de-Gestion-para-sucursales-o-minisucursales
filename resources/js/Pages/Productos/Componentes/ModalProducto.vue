<script setup>
import { useForm } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    mostrar: Boolean,
    producto: Object,
    categorias: Array,
    marcas: Array
});

const emit = defineEmits(['cerrar']);
const imagenPreview = ref(null);

const formulario = useForm({
    id: null,
    nombre: '',
    sku: '',
    categoria_id: '',
    marca_id: '',
    precio_costo: '',
    precio_venta: '',
    stock_minimo: 5,
    descripcion: '',
    imagen: null,
});

watch(() => props.producto, (nuevoValor) => {
    if (nuevoValor) {
        formulario.id = nuevoValor.id;
        formulario.nombre = nuevoValor.nombre;
        formulario.sku = nuevoValor.sku;
        formulario.categoria_id = nuevoValor.categoria_id;
        formulario.marca_id = nuevoValor.marca_id;
        formulario.precio_costo = nuevoValor.precio_costo;
        formulario.precio_venta = nuevoValor.precio_venta;
        formulario.stock_minimo = nuevoValor.stock_minimo;
        formulario.descripcion = nuevoValor.descripcion;
        imagenPreview.value = nuevoValor.url_imagen;
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
    const ruta = esEdicion ? route('productos.update', formulario.id) : route('productos.store');
    
    formulario.post(ruta, {
        forceFormData: true,
        onSuccess: () => {
            Swal.fire('¡Éxito!', `Producto ${esEdicion ? 'actualizado' : 'guardado'} correctamente.`, 'success');
            emit('cerrar');
            formulario.reset();
            imagenPreview.value = null;
        },
        onError: () => Swal.fire('Error', 'Hubo un problema. Revisá los campos obligatorios.', 'error')
    });
};
</script>

<template>
    <div v-if="mostrar" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl overflow-y-auto max-h-[90vh]">
            <div class="bg-sky-600 p-4 text-white font-bold text-center uppercase tracking-widest">
                {{ formulario.id ? 'Editar Producto' : 'Registrar Nuevo Producto' }}
            </div>
            
            <form @submit.prevent="guardar" class="p-6 grid grid-cols-2 gap-4">
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-gray-500 uppercase">Nombre</label>
                    <input v-model="formulario.nombre" type="text" class="w-full rounded border-gray-300 shadow-sm" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase">SKU (13 dígitos)</label>
                    <input v-model="formulario.sku" type="text" maxlength="13" oninput="this.value = this.value.replace(/[^0-9]/g, '');" class="w-full rounded border-gray-300 shadow-sm font-mono" required>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase">Stock Mínimo</label>
                    <input v-model="formulario.stock_minimo" type="number" class="w-full rounded border-gray-300 shadow-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase">Categoría</label>
                    <select v-model="formulario.categoria_id" class="w-full rounded border-gray-300 shadow-sm" required>
                        <option value="" disabled>Seleccione...</option>
                        <option v-for="cat in categorias" :key="cat.id" :value="cat.id">{{ cat.nombreCategoria }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase">Marca</label>
                    <select v-model="formulario.marca_id" class="w-full rounded border-gray-300 shadow-sm" required>
                        <option value="" disabled>Seleccione...</option>
                        <option v-for="m in marcas" :key="m.id" :value="m.id">{{ m.nombreMarca }}</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase font-mono text-[10px]">Precio Costo</label>
                    <input v-model="formulario.precio_costo" type="number" step="0.01" class="w-full rounded border-gray-300 shadow-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-gray-500 uppercase font-mono text-[10px]">Precio Venta</label>
                    <input v-model="formulario.precio_venta" type="number" step="0.01" class="w-full rounded border-gray-300 shadow-sm">
                </div>

                <div class="col-span-2 flex flex-col items-center border-t pt-4">
                    <div class="w-24 h-24 border-2 border-dashed border-gray-300 rounded-lg overflow-hidden flex items-center justify-center bg-gray-50 shadow-inner">
                        <img v-if="imagenPreview" :src="imagenPreview" class="w-full h-full object-cover">
                        <span v-else class="text-gray-300 text-[10px]">Sin imagen</span>
                    </div>
                    <label class="mt-2 cursor-pointer bg-sky-50 text-sky-700 px-4 py-1.5 rounded-full text-xs font-bold hover:bg-sky-100 transition-colors border border-sky-200">
                        Seleccionar Imagen
                        <input type="file" @input="alSeleccionarImagen" class="hidden" accept="image/*">
                    </label>
                    <div v-if="formulario.errors.imagen" class="text-red-500 text-[10px] mt-1">{{ formulario.errors.imagen }}</div>
                </div>

                <div class="col-span-2 flex justify-end gap-3 border-t pt-4">
                    <button type="button" @click="$emit('cerrar')" class="text-gray-400 font-bold hover:text-gray-600 transition-colors uppercase text-xs">Cancelar</button>
                    <button type="submit" class="bg-sky-600 text-white px-8 py-2 rounded-lg font-bold hover:bg-sky-700 shadow-lg transition-all uppercase text-xs" :disabled="formulario.processing">
                        {{ formulario.processing ? 'Enviando...' : 'Guardar Datos' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>