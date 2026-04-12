<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ModalProducto from './Componentes/ModalProducto.vue'; 
import DetalleProducto from './Componentes/DetalleProducto.vue'; 
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import Swal from 'sweetalert2';
import axios from 'axios';

const props = defineProps({ 
    productos: Array, 
    categorias: Array, 
    marcas: Array,
    proveedores: Array,
    sucursales: Array
});

const page = usePage();
const verModal = ref(false);
const verDetalle = ref(false);
const verStock = ref(false); 
const seleccionado = ref(null);

const verAjuste = ref(false);
const verAuditoria = ref(false);
const movimientos = ref([]);

// --- 🚀 NUEVO ESTADO PARA EL MENÚ DESPLEGABLE ---
const menuAbierto = ref(null);

const toggleMenu = (id) => {
    menuAbierto.value = menuAbierto.value === id ? null : id;
};

const cerrarMenu = () => {
    menuAbierto.value = null;
};

const formAjuste = useForm({
    sucursal_id: '',
    tipo_ajuste: 'Restar',
    cantidad: '',
    motivo: 'Rotura o Daño',
});

watch(() => page.props.flash, (nuevo) => {
    if (nuevo.exito) Swal.fire({ title: '¡Éxito!', text: nuevo.exito, icon: 'success', timer: 3000, showConfirmButton: false });
    if (nuevo.error) Swal.fire({ title: 'Error', text: nuevo.error, icon: 'error' });
}, { deep: true });

// Modificamos las funciones para que también cierren el menú al hacer clic
const abrirAjuste = (p) => {
    seleccionado.value = p;
    formAjuste.reset();
    if (props.sucursales && props.sucursales.length === 1) formAjuste.sucursal_id = props.sucursales[0].id;
    cerrarMenu();
    verAjuste.value = true;
};

const guardarAjuste = () => {
    formAjuste.post(route('productos.ajustar', seleccionado.value.id), {
        preserveScroll: true,
        onSuccess: () => { verAjuste.value = false; }
    });
};

const abrirAuditoria = async (p) => {
    seleccionado.value = p;
    cerrarMenu();
    verAuditoria.value = true;
    movimientos.value = [];
    try {
        const respuesta = await axios.get(route('productos.auditoria', p.id));
        movimientos.value = respuesta.data;
    } catch (error) {
        Swal.fire('Error', 'No se pudo cargar el historial', 'error');
    }
};

const abrirNuevo = () => { seleccionado.value = null; verModal.value = true; };
const abrirEditar = (p) => { seleccionado.value = p; cerrarMenu(); verModal.value = true; };
const abrirDetalle = (p) => { seleccionado.value = p; cerrarMenu(); verDetalle.value = true; };
const abrirStock = (p) => { seleccionado.value = p; cerrarMenu(); verStock.value = true; };
const cerrarModalGlobal = () => { verModal.value = false; seleccionado.value = null; };

const toggleEstado = (p) => {
    cerrarMenu();
    const accion = p.estado ? 'desactivar' : 'activar';
    const resultado = p.estado ? 'desactivado' : 'activado';
    const colorConfirm = p.estado ? '#ef4444' : '#10b981';

    Swal.fire({
        title: `¿${accion.toUpperCase()} producto?`,
        text: `El producto "${p.nombre}" cambiará su estado a ${resultado}.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: colorConfirm,
        cancelButtonColor: '#6b7280',
        confirmButtonText: `Sí, ${accion}`,
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.isConfirmed) {
            router.patch(route('productos.status', p.id), {}, {
                onSuccess: () => Swal.fire({ title: '¡Listo!', text: `Producto ${resultado}.`, icon: 'success', confirmButtonColor: '#0284c7' })
            });
        }
    });
};
</script>

<template>
    <Head title="Gestión de Productos" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Control de Inventario</h2>
        </template>

        <div v-if="menuAbierto" @click="cerrarMenu" class="fixed inset-0 z-30"></div>

        <div class="py-12">
            <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-bold text-gray-600 uppercase tracking-wider">Listado de Productos</h3>
                    <button @click="abrirNuevo" class="bg-sky-600 text-white px-6 py-2.5 rounded-lg font-bold shadow-lg hover:bg-sky-700 hover:-translate-y-0.5 transition-all active:scale-95">
                        + Nuevo Producto
                    </button>
                </div>

                <div class="bg-white shadow-xl rounded-2xl border border-gray-100 p-4">
                    <div class="overflow-visible">
                        <table class="w-full text-left border-separate border-spacing-y-2">
                            <thead>
                                <tr class="bg-sky-50 text-sky-900 uppercase text-xs font-black">
                                    <th class="p-4 rounded-l-xl">Cód. Barras</th>
                                    <th class="p-4">Producto</th>
                                    <th class="p-4 text-center">Imagen</th>
                                    <th class="p-4 text-right">Precio Venta</th>
                                    <th class="p-4 text-center">Estado</th>
                                    <th class="p-4 text-center rounded-r-xl">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="productos.length === 0">
                                    <td colspan="6" class="p-10 text-center text-gray-400 italic bg-gray-50 rounded-xl">
                                        No hay productos registrados todavía. ¡Empezá cargando uno nuevo!
                                    </td>
                                </tr>

                                <tr v-for="p in productos" :key="p.id" 
                                    class="bg-white border-b hover:bg-sky-50 transition-all duration-200 group shadow-sm"
                                    :class="{'opacity-50 grayscale': !p.estado}">
                                    
                                    <td class="p-4 font-mono font-bold text-sky-800">{{ p.codigo_barras }}</td>
                                    <td class="p-4 font-bold text-slate-700">{{ p.nombre }}</td>
                                    
                                    <td class="p-4">
                                        <div class="flex justify-center">
                                            <img v-if="p.url_imagen" :src="p.url_imagen" class="w-12 h-12 object-cover rounded-xl border-2 border-white shadow-md group-hover:scale-110 transition-transform">
                                            <div v-else class="w-12 h-12 bg-gray-100 rounded-xl flex items-center justify-center text-gray-300">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" /></svg>
                                            </div>
                                        </div>
                                    </td>

                                    <td class="p-4 text-right font-black text-sky-600 font-mono text-lg">${{ p.precio_venta }}</td>
                                    
                                    <td class="p-4 text-center">
                                        <span :class="p.estado ? 'text-emerald-600 bg-emerald-50' : 'text-rose-600 bg-rose-50'" class="px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-widest shadow-sm">
                                            {{ p.estado ? 'Activo' : 'Baja' }}
                                        </span>
                                    </td>
                                    
                                    <td class="p-4 text-center relative">
                                        <button @click.stop="toggleMenu(p.id)" class="p-2 rounded-full text-slate-400 hover:text-sky-600 hover:bg-sky-100 transition-colors focus:outline-none">
                                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                                        </button>

                                        <div v-if="menuAbierto === p.id" class="absolute right-10 top-10 w-48 bg-white rounded-xl shadow-2xl border border-slate-100 z-40 py-2 animate-in fade-in zoom-in-95 duration-150">
                                            
                                            <button @click="abrirAjuste(p)" class="w-full text-left px-4 py-2.5 text-xs font-bold text-fuchsia-600 hover:bg-fuchsia-50 flex items-center gap-3 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4" /></svg>
                                                Ajustar Stock
                                            </button>

                                            <button @click="abrirAuditoria(p)" class="w-full text-left px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-50 flex items-center gap-3 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                Auditoría
                                            </button>

                                            <button @click="abrirStock(p)" class="w-full text-left px-4 py-2.5 text-xs font-bold text-indigo-600 hover:bg-indigo-50 flex items-center gap-3 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4" /></svg>
                                                Ver Disp. Física
                                            </button>

                                            <button @click="abrirDetalle(p)" class="w-full text-left px-4 py-2.5 text-xs font-bold text-sky-600 hover:bg-sky-50 flex items-center gap-3 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                                                Ver Info Completa
                                            </button>

                                            <button @click="abrirEditar(p)" class="w-full text-left px-4 py-2.5 text-xs font-bold text-amber-600 hover:bg-amber-50 flex items-center gap-3 transition-colors">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" /></svg>
                                                Editar Datos
                                            </button>

                                            <div class="border-t border-slate-100 my-1"></div>

                                            <button @click="toggleEstado(p)" 
                                                class="w-full text-left px-4 py-2.5 text-xs font-bold flex items-center gap-3 transition-colors"
                                                :class="p.estado ? 'text-rose-600 hover:bg-rose-50' : 'text-emerald-600 hover:bg-emerald-50'">
                                                <svg v-if="p.estado" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" /></svg>
                                                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                                {{ p.estado ? 'Dar de Baja' : 'Activar Producto' }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <ModalProducto :mostrar="verModal" :producto="seleccionado" :categorias="categorias" :marcas="marcas" :proveedores="proveedores" @cerrar="cerrarModalGlobal" />
        <DetalleProducto :mostrar="verDetalle" :producto="seleccionado" @cerrar="verDetalle = false" />
        
        <div v-if="verStock && seleccionado" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in-95">
                <div class="bg-indigo-600 p-4 text-white text-center font-black uppercase tracking-widest text-xs">Disponibilidad Física</div>
                <div class="p-6">
                    <h3 class="text-xl font-black text-slate-800 text-center mb-6">{{ seleccionado.nombre }}</h3>
                    <div class="space-y-3">
                        <div v-if="!seleccionado.sucursales || seleccionado.sucursales.length === 0" class="text-center text-slate-500 italic text-sm">Este producto no tiene stock registrado.</div>
                        <div v-for="suc in seleccionado.sucursales" :key="suc.id" class="flex justify-between items-center p-3 border-b border-slate-100 bg-slate-50 rounded-xl">
                            <span class="font-bold text-slate-700">{{ suc.nombre }}</span>
                            <span class="px-3 py-1 bg-indigo-100 text-indigo-700 font-black rounded-lg shadow-sm">
                                {{ Number(suc.pivot?.cantidad_fisica || 0).toFixed(3) }} {{ seleccionado.unidad_medida }}
                            </span>
                        </div>
                    </div>
                    <button @click="verStock = false" class="w-full mt-6 bg-slate-800 text-white py-3 rounded-xl font-bold hover:bg-slate-900 uppercase text-xs tracking-widest shadow-lg">Cerrar Stock</button>
                </div>
            </div>
        </div>

        <div v-if="verAjuste && seleccionado" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden animate-in zoom-in-95">
                <div class="bg-fuchsia-600 p-4 text-white text-center font-black uppercase tracking-widest text-xs">Ajuste Manual de Stock</div>
                <form @submit.prevent="guardarAjuste" class="p-6">
                    <h3 class="text-xl font-black text-slate-800 text-center mb-6">{{ seleccionado.nombre }}</h3>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Sucursal a Ajustar</label>
                            <select v-model="formAjuste.sucursal_id" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:ring-fuchsia-500" required>
                                <option value="" disabled>Seleccione una sucursal...</option>
                                <option v-for="suc in sucursales" :key="suc.id" :value="suc.id">{{ suc.nombre }}</option>
                            </select>
                        </div>
                        <div class="flex gap-4">
                            <div class="w-1/3">
                                <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Operación</label>
                                <select v-model="formAjuste.tipo_ajuste" class="w-full rounded-xl border-slate-200 bg-slate-50 font-bold" :class="formAjuste.tipo_ajuste === 'Restar' ? 'text-rose-600' : 'text-emerald-600'">
                                    <option value="Sumar">+ Ingresar</option>
                                    <option value="Restar">- Descontar</option>
                                </select>
                            </div>
                            <div class="w-2/3">
                                <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Cantidad ({{ seleccionado.unidad_medida }})</label>
                                <input v-model="formAjuste.cantidad" type="number" step="0.001" min="0.001" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:ring-fuchsia-500" placeholder="Ej: 0.250 o 1" required>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[10px] font-black uppercase text-slate-400 mb-1">Motivo del Ajuste</label>
                            <select v-model="formAjuste.motivo" class="w-full rounded-xl border-slate-200 bg-slate-50 focus:ring-fuchsia-500" required>
                                <option>Rotura o Daño</option>
                                <option>Vencimiento</option>
                                <option>Faltante / Robo</option>
                                <option>Sobrante / Encontrado</option>
                                <option>Consumo Interno</option>
                                <option>Corrección de Inventario</option>
                                <option>Otro</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-6 flex gap-3">
                        <button type="button" @click="verAjuste = false" class="w-1/3 py-3 rounded-xl font-bold text-slate-500 hover:bg-slate-100 uppercase text-xs tracking-widest">Cancelar</button>
                        <button type="submit" class="w-2/3 bg-fuchsia-600 text-white py-3 rounded-xl font-bold hover:bg-fuchsia-700 uppercase text-xs tracking-widest shadow-lg" :disabled="formAjuste.processing">Confirmar Ajuste</button>
                    </div>
                </form>
            </div>
        </div>

        <div v-if="verAuditoria && seleccionado" class="fixed inset-0 z-[60] flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden flex flex-col max-h-[90vh]">
                <div class="px-6 py-5 border-b border-slate-100 flex justify-between items-center bg-slate-50">
                    <div>
                        <h3 class="font-black text-xl text-slate-800 tracking-tight flex items-center gap-2">
                            <svg class="w-6 h-6 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                            Auditoría de Stock
                        </h3>
                        <p class="text-xs font-bold text-slate-500 mt-1 uppercase tracking-widest">{{ seleccionado.nombre }} ({{ seleccionado.unidad_medida }})</p>
                    </div>
                    <button @click="verAuditoria = false" class="w-8 h-8 rounded-full bg-slate-200 text-slate-600 flex items-center justify-center hover:bg-slate-300">✕</button>
                </div>
                <div class="p-6 overflow-y-auto flex-1 bg-slate-50">
                    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="bg-slate-100 border-b border-slate-200 text-[10px] uppercase font-black text-slate-500 tracking-widest">
                                    <th class="py-3 px-4">Fecha y Hora</th>
                                    <th class="py-3 px-4">Sucursal</th>
                                    <th class="py-3 px-4">Usuario / Motivo</th>
                                    <th class="py-3 px-4 text-center">Previo</th>
                                    <th class="py-3 px-4 text-center">Mov.</th>
                                    <th class="py-3 px-4 text-center">Actual</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr v-if="movimientos.length === 0">
                                    <td colspan="6" class="py-10 text-center text-slate-400 italic">No hay registros de movimientos para este producto.</td>
                                </tr>
                                <tr v-for="mov in movimientos" :key="mov.id" class="hover:bg-slate-50/50 transition-colors">
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-700 text-sm">{{ new Date(mov.created_at).toLocaleDateString('es-AR') }}</div>
                                        <div class="text-[10px] text-slate-400 font-mono">{{ new Date(mov.created_at).toLocaleTimeString('es-AR') }}</div>
                                    </td>
                                    <td class="py-3 px-4 font-bold text-slate-600 text-xs">{{ mov.sucursal }}</td>
                                    <td class="py-3 px-4">
                                        <div class="font-bold text-slate-800 text-xs">{{ mov.tipo_movimiento }}</div>
                                        <div class="text-[10px] text-slate-500 truncate max-w-[200px]" :title="mov.motivo">{{ mov.usuario }} • {{ mov.motivo || 'Sin motivo' }}</div>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono text-xs text-slate-400">{{ Number(mov.cantidad_anterior).toFixed(3) }}</td>
                                    <td class="py-3 px-4 text-center font-mono font-black text-sm" :class="mov.cantidad_movimiento > 0 ? 'text-emerald-500' : 'text-rose-500'">
                                        {{ mov.cantidad_movimiento > 0 ? '+' : '' }}{{ Number(mov.cantidad_movimiento).toFixed(3) }}
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-black text-slate-800 text-sm">{{ Number(mov.cantidad_actual).toFixed(3) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

    </AuthenticatedLayout>
</template>