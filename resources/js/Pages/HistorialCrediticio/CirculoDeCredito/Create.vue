<script setup>
import { Link, router, useForm } from "@inertiajs/vue3";
import { ref } from "vue";
import axios from "axios";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
const props = defineProps({
    cliente: Object,
    habilitado: Boolean,
    servicios: Array,
    consentimientos: Array,
    revisiones: Array,
    motivoNoDisponible: String,
});
const form = useForm({
    api: "",
    consentimiento_id: null,
    solicitud_revision_id: null,
    idempotency_key: crypto.randomUUID(),
    confirmacion_qa: false,
});
const buscar = ref("");
const opciones = ref([]);
const buscando = ref(false);
const error = ref("");
const nombre = (c) =>
    [c.primer_nombre, c.apellido_paterno, c.apellido_materno].filter(Boolean).join(" ");
async function buscarClientes() {
    buscando.value = true;
    error.value = "";
    try {
        const { data } = await axios.get(route("consultas-sic.clientes"), {
            params: { buscar: buscar.value },
        });
        opciones.value = data;
    } catch {
        error.value = "No se pudo buscar. Escribe al menos dos caracteres y revisa tu sesión.";
    } finally {
        buscando.value = false;
    }
}
function enviar() {
    form.post(route("circulo-credito.store", props.cliente.id));
}
</script>
<template>
    <AppLayout title="Consultar SIC QA">
        <template #card-header
            ><PageHeader
                eyebrow="Operación · Clientes"
                title="Consultar Círculo de Crédito"
                description="Selecciona el servicio y su autorización. Cada consulta conserva su resultado y evidencia."
        /></template>
        <template #card-content
            ><div class="space-y-6 p-4 md:p-6">
                <Link :href="route('clientes.historial-crediticio.index')" class="k-action-link"
                    >Ver historial de consultas</Link
                >
                <section class="k-feature p-6">
                    <Tag value="Sandbox · QA" severity="warn" />
                    <h2 class="mt-4 text-xl font-semibold">Una consulta, un resultado trazable</h2>
                    <p class="mt-2">
                        Usa únicamente personas sintéticas. La consulta no aprueba un crédito:
                        evaluación y cumplimiento revisan la evidencia.
                    </p>
                </section>
                <Message v-if="!habilitado" severity="warn" :closable="false"
                    >Las nuevas consultas requieren habilitar SIC_QA_HABILITADO y credenciales
                    sandbox. Puedes revisar el historial conservado.</Message
                >
                <section v-if="!cliente" class="k-surface space-y-4 p-5">
                    <h2 class="text-lg font-semibold">Seleccionar cliente de tu sucursal</h2>
                    <form class="flex flex-wrap items-end gap-3" @submit.prevent="buscarClientes">
                        <div class="min-w-0 flex-1">
                            <label for="sic-cliente" class="mb-2 block">Nombre o apellido</label
                            ><InputText
                                id="sic-cliente"
                                v-model="buscar"
                                minlength="2"
                                maxlength="100"
                                fluid
                            />
                        </div>
                        <Button type="submit" label="Buscar cliente" :loading="buscando" />
                    </form>
                    <Message v-if="error" severity="error">{{ error }}</Message>
                    <ul class="space-y-2">
                        <li v-for="c in opciones" :key="c.id">
                            <Button
                                :label="nombre(c)"
                                text
                                @click="router.get(route('circulo-credito.create', c.id))"
                            />
                        </li>
                    </ul>
                    <p v-if="!opciones.length" class="text-sm text-muted-color">
                        Busca y selecciona un cliente para ver sus consentimientos.
                    </p>
                </section>
                <div v-else class="grid gap-5 lg:grid-cols-3">
                    <form class="k-surface space-y-5 p-5 lg:col-span-2" @submit.prevent="enviar">
                        <h2 class="text-lg font-semibold">{{ nombre(cliente) }}</h2>
                        <div>
                            <label for="sic-api" class="mb-2 block font-semibold">Servicio *</label
                            ><Select
                                input-id="sic-api"
                                aria-label="Servicio"
                                v-model="form.api"
                                :options="servicios"
                                option-label="nombre"
                                option-value="clave"
                                placeholder="Selecciona un servicio"
                                fluid
                            />
                        </div>
                        <div>
                            <label for="sic-consentimiento" class="mb-2 block font-semibold"
                                >Consentimiento *</label
                            ><Select
                                input-id="sic-consentimiento"
                                aria-label="Consentimiento"
                                v-model="form.consentimiento_id"
                                :options="
                                    consentimientos.map((c) => ({
                                        ...c,
                                        etiqueta: `Autorización #${c.id} · ${c.otorgado_en?.slice(0, 10)}`,
                                    }))
                                "
                                option-label="etiqueta"
                                option-value="id"
                                placeholder="Selecciona la autorización"
                                fluid
                            />
                            <p class="mt-2 text-sm text-muted-color">
                                Su vigencia y archivo se verifican antes del envío.
                            </p>
                        </div>
                        <div>
                            <label for="sic-revision" class="mb-2 block font-semibold"
                                >Solicitud relacionada</label
                            ><Select
                                input-id="sic-revision"
                                aria-label="Solicitud relacionada"
                                v-model="form.solicitud_revision_id"
                                :options="
                                    revisiones.map((r) => ({
                                        ...r,
                                        etiqueta: `Solicitud #${r.solicitud_id} · revisión ${r.numero}`,
                                    }))
                                "
                                option-label="etiqueta"
                                option-value="id"
                                show-clear
                                placeholder="Consulta independiente"
                                fluid
                            />
                            <p class="mt-2 text-sm text-muted-color">
                                Selecciona la revisión vigente si la consulta será evidencia de
                                originación.
                            </p>
                        </div>
                        <Message
                            v-for="(message, key) in form.errors"
                            :key="key"
                            severity="error"
                            :closable="false"
                            >{{ message }}</Message
                        >
                        <label class="flex items-start gap-3"
                            ><Checkbox
                                v-model="form.confirmacion_qa"
                                input-id="sic-confirmacion"
                                binary
                            /><span
                                >Confirmo que el cliente es sintético y la consulta se realizará en
                                simulación QA.</span
                            ></label
                        >
                        <Button
                            type="submit"
                            label="Consultar en sandbox"
                            icon="pi pi-search"
                            :loading="form.processing"
                            :disabled="!habilitado || !form.confirmacion_qa"
                        />
                    </form>
                    <aside class="k-surface space-y-3 p-5">
                        <h2 class="font-semibold">Qué esperar</h2>
                        <p class="text-sm">
                            El resultado puede incluir score, razones o reporte de cuentas según el
                            servicio.
                        </p>
                        <p class="text-sm">
                            Si el proveedor tarda o se pierde la conexión, el resultado puede quedar
                            incierto. Revisa la consulta original antes de realizar otra.
                        </p>
                        <Link
                            :href="route('clientes.expediente.show', cliente.id)"
                            class="k-action-link"
                            >Abrir expediente y autorizaciones</Link
                        >
                    </aside>
                </div>
            </div></template
        >
    </AppLayout>
</template>
