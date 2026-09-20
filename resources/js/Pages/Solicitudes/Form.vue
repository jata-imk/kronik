<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import axios from "axios";
import { computed, ref } from "vue";

const props = defineProps({ solicitud: { type: Object, default: null }, productos: Array, clienteInicial: { type: Object, default: null } });
const selectedClient = ref(props.clienteInicial ? { ...props.clienteInicial, label: [props.clienteInicial.primer_nombre, props.clienteInicial.apellido_paterno, props.clienteInicial.apellido_materno].filter(Boolean).join(" ") } : null);
const suggestions = ref([]);
const searchError = ref("");
let sequence = 0;
const form = useForm({
    cliente_id: null,
    clave_creacion: crypto.randomUUID(),
    lock_version: props.solicitud?.lock_version ?? 0,
    producto_version_id: props.solicitud?.producto_version_id ?? null,
    monto: props.solicitud?.monto ?? "",
    plazo: props.solicitud?.plazo ?? null,
    periodicidad: props.solicitud?.periodicidad ?? null,
    metodo: props.solicitud?.metodo ?? null,
    destino: props.solicitud?.destino ?? "",
    fecha_estimada: props.solicitud?.fecha_estimada ?? "",
});
const productos = computed(() => props.productos.map((p) => ({ ...p, label: `${p.producto.nombre} — versión ${p.numero}` })));
const selectedProduct = computed(() => props.productos.find((p) => p.id === form.producto_version_id));
const periodicidades = computed(() => selectedProduct.value?.periodicidades.map((p) => ({ label: p.periodicidad, value: p.periodicidad })) ?? []);
const metodos = computed(() => (selectedProduct.value?.reglas?.metodos_amortizacion ?? []).map((m) => ({ label: m === "cuota_nivelada" ? "Cuota nivelada" : "Capital fijo", value: m })));
async function search({ query }) {
    const current = ++sequence;
    searchError.value = "";
    if (query.trim().length < 2) { suggestions.value = []; return; }
    try {
        const { data } = await axios.get(route("solicitudes.clientes"), { params: { buscar: query } });
        if (current !== sequence) return;
        suggestions.value = data.map((c) => ({ ...c, label: [c.primer_nombre, c.apellido_paterno, c.apellido_materno].filter(Boolean).join(" ") }));
    } catch {
        if (current === sequence) { suggestions.value = []; searchError.value = "No se pudo buscar. Intenta nuevamente."; }
    }
}
function save() {
    form.transform((data) => {
        const result = { ...data };
        if (props.solicitud) { delete result.cliente_id; delete result.clave_creacion; }
        else { result.cliente_id = selectedClient.value?.id ?? null; delete result.lock_version; }
        return result;
    });
    if (props.solicitud) form.put(route("solicitudes.update", props.solicitud.id));
    else form.post(route("solicitudes.store"));
}
</script>

<template>
    <AppLayout :title="solicitud ? 'Editar borrador' : 'Nueva solicitud'">
        <template #card-header><h1 class="p-6 text-2xl font-semibold">{{ solicitud ? `Editar SOL-${solicitud.id}` : "Nueva solicitud" }}</h1></template>
        <template #card-content>
            <form class="space-y-5 p-6" @submit.prevent="save">
                <p>Guarda un borrador con el cliente y completa las condiciones después. Solo se muestran clientes de tu sucursal activa.</p>
                <Message v-if="Object.keys(form.errors).length" severity="error" :closable="false">
                    <ul aria-live="polite"><li v-for="(error, key) in form.errors" :key="key">{{ error }}</li></ul>
                </Message>
                <div v-if="solicitud">Cliente: {{ solicitud.cliente.primer_nombre }} {{ solicitud.cliente.apellido_paterno }} · {{ solicitud.sucursal.nombre }}</div>
                <div v-else class="flex flex-col gap-1">
                    <label for="sol-cliente">Cliente (obligatorio)</label>
                    <AutoComplete input-id="sol-cliente" v-model="selectedClient" :suggestions="suggestions" option-label="label" force-selection :min-length="2" @complete="search" placeholder="Escribe al menos dos letras" />
                    <small v-if="searchError" role="alert">{{ searchError }}</small>
                    <Link :href="route('clientes.index')" class="text-primary underline">Consultar clientes y expedientes</Link>
                </div>
                <Message v-if="!productos.length" severity="warn" :closable="false">No hay productos vigentes. Puedes guardar el borrador; un administrador de productos debe activar una versión antes del envío.</Message>
                <div class="grid gap-5 md:grid-cols-2">
                    <div class="flex flex-col gap-1"><label for="sol-producto">Producto y versión</label><Select input-id="sol-producto" aria-label="Producto y versión" v-model="form.producto_version_id" :options="productos" option-label="label" option-value="id" show-clear /></div>
                    <div class="flex flex-col gap-1"><label for="sol-monto">Monto solicitado (MXN)</label><InputText id="sol-monto" v-model="form.monto" inputmode="decimal" /><small v-if="selectedProduct">Rango: {{ selectedProduct.monto_minimo }} a {{ selectedProduct.monto_maximo }} MXN. Los cargos financiados también deben caber en el máximo.</small></div>
                    <div class="flex flex-col gap-1"><label for="sol-periodicidad">Periodicidad</label><Select input-id="sol-periodicidad" aria-label="Periodicidad" v-model="form.periodicidad" :options="periodicidades" option-label="label" option-value="value" /></div>
                    <div class="flex flex-col gap-1"><label for="sol-plazo">Número de pagos</label><InputText id="sol-plazo" v-model="form.plazo" inputmode="numeric" maxlength="3" /></div>
                    <div class="flex flex-col gap-1"><label for="sol-metodo">Amortización</label><Select input-id="sol-metodo" aria-label="Amortización" v-model="form.metodo" :options="metodos" option-label="label" option-value="value" /></div>
                    <div class="flex flex-col gap-1"><label for="sol-fecha">Fecha estimada de desembolso</label><input id="sol-fecha" type="date" v-model="form.fecha_estimada" class="rounded border border-surface-300 bg-surface-0 p-2 text-surface-900 dark:bg-surface-900 dark:text-surface-0" /></div>
                </div>
                <div class="flex flex-col gap-1"><label for="sol-destino">Destino del crédito</label><Textarea id="sol-destino" v-model="form.destino" rows="3" maxlength="2000" /></div>
                <div class="flex items-center gap-4"><Button type="submit" label="Guardar borrador" :loading="form.processing" /><Link :href="solicitud ? route('solicitudes.show', solicitud.id) : route('solicitudes.index')" class="text-primary underline">Volver</Link></div>
            </form>
        </template>
    </AppLayout>
</template>
