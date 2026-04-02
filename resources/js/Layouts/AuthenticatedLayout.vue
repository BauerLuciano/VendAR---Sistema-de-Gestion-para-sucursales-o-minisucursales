<script setup>
import Sidebar from '@/Components/Sidebar.vue';
</script>

<template>
    <div class="min-h-screen bg-slate-100 flex">
        
        <Sidebar />

        <div class="flex-1 ml-64 min-h-screen flex flex-col">
            
            <header v-if="$slots.header" class="bg-white shadow-sm border-b sticky top-0 z-40 h-16 flex items-center px-8 justify-between">
                <div class="text-slate-400 font-bold uppercase text-xs tracking-[0.2em]">
                    <slot name="header" />
                </div>
                
            </header>

            <main class="p-8 flex-1">
                <slot />
            </main>

            <footer class="p-4 text-center text-slate-400 text-[10px] font-bold uppercase tracking-widest">
                &copy; 2026 - VENDAR APP - HECHO POR LUCIANO
            </footer>
        </div>
    </div>
</template>