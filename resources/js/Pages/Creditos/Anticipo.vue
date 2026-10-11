<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import axios from "axios";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import DistribucionPago from "@/Components/Creditos/DistribucionPago.vue";
import SerieFinanciera from "@/Components/Creditos/SerieFinanciera.vue";
import { formatMoneyWithCents as money } from "@/Pages/ProductosCrediticios/productValidation";
const props = defineProps({
    credito: Object,
    situacion: Object,
    hoy: String,
    habilitado: Boolean,
    operaciones: Array,
    opcionales: Array,
});
const form = useForm({
    operacion: props.operaciones[0]?.value,
    fecha_pago: props.hoy,
    importe: null,
    referencia: "",
    comisiones_opcionales: [],
    idempotency_key: crypto.randomUUID(),
    confirmacion_qa: false,
    previa_hash: "",
});
const previa = ref(null);
const loading = ref(false);
const error = ref("");
let revision = 0;
let cotizando = false;
watch(
    () => [
        form.operacion,
        form.fecha_pago,
        form.importe,
        form.referencia,
        ...form.comisiones_opcionales,
    ],
    () => {
        if (cotizando) return;
        revision++;
        previa.value = null;
        form.previa_hash = "";
        form.confirmacion_qa = false;
        error.value = "";
    },
    { flush: "sync" },
);
const series = computed(() => [
    {
        clave: "despues",
        nombre: form.operacion === "liquidacion" ? "Con liquidación" : "Con anticipo",
    },
    { clave: "antes", nombre: "Sin esta operación" },
]);
const evento = (v) =>
    ({
        prepago: "Anticipo parcial",
        liquidacion_anticipada: "Liquidación anticipada",
        liquidacion_ordinaria: "Liquidación al vencimiento",
    })[v] ?? "Sin aplicación definida";
const distribucion = computed(
    () =>
        previa.value?.asignaciones.map((a, i) => ({
            etiqueta: `${i + 1}. ${a.concepto}`,
            importe: a.importe,
            impuesto: a.impuesto,
        })) ?? [],
);
async function revisar() {
    const version = ++revision;
    previa.value = null;
    error.value = "";
    loading.value = true;
    form.clearErrors();
    try {
        const { data } = await axios.post(
            route("creditos.anticipos.preview", props.credito.id),
            form.data(),
        );
        if (version === revision) {
            cotizando = true;
            form.importe = Number(data.importe);
            cotizando = false;
            previa.value = data;
            form.previa_hash = data.previa_hash;
        }
    } catch (e) {
        if (version === revision)
            error.value =
                Object.values(e.response?.data?.errors ?? {})
                    .flat()
                    .join(" ") || "No se pudo calcular. Revisa tu sesión y vuelve a intentarlo.";
    } finally {
        loading.value = false;
    }
}
function guardar() {
    if (!previa.value || !form.confirmacion_qa || loading.value) return;
    form.transform((data) => ({ ...data, importe: previa.value.importe })).post(
        route("creditos.anticipos.store", props.credito.id),
        {
            onError: () => {
                previa.value = null;
                form.previa_hash = "";
                form.confirmacion_qa = false;
            },
        },
    );
}
</script>
<template>
    <AppLayout title="Anticipos y liquidación"
        ><template #card-header
            ><PageHeader
                eyebrow="Operación · Créditos"
                title="Anticipos y liquidación"
                :description="`${credito.cliente.primer_nombre} ${credito.cliente.apellido_paterno} · CR-${credito.id}`"
                ><template #actions
                    ><Link :href="route('creditos.show', credito.id)" class="k-action-link"
                        >Volver al crédito</Link
                    ></template
                ></PageHeader
            ></template
        ><template #card-content
            ><div class="space-y-6 p-4 md:p-6">
                <section class="k-feature p-6">
                    <Tag value="Operación sintética · QA" severity="warn" />
                    <h2 class="mt-4 text-xl font-semibold">
                        Decide con el efecto completo a la vista
                    </h2>
                    <p class="mt-2">
                        Revisa devengos, comisiones, capital y calendario antes de confirmar.
                    </p>
                    <p v-if="situacion.resumen" class="k-financial mt-4 text-2xl font-semibold">
                        Capital insoluto {{ money(situacion.resumen.capital_insoluto) }}
                    </p>
                </section>
                <Message v-if="!habilitado" severity="warn" :closable="false"
                    >Se requiere habilitar pagos y ORIGINACION_ANTICIPOS_QA_HABILITADOS
                    exclusivamente en QA.</Message
                >
                <Message v-if="situacion.error" severity="error" :closable="false">{{
                    situacion.error
                }}</Message>
                <div class="grid gap-5 lg:grid-cols-3">
                    <form class="k-surface space-y-5 p-5 lg:col-span-2" @submit.prevent="revisar">
                        <div class="grid gap-4 sm:grid-cols-2">
                            <div>
                                <label for="anticipo-tipo" class="mb-2 block font-semibold"
                                    >Operación *</label
                                ><Select
                                    input-id="anticipo-tipo"
                                    aria-label="Operación"
                                    v-model="form.operacion"
                                    :options="operaciones"
                                    option-label="label"
                                    option-value="value"
                                    :disabled="form.processing"
                                    fluid
                                />
                            </div>
                            <div>
                                <label for="anticipo-fecha" class="mb-2 block font-semibold"
                                    >Fecha efectiva *</label
                                ><InputText
                                    id="anticipo-fecha"
                                    v-model="form.fecha_pago"
                                    type="date"
                                    :min="credito.fecha_desembolso?.slice(0, 10)"
                                    :max="hoy"
                                    :disabled="form.processing"
                                    fluid
                                />
                            </div>
                        </div>
                        <div>
                            <label for="anticipo-importe" class="mb-2 block font-semibold"
                                >Total recibido (MXN)
                                {{ form.operacion === "prepago" ? "*" : "" }}</label
                            ><InputNumber
                                input-id="anticipo-importe"
                                v-model="form.importe"
                                mode="currency"
                                currency="MXN"
                                locale="es-MX"
                                :min-fraction-digits="2"
                                :max-fraction-digits="2"
                                :disabled="form.processing"
                                fluid
                            />
                            <p class="mt-2 text-sm text-muted-color">
                                {{
                                    form.operacion === "liquidacion"
                                        ? "Déjalo vacío para cotizar el total de liquidación. Al confirmar debe coincidir exactamente."
                                        : "Incluye lo recibido completo: primero se cubren vencidos y devengos, después comisiones y capital anticipado."
                                }}
                            </p>
                        </div>
                        <div>
                            <label for="anticipo-referencia" class="mb-2 block font-semibold"
                                >Referencia única *</label
                            ><InputText
                                id="anticipo-referencia"
                                v-model="form.referencia"
                                minlength="5"
                                maxlength="120"
                                :disabled="form.processing"
                                fluid
                            />
                        </div>
                        <fieldset v-if="opcionales.length" class="space-y-3">
                            <legend class="mb-2 font-semibold">
                                Comisiones opcionales del contrato
                            </legend>
                            <label v-for="c in opcionales" :key="c.indice" class="flex gap-3"
                                ><Checkbox
                                    v-model="form.comisiones_opcionales"
                                    :value="c.indice"
                                /><span
                                    >{{ c.concepto.nombre }} · {{ evento(c.evento_cobro) }}</span
                                ></label
                            >
                            <p class="text-sm text-muted-color">
                                Selecciona únicamente las que corresponden al evento que vas a
                                registrar.
                            </p>
                        </fieldset>
                        <Message v-if="error" severity="error" :closable="false">{{
                            error
                        }}</Message
                        ><Message
                            v-for="(m, k) in form.errors"
                            :key="k"
                            severity="error"
                            :closable="false"
                            >{{ m }}</Message
                        >
                        <Button
                            type="submit"
                            label="Revisar efecto y distribución"
                            icon="pi pi-calculator"
                            :loading="loading"
                            :disabled="
                                !habilitado ||
                                !!situacion.error ||
                                form.processing ||
                                situacion.resumen?.estado_operativo === 'liquidado'
                            "
                        />
                    </form>
                    <aside class="k-surface space-y-4 p-5">
                        <h2 class="font-semibold">Lo que conserva el crédito</h2>
                        <p class="text-sm">
                            El contrato determina si disminuye el plazo o el pago. Cada cambio
                            guarda una nueva versión del calendario.
                        </p>
                        <p class="text-sm">
                            La liquidación cubre saldo y devengos a la fecha; excluye intereses
                            futuros y cuotas eliminadas.
                        </p>
                        <p class="text-sm">
                            El reverso conserva el recibo y restaura el calendario anterior. Solo se
                            admite la última operación sin movimientos posteriores.
                        </p>
                    </aside>
                </div>
                <section v-if="previa" class="space-y-5" aria-label="Vista previa de anticipo">
                    <div class="grid gap-4 sm:grid-cols-3">
                        <section
                            v-for="item in [
                                { nombre: 'Total recibido', valor: previa.importe },
                                { nombre: 'Capital anticipado', valor: previa.capital_anticipado },
                                {
                                    nombre: 'Capital después',
                                    valor: previa.despues.capital_insoluto,
                                },
                            ]"
                            :key="item.nombre"
                            class="k-surface p-5"
                        >
                            <p class="text-sm text-muted-color">{{ item.nombre }}</p>
                            <p class="k-financial mt-2 text-2xl font-semibold">
                                {{ money(item.valor) }}
                            </p>
                        </section>
                    </div>
                    <Message
                        v-if="previa.despues.estado_operativo === 'liquidado'"
                        severity="success"
                        :closable="false"
                        >Esta operación liquidará el crédito. El saldo y los cargos aplicables
                        quedarán en cero.</Message
                    >
                    <section class="k-surface p-5">
                        <h2 class="mb-4 font-semibold">Efecto en las próximas cuotas</h2>
                        <dl class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <dt class="text-sm text-muted-color">Aplicación contractual</dt>
                                <dd class="mt-1 font-semibold">
                                    {{
                                        previa.despues.estado_operativo === "liquidado"
                                            ? "Cierre total"
                                            : previa.proyeccion.aplicacion === "reducir_plazo"
                                              ? "Reducir plazo"
                                              : "Reducir pago"
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-muted-color">Cuotas futuras</dt>
                                <dd class="mt-1 k-financial font-semibold">
                                    {{ previa.proyeccion.cuotas }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-muted-color">Último vencimiento</dt>
                                <dd class="mt-1 font-semibold">
                                    {{
                                        previa.proyeccion.ultimo_vencimiento ??
                                        "Sin cuotas pendientes"
                                    }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm text-muted-color">
                                    Intereses futuros estimados
                                </dt>
                                <dd class="mt-1 k-financial font-semibold">
                                    {{ money(previa.proyeccion.intereses) }}
                                </dd>
                            </div>
                        </dl>
                    </section>
                    <SerieFinanciera
                        titulo="Capital pendiente antes y después"
                        descripcion="Proyección del capital si se cubren puntualmente las cuotas futuras. Cada punto usa la misma fecha de corte; no representa movimientos ya registrados."
                        :filas="previa.comparacion ?? []"
                        :series="series"
                    />
                    <SerieFinanciera
                        titulo="Cómo se distribuye el importe"
                        descripcion="Conceptos e impuestos separados, en MXN."
                        :filas="distribucion"
                        :series="[
                            { clave: 'importe', nombre: 'Concepto' },
                            { clave: 'impuesto', nombre: 'Impuesto' },
                        ]"
                        tipo="bar"
                    />
                    <section class="k-surface space-y-4 p-5">
                        <h2 class="text-lg font-semibold">Distribución y calendario propuestos</h2>
                        <DistribucionPago :filas="previa.asignaciones" /><DataTable
                            :value="previa.cronograma.tabla"
                            scrollable
                            paginator
                            :rows="8"
                            ><Column field="fecha" header="Fecha" /><Column
                                v-for="col in [
                                    { key: 'capital', label: 'Capital' },
                                    { key: 'interes', label: 'Interés proyectado' },
                                    { key: 'impuestos', label: 'Impuestos' },
                                    { key: 'pago_total', label: 'Total proyectado' },
                                ]"
                                :key="col.key"
                                :header="col.label"
                                ><template #body="{ data }">{{
                                    money(data[col.key])
                                }}</template></Column
                            ><template #empty
                                >Liquidación: no quedan cuotas futuras.</template
                            ></DataTable
                        >
                        <label class="flex items-start gap-3"
                            ><Checkbox
                                v-model="form.confirmacion_qa"
                                input-id="anticipo-confirmacion"
                                binary
                            /><span
                                >Revisé cliente, fecha, importe, distribución y calendario. Confirmo
                                esta operación sintética QA.</span
                            ></label
                        ><Button
                            label="Confirmar operación QA"
                            icon="pi pi-check"
                            :loading="form.processing"
                            :disabled="!form.confirmacion_qa || loading"
                            @click="guardar"
                        />
                        <p class="text-sm text-muted-color">
                            La previa no registra dinero. Cambiar la captura o recibir otro
                            movimiento exige revisar nuevamente.
                        </p>
                    </section>
                </section>
            </div></template
        ></AppLayout
    >
</template>
