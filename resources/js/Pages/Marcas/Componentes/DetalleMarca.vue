<script setup>
defineProps({ mostrar: Boolean, marca: Object });
defineEmits(['cerrar']);
</script>

<template>
    <div v-if="mostrar && marca" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-sm overflow-hidden border border-slate-100">
            <div class="bg-slate-800 p-4 text-white text-center font-black uppercase tracking-widest text-xs">
                Ficha de Marca
            </div>

            <div class="p-8 text-center">
                <div class="w-24 h-24 mx-auto mb-6 bg-gray-50 rounded-2xl border-2 border-dashed border-gray-200 flex items-center justify-center overflow-hidden shadow-inner">
                    <img v-if="marca.url_imagen" :src="marca.url_imagen" class="w-full h-full object-cover">
                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 text-gray-200" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                </div>

                <h3 class="text-2xl font-black text-slate-800 uppercase tracking-tighter">{{ marca.nombreMarca }}</h3>
                <p class="text-slate-400 font-mono text-xs mt-1 italic leading-none">ID Interno: #{{ marca.id }}</p>
                
                <div class="mt-6 inline-block px-4 py-1.5 rounded-full shadow-sm" :class="marca.estado ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600'">
                    <span class="text-[10px] font-black uppercase tracking-widest">
                        Estado: {{ marca.estado ? 'Activa' : 'Inactiva' }}
                    </span>
                </div>

                <div class="mt-8 pt-6 border-t border-slate-50">
                    <button @click="$emit('cerrar')" class="w-full bg-slate-800 text-white py-3 rounded-xl font-bold hover:bg-slate-900 transition-all active:scale-95 shadow-lg shadow-slate-200">
                        Cerrar Ficha
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>