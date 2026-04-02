<script setup>
defineProps({ mostrar: Boolean, producto: Object });
defineEmits(['cerrar']);
</script>

<template>
    <div v-if="mostrar && producto" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 p-4">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-md p-6">
            <h2 class="text-xl font-black text-slate-800 mb-4 border-b pb-2 uppercase text-center">{{ producto.nombre }}</h2>
            
            <div class="flex justify-center mb-6">
                <img :src="producto.imagen ? `/storage/${producto.imagen}` : '/img/sin-foto.png'" class="w-40 h-40 object-cover rounded-lg shadow-md border-4 border-white">
            </div>

            <div class="space-y-2 text-sm">
                <p><strong>Cód. Barras:</strong> {{ producto.sku }}</p>
                <p><strong>Categoría:</strong> {{ producto.categoria?.nombreCategoria }}</p>
                <p><strong>Marca:</strong> {{ producto.marca?.nombreMarca }}</p>
                <p><strong>Stock Mínimo:</strong> {{ producto.stock_minimo }}</p>
                <div class="bg-sky-50 p-3 rounded-lg mt-4">
                    <p class="text-sky-800 font-black text-lg text-center">Precio: ${{ producto.precio_venta }}</p>
                </div>
            </div>

            <button @click="$emit('cerrar')" class="mt-6 w-full bg-slate-800 text-white py-2 rounded-lg font-bold">Cerrar</button>
        </div>
    </div>
</template>