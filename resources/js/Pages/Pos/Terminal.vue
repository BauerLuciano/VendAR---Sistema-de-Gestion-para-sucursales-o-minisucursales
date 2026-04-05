<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router, usePage } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import Swal from 'sweetalert2';

const props = defineProps({
    turno: Object,
    productos: Array,
    clientes: Array
});

const page = usePage();

// Estado general
const buscar = ref('');
const carrito = ref([]);
const metodoPago = ref('Efectivo'); 

// --- NUEVO: ESTADO DEL BUSCADOR DE CLIENTES ---
const clienteSeleccionado = ref(null); 
const busquedaCliente = ref('');
const mostrarDropdownClientes = ref(false);

const clientesFiltradosSelect = computed(() => {
    if (!busquedaCliente.value) return props.clientes;
    return props.clientes.filter(c => 
        c.nombre.toLowerCase().includes(busquedaCliente.value.toLowerCase()) ||
        (c.documento && c.documento.includes(busquedaCliente.value))
    );
});

const seleccionarCliente = (cliente) => {
    clienteSeleccionado.value = cliente ? cliente.id : null;
    mostrarDropdownClientes.value = false;
    busquedaCliente.value = ''; 
};

const clienteActivoObj = computed(() => {
    if (!clienteSeleccionado.value) return null;
    return props.clientes.find(c => c.id === clienteSeleccionado.value);
});
// ----------------------------------------------

// Buscador de Productos
const productosFiltrados = computed(() => {
    if (buscar.value.length < 2) return [];
    return props.productos.filter(p => 
        p.nombre.toLowerCase().includes(buscar.value.toLowerCase()) || 
        (p.codigo_barras && p.codigo_barras.includes(buscar.value))
    );
});

const agregarAlCarrito = (producto) => {
    const existe = carrito.value.find(item => item.id === producto.id);
    if (existe) {
        existe.cantidad++;
    } else {
        carrito.value.push({ ...producto, cantidad: 1 });
    }
    buscar.value = '';
};

const incrementarCantidad = (index) => carrito.value[index].cantidad++;
const decrementarCantidad = (index) => { if (carrito.value[index].cantidad > 1) carrito.value[index].cantidad--; };
const validarCantidad = (index) => { if (!carrito.value[index].cantidad || carrito.value[index].cantidad <= 0) carrito.value[index].cantidad = 1; };
const eliminarDelCarrito = (index) => carrito.value.splice(index, 1);

const totalVenta = computed(() => {
    return carrito.value.reduce((acc, item) => acc + (item.precio_venta * item.cantidad), 0);
});

const finalizarVenta = () => {
    if (carrito.value.length === 0) return;
    
    // Mostramos un loading para que el usuario sepa que se está procesando
    Swal.fire({
        title: 'Procesando cobro...',
        didOpen: () => { Swal.showLoading() },
        allowOutsideClick: false
    });

    router.post(route('ventas.store'), {
        turno_caja_id: props.turno.id, 
        consumidor_id: clienteSeleccionado.value,
        items: carrito.value,
        total: totalVenta.value,
        metodo_pago: metodoPago.value
    }, {
        onSuccess: () => {
            carrito.value = [];
            clienteSeleccionado.value = null;
            Swal.fire({
                icon: 'success',
                title: '¡Venta Exitosa!',
                text: 'El ticket se registró y el stock fue actualizado.',
                timer: 2000
            });
        },
        onError: (errors) => {
            // Cerramos el loading y mostramos el error real
            Swal.close();
            console.error(errors);
            Swal.fire({
                icon: 'error',
                title: 'Error al cobrar',
                text: errors.error || 'Ocurrió un problema inesperado en el servidor.',
            });
        }
    });
};
</script>

<template>
    <Head title="Caja Registradora - VendAR" />

    <AuthenticatedLayout>
        <div class="py-6 px-4 sm:px-6 lg:px-8 bg-slate-50 min-h-screen" @click="mostrarDropdownClientes = false">
            
            <div class="mb-8 flex justify-between items-end">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="bg-sky-100 text-sky-800 text-[10px] font-black px-2 py-1 rounded-md uppercase tracking-widest border border-sky-200">
                            {{ turno.caja.nombre }}
                        </span>
                        <span class="text-xs font-bold text-slate-500">
                            Sucursal: {{ turno.caja.sucursal.nombre }}
                        </span>
                    </div>
                    <h1 class="text-2xl font-black text-slate-800 uppercase tracking-tight">Terminal de Venta</h1>
                </div>
            </div>

            <div class="grid grid-cols-12 gap-6">
                <div class="col-span-12 lg:col-span-8 space-y-6">
                    <div class="bg-white p-2 rounded-2xl shadow-sm border border-slate-200">
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </span>
                            <input 
                                v-model="buscar"
                                type="text" 
                                placeholder="Escaneá código de barras o buscá por nombre..."
                                class="block w-full pl-12 pr-4 py-4 bg-transparent border-none focus:ring-0 text-lg font-medium text-slate-700"
                                autofocus
                            />
                        </div>
                    </div>

                    <div v-if="productosFiltrados.length > 0" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        <div 
                            v-for="p in productosFiltrados" :key="p.id"
                            @click="agregarAlCarrito(p)"
                            class="bg-white p-4 rounded-2xl shadow-sm border border-slate-100 hover:border-sky-500 hover:shadow-md transition-all cursor-pointer group"
                        >
                            <div class="flex items-center gap-4">
                                <div class="w-16 h-16 bg-slate-100 rounded-xl overflow-hidden flex items-center justify-center border border-slate-50">
                                    <img v-if="p.imagen" :src="'/storage/' + p.imagen" class="w-full h-full object-cover" />
                                    <svg v-else xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                </div>
                                <div>
                                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ p.codigo_barras || 'SIN CÓDIGO' }}</p>
                                    <p class="font-bold text-slate-800 leading-tight group-hover:text-sky-600 transition-colors">{{ p.nombre }}</p>
                                    <p class="text-sky-600 font-black mt-1 text-lg">${{ p.precio_venta }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-span-12 lg:col-span-4">
                    <div class="bg-white rounded-3xl shadow-xl shadow-slate-200/50 border border-slate-100 flex flex-col h-[calc(100vh-180px)] sticky top-6">
                        <div class="p-6 border-b border-slate-50 flex flex-col gap-3">
                            <div class="flex justify-between items-center relative" @click.stop>
                                <h2 class="text-xl font-black text-slate-800 flex items-center gap-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-sky-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" /></svg>
                                    Ticket
                                </h2>
                                
                                <div class="relative w-48">
                                    <div 
                                        @click="mostrarDropdownClientes = !mostrarDropdownClientes"
                                        class="bg-slate-100 px-3 py-2 rounded-lg text-xs font-bold text-slate-700 cursor-pointer flex justify-between items-center border border-slate-200 hover:border-sky-300 transition-colors"
                                    >
                                        <span class="truncate">{{ clienteActivoObj ? clienteActivoObj.nombre : 'Consumidor Final' }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </div>
                                    
                                    <div v-if="mostrarDropdownClientes" class="absolute right-0 top-full mt-1 w-64 bg-white border border-slate-200 shadow-2xl rounded-xl z-50 overflow-hidden">
                                        <div class="p-2 border-b border-slate-100">
                                            <input 
                                                v-model="busquedaCliente" 
                                                type="text" 
                                                placeholder="Buscar cliente..." 
                                                class="w-full text-xs font-bold border-slate-200 rounded-md focus:ring-sky-500 focus:border-sky-500 py-1.5"
                                            >
                                        </div>
                                        <ul class="max-h-48 overflow-y-auto">
                                            <li 
                                                @click="seleccionarCliente(null)" 
                                                class="px-4 py-2 text-xs font-bold text-slate-600 hover:bg-sky-50 hover:text-sky-700 cursor-pointer border-b border-slate-50"
                                            >
                                                [ Consumidor Final ]
                                            </li>
                                            <li 
                                                v-for="c in clientesFiltradosSelect" :key="c.id"
                                                @click="seleccionarCliente(c)"
                                                class="px-4 py-2 text-xs font-medium text-slate-700 hover:bg-sky-50 hover:text-sky-700 cursor-pointer border-b border-slate-50"
                                            >
                                                {{ c.nombre }}
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex gap-2 mt-2">
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" v-model="metodoPago" value="Efectivo" class="peer sr-only">
                                    <div class="text-center px-2 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider border-2 peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700 text-slate-400 transition-all">Efectivo</div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" v-model="metodoPago" value="Débito" class="peer sr-only">
                                    <div class="text-center px-2 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider border-2 peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700 text-slate-400 transition-all">Tarj / Transf</div>
                                </label>
                                <label class="flex-1 cursor-pointer">
                                    <input type="radio" v-model="metodoPago" value="Cuenta Corriente" class="peer sr-only">
                                    <div class="text-center px-2 py-2 rounded-lg text-[10px] font-black uppercase tracking-wider border-2 peer-checked:border-rose-500 peer-checked:bg-rose-50 peer-checked:text-rose-700 text-slate-400 transition-all">Fiado (CC)</div>
                                </label>
                            </div>
                        </div>

                        <div class="flex-1 overflow-y-auto p-4 space-y-4">
                            <div v-if="carrito.length === 0" class="h-full flex flex-col items-center justify-center text-slate-300">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                <p class="font-bold">Sin productos</p>
                            </div>

                            <div v-for="(item, index) in carrito" :key="item.id" class="flex flex-col gap-2 p-3 bg-white border border-slate-100 rounded-xl shadow-sm group">
                                <div class="flex justify-between items-start">
                                    <span class="font-bold text-slate-700 text-sm leading-tight pr-2">{{ item.nombre }}</span>
                                    <button @click="eliminarDelCarrito(index)" class="text-slate-300 hover:text-rose-500 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" /></svg>
                                    </button>
                                </div>
                                
                                <div class="flex justify-between items-center mt-1">
                                    <div class="flex items-center bg-slate-100 rounded-lg p-1 border border-slate-200">
                                        <button @click="decrementarCantidad(index)" type="button" class="w-7 h-7 flex items-center justify-center bg-white rounded shadow-sm text-slate-500 hover:text-sky-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4" /></svg>
                                        </button>
                                        <input 
                                            type="number" step="0.001"
                                            v-model.number="item.cantidad" 
                                            @blur="validarCantidad(index)"
                                            class="w-14 text-center bg-transparent border-none text-xs font-black p-0 focus:ring-0 text-slate-800"
                                        >
                                        <button @click="incrementarCantidad(index)" type="button" class="w-7 h-7 flex items-center justify-center bg-white rounded shadow-sm text-slate-500 hover:text-sky-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                                        </button>
                                    </div>
                                    <div class="flex flex-col text-right">
                                        <span class="text-[10px] text-slate-400 font-bold uppercase">${{ item.precio_venta }} c/u</span>
                                        <span class="font-black text-slate-800">${{ (item.cantidad * item.precio_venta).toFixed(2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="p-6 bg-slate-50 rounded-b-3xl border-t border-slate-100">
                            <div class="flex justify-between items-center mb-6">
                                <span class="text-slate-400 font-black uppercase tracking-widest text-[10px]">Total a cobrar</span>
                                <span class="text-4xl font-black text-slate-900 tracking-tighter">${{ totalVenta.toFixed(2) }}</span>
                            </div>

                            <button 
                                @click="finalizarVenta"
                                :disabled="carrito.length === 0"
                                class="w-full bg-sky-600 hover:bg-sky-700 disabled:bg-slate-300 text-white font-black py-4 rounded-2xl shadow-lg shadow-sky-600/30 transition-all uppercase tracking-widest flex items-center justify-center gap-2 active:scale-95"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" /></svg>
                                Cobrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>