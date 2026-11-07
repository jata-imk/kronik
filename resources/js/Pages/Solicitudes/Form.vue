<script setup>
import OriginacionAyuda from "@/Components/Products/OriginacionAyuda.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import axios from "axios";
import { computed, ref, watch } from "vue";
import { formatMoneyWithCents as money } from "@/Pages/ProductosCrediticios/productValidation";
import "../../../css/originacion.css";

const props = defineProps({ solicitud: { type: Object, default: null }, productos: Array, capacidadesCredito: { type: Object, required: true }, clienteInicial: { type: Object, default: null } });
const selectedClient = ref(props.clienteInicial ? { ...props.clienteInicial, label: [props.clienteInicial.primer_nombre, props.clienteInicial.apellido_paterno, props.clienteInicial.apellido_materno].filter(Boolean).join(" ") } : null);
const suggestions = ref([]);
const searchError = ref("");
let sequence = 0;
const form = useForm({
    cliente_id: null,
    clave_creacion: crypto.randomUUID(),
    lock_version: props.solicitud?.lock_version ?? 0,
    producto_version_id: props.solicitud?.producto_version_id ?? null,
    monto: props.solicitud?.monto == null ? null : Number(props.solicitud.monto),
    plazo: props.solicitud?.plazo ?? null,
    periodicidad: props.solicitud?.periodicidad ?? null,
    metodo: props.solicitud?.metodo ?? null,
    destino: props.solicitud?.destino ?? "",
    fecha_estimada: props.solicitud?.fecha_estimada ?? "",
});
const productos = computed(() => props.productos.map((p) => ({ ...p, label: `${p.producto.nombre} — versión ${p.numero}` })));
const selectedProduct = computed(() => props.productos.find((p) => p.id === form.producto_version_id));
const periodicidades = computed(() => selectedProduct.value?.capacidades?.opciones.periodicidades ?? []);
const metodos = computed(() => selectedProduct.value?.capacidades?.opciones.metodos_amortizacion ?? []);
const bullet = computed(() => metodos.value.some((o) => o.value === "bullet"));
const politicaBullet = computed(() => selectedProduct.value?.capacidades?.opciones.politicas_interes_bullet?.[0]);
const cambioProducto = ref("");
const ayuda = (opciones, valor) => opciones.find((o) => o.value === valor)?.ayuda ?? "";
watch(() => form.producto_version_id, () => {
    const cambios = [];
    if (form.periodicidad && !periodicidades.value.some((o) => o.value === form.periodicidad)) {
        form.periodicidad = null;
        cambios.push("periodicidad");
    }
    if (form.metodo && !metodos.value.some((o) => o.value === form.metodo)) {
        form.metodo = null;
        cambios.push("amortización");
    }
    const periodo = periodicidades.value.find((o) => o.value === form.periodicidad);
    if (form.plazo && (!periodo || form.plazo < periodo.plazo_minimo || form.plazo > periodo.plazo_maximo)) {
        form.plazo = null;
        cambios.push("número de pagos");
    }
    cambioProducto.value = cambios.length ? `Revisa ${cambios.join(", ")}: la selección anterior no está permitida por este producto.` : "";
});
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
        const result = { ...data, monto: data.monto == null ? null : Number(data.monto).toFixed(2) };
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
        <template #card-header><PageHeader eyebrow="Operación · Captura" :title="solicitud ? `Editar ${solicitud.folio ?? `SOL-${solicitud.id}`}` : 'Nueva solicitud'" description="Relaciona un cliente y un producto para comenzar su evaluación." /></template>
        <template #card-content>
            <form class="originacion-content originacion-captura space-y-5 p-4 md:p-6" @submit.prevent="save">
                <Message severity="info" :closable="false">Solo se muestran clientes de tu sucursal activa. Guarda un borrador con el cliente y completa las condiciones después. Evaluación y PLD se habilitan al enviar a revisión, no al guardar.</Message>
                <Message v-if="Object.keys(form.errors).length" severity="error" :closable="false">
                    <ul aria-live="polite"><li v-for="(error, key) in form.errors" :key="key">{{ error }}</li></ul>
                </Message>
                <div v-if="solicitud">Cliente: {{ solicitud.cliente.primer_nombre }} {{ solicitud.cliente.apellido_paterno }} · {{ solicitud.sucursal.nombre }}</div>
                <div v-else class="flex flex-col gap-1">
                    <label for="sol-cliente">Cliente (obligatorio)</label>
                    <AutoComplete input-id="sol-cliente" v-model="selectedClient" :suggestions="suggestions" option-label="label" force-selection :min-length="2" :delay="300" fluid empty-search-message="No encontramos clientes en esta sucursal. Prueba otro nombre o apellido." @complete="search" placeholder="Buscar por nombre o apellido">
                        <template #option="{ option }"><div class="flex items-center gap-3"><i class="pi pi-user text-primary" aria-hidden="true" /><div><p class="font-medium">{{ option.label }}</p><small>Cliente #{{ option.id }}</small></div></div></template>
                    </AutoComplete>
                    <small>Escribe al menos dos letras y selecciona una sugerencia. La búsqueda consulta los clientes disponibles en el servidor.</small>
                    <small v-if="searchError" role="alert">{{ searchError }}</small>
                    <Link :href="route('clientes.index')" class="text-primary underline">Consultar clientes y expedientes</Link>
                </div>
                <div class="grid gap-4 md:grid-cols-2">
                    <section class="rounded-2xl border border-surface-200 bg-surface-50 p-5 dark:border-surface-700 dark:bg-surface-900"><h2 class="font-semibold"><i class="pi pi-user mr-2 text-primary" aria-hidden="true" />Cliente seleccionado</h2><template v-if="solicitud || selectedClient?.id"><p class="mt-2 text-lg">{{ solicitud ? `${solicitud.cliente.primer_nombre} ${solicitud.cliente.apellido_paterno}` : selectedClient.label }}</p><p class="text-sm text-surface-500">Cliente #{{ solicitud?.cliente_id ?? selectedClient.id }} · Su expediente se conserva; no necesitas capturarlo otra vez.</p></template><p v-else class="mt-2 text-surface-500">Busca un cliente para comenzar. Es el único dato requerido para guardar un borrador.</p></section>
                    <section class="rounded-2xl border border-surface-200 bg-surface-50 p-5 dark:border-surface-700 dark:bg-surface-900"><h2 class="font-semibold"><i class="pi pi-wallet mr-2 text-primary" aria-hidden="true" />Producto seleccionado</h2><template v-if="selectedProduct"><p class="mt-2 text-lg">{{ selectedProduct.producto.nombre }} · v{{ selectedProduct.numero }}</p><p>{{ money(selectedProduct.monto_minimo) }} a {{ money(selectedProduct.monto_maximo) }} MXN</p><p class="text-sm text-surface-500">Sus reglas determinan los plazos y métodos disponibles.</p></template><p v-else class="mt-2 text-surface-500">Selecciona una versión vigente para definir las condiciones.</p></section>
                </div>
                <Message v-if="!productos.length" severity="warn" :closable="false">No hay productos vigentes. Puedes guardar el borrador; un administrador de productos debe activar una versión antes del envío.</Message>
                <OriginacionAyuda v-if="selectedProduct" :info="selectedProduct.originacion" /><Message v-if="cambioProducto" severity="warn">{{ cambioProducto }}</Message><Message v-for="problema in selectedProduct?.capacidades?.problemas" :key="problema" severity="warn">{{ problema }}</Message><div class="grid gap-5 md:grid-cols-2">
                    <div class="flex flex-col gap-1"><label for="sol-producto">Producto y versión</label><Select input-id="sol-producto" aria-label="Producto y versión" v-model="form.producto_version_id" :options="productos" option-label="label" option-value="id" filter show-clear /><small>Solo versiones activas y vigentes. Revisa las condiciones si cambias de producto.</small></div>
                    <div class="flex flex-col gap-1"><label for="sol-monto">Monto solicitado (MXN)</label><InputNumber input-id="sol-monto" v-model="form.monto" mode="currency" currency="MXN" locale="es-MX" :min-fraction-digits="2" :max-fraction-digits="2" fluid /><small v-if="selectedProduct">Rango: {{ money(selectedProduct.monto_minimo) }} a {{ money(selectedProduct.monto_maximo) }} MXN. Los cargos financiados también deben caber en el máximo.</small><small v-else>Importe solicitado en pesos, con hasta dos decimales.</small></div>
                    <div class="flex flex-col gap-1"><label for="sol-periodicidad">{{ bullet ? "Frecuencia para definir duración" : "Periodicidad" }}</label><Select input-id="sol-periodicidad" aria-label="Periodicidad" v-model="form.periodicidad" :disabled="!periodicidades.length" :options="periodicidades" option-label="label" option-value="value" /><small>{{ ayuda(periodicidades, form.periodicidad) || "Selecciona la frecuencia de pagos del producto." }}</small></div>
                    <div class="flex flex-col gap-1"><label for="sol-plazo">{{ bullet ? "Número de periodos hasta vencer el capital" : "Número de pagos" }}</label><InputText id="sol-plazo" v-model="form.plazo" :disabled="!selectedProduct" inputmode="numeric" maxlength="3" /><small>{{ bullet ? "Define la duración, no el número de abonos de capital. El capital vence al final." : "Cantidad de pagos, no días. Debe estar dentro del plazo permitido por el producto." }}</small><small v-if="politicaBullet">{{ politicaBullet.label }}. {{ politicaBullet.ayuda }}</small></div>
                    <div class="flex flex-col gap-1"><label for="sol-metodo">Amortización</label><Select input-id="sol-metodo" aria-label="Amortización" v-model="form.metodo" :disabled="!metodos.length" :options="metodos" option-label="label" option-value="value" /><small>{{ ayuda(metodos, form.metodo) || "Selecciona un método autorizado por esta versión." }}</small></div>
                    <div class="flex flex-col gap-1"><label for="sol-fecha">Fecha estimada de desembolso</label><input id="sol-fecha" type="date" v-model="form.fecha_estimada" class="rounded border border-surface-300 bg-surface-0 p-2 text-surface-900 dark:bg-surface-900 dark:text-surface-0" /><small>Se usa para proyectar el calendario. No programa una transferencia.</small></div>
                </div>
                <div class="flex flex-col gap-1"><label for="sol-destino">Destino del crédito</label><Textarea id="sol-destino" v-model="form.destino" rows="3" maxlength="2000" /><small>Explica para qué se utilizarán los recursos, por ejemplo: compra de herramientas para el taller.</small></div>
                <div class="flex items-center gap-4"><Button type="submit" v-empresa-operacion="'capturar'" label="Guardar borrador" :loading="form.processing" /><Link :href="solicitud ? route('solicitudes.show', solicitud.id) : route('solicitudes.index')" class="text-primary underline">Volver</Link></div>
            </form>
        </template>
    </AppLayout>
</template>
