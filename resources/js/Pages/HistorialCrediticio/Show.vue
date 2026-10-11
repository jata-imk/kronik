<script setup>
import { Link, router, useForm } from "@inertiajs/vue3";
import { onUnmounted, reactive, watch } from "vue";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import SerieFinanciera from "@/Components/Creditos/SerieFinanciera.vue";
import ResumenSic from "@/Components/Creditos/ResumenSic.vue";
const props = defineProps({
    consulta: Object,
    resultado: Object,
    resumen: Object,
    eventos: Array,
    puedeConciliar: Boolean,
    evolucion: { type: Array, default: () => [] },
});
const conciliacion = useForm({ motivo: "", referencia: "", confirmacion_revision: false });
const paginas = reactive({});
const estado = (key) =>
    ({
        preparada: "Preparada",
        enviando: "En proceso",
        incierta: "Resultado incierto",
        completada: "Completada",
        cancelada: "Cancelada",
        conciliada_sin_resultado: "Conciliada sin resultado",
        respuesta_tardia: "Respuesta tardía conservada",
        success: "Resultado registrado",
        pending: "Pendiente",
        error: "Error",
    })[key] ?? "Sin estado acreditado";
const fecha = (v) => (v ? new Date(v).toLocaleString("es-MX") : "Sin fecha");
const labels = {
    creditos: "Cuentas de crédito",
    consultas: "Consultas reportadas",
    domicilios: "Domicilios reportados",
    empleos: "Empleos reportados",
    mensajes: "Mensajes del proveedor",
    persona: "Identidad reportada",
    declaraciones: "Declaraciones del consumidor",
};
const fieldLabels = {
    saldoActual: "Saldo actual",
    saldoVencido: "Saldo vencido",
    limiteCredito: "Límite de crédito",
    nombreOtorgante: "Otorgante",
    tipoCuenta: "Tipo de cuenta (código)",
    tipoCredito: "Tipo de crédito (código)",
    cuentaActual: "Cuenta reportada",
    numeroCuenta: "Cuenta",
    claveUnidadMonetaria: "Moneda (código)",
    numeroPagosVencidos: "Pagos vencidos",
    frecuenciaPagos: "Frecuencia de pagos",
    montoPagar: "Monto a pagar",
    montoUltimoPago: "Último pago",
    fechaUltimoPago: "Fecha del último pago",
    fechaUltimaCompra: "Última compra",
    fechaCierre: "Cierre",
    fechaActualizacion: "Actualización",
    fechaApertura: "Apertura",
    fechaConsulta: "Fecha de consulta",
    fechaReporte: "Fecha del reporte",
    nombreEmpresa: "Empresa",
    CP: "Código postal",
    moneda: "Moneda",
    formaPagoActual: "Forma de pago (código)",
    historicoPagos: "Historial de pagos (códigos)",
    clavePrevencion: "Prevención (código)",
    direccion: "Dirección",
    nombre: "Nombre",
    mensaje: "Mensaje",
};
const title = (key) =>
    fieldLabels[key] ?? key.replace(/([A-Z])/g, " $1").replace(/^./, (c) => c.toUpperCase());
const value = (v) =>
    v == null
        ? "Sin dato"
        : typeof v === "object"
          ? Object.entries(v)
                .map(([k, x]) => `${title(k)}: ${value(x)}`)
                .join(" · ")
          : String(v);
let timer;
watch(
    () => props.consulta.estado_operacion,
    (value) => {
        clearInterval(timer);
        if (["preparada", "enviando"].includes(value))
            timer = setInterval(
                () =>
                    router.reload({
                        only: [
                            "consulta",
                            "resultado",
                            "resumen",
                            "eventos",
                            "evolucion",
                            "puedeConciliar",
                        ],
                        preserveScroll: true,
                    }),
                5000,
            );
    },
    { immediate: true },
);
onUnmounted(() => clearInterval(timer));
</script>
<template>
    <AppLayout title="Resultado SIC"
        ><template #card-header
            ><PageHeader
                eyebrow="Operación · Consultas SIC"
                :title="`Consulta #${consulta.id}`"
                :description="`${consulta.cliente.primer_nombre} ${consulta.cliente.apellido_paterno} · ${consulta.servicio}`"
                ><template #actions
                    ><Link
                        :href="route('clientes.historial-crediticio.show', consulta.cliente.id)"
                        class="k-action-link"
                        >Volver al historial</Link
                    ></template
                ></PageHeader
            ></template
        ><template #card-content
            ><div class="space-y-6 p-4 md:p-6">
                <div class="flex flex-wrap items-center gap-3">
                    <Tag
                        :value="
                            consulta.ambiente === 'qa'
                                ? 'Sandbox · QA'
                                : 'Histórico · procedencia no verificada'
                        "
                        severity="warn"
                    /><Tag
                        :value="estado(consulta.estado_operacion ?? consulta.status)"
                        severity="info"
                    /><span class="text-sm text-muted-color">{{
                        fecha(consulta.fecha_consulta)
                    }}</span>
                </div>
                <Message v-if="!consulta.ambiente" severity="warn" :closable="false"
                    >Este registro se conserva para consulta histórica. Su procedencia no acredita
                    un requisito de aprobación.</Message
                >
                <Message v-if="resultado?.demostrativo" severity="warn" :closable="false"
                    >Resultado demostrativo. No acredita un requisito SIC para aprobación.</Message
                >
                <p v-if="resultado?.folio" class="text-sm text-muted-color">
                    Folio del proveedor:
                    <span class="k-financial font-semibold">{{ resultado.folio }}</span>
                </p>
                <Message
                    v-if="['enviando', 'preparada'].includes(consulta.estado_operacion)"
                    severity="info"
                    :closable="false"
                    >La consulta está en proceso. Esta página actualizará el resultado.</Message
                >
                <Message
                    v-if="consulta.estado_operacion === 'incierta'"
                    severity="warn"
                    :closable="false"
                    >No se pudo confirmar el resultado del proveedor. No se reenviará
                    automáticamente. Solicita conciliación de este intento antes de consultar
                    nuevamente.</Message
                >
                <form
                    v-if="puedeConciliar"
                    class="k-surface space-y-4 p-5"
                    @submit.prevent="
                        conciliacion.post(route('consultas-sic.conciliar', consulta.id))
                    "
                >
                    <h2 class="text-lg font-semibold">Conciliar intento incierto</h2>
                    <p class="text-sm text-muted-color">
                        Revisa el intento con Círculo de Crédito antes de cerrarlo. Esta acción
                        conserva el intento sin acreditar un score y permite preparar una consulta
                        nueva.
                    </p>
                    <label for="sic-conciliar-referencia" class="block font-semibold"
                        >Referencia de revisión con el proveedor *</label
                    ><InputText
                        id="sic-conciliar-referencia"
                        v-model="conciliacion.referencia"
                        maxlength="100"
                        fluid
                    /><label for="sic-conciliar-motivo" class="block font-semibold"
                        >Motivo y resultado de la revisión *</label
                    ><Textarea
                        id="sic-conciliar-motivo"
                        v-model="conciliacion.motivo"
                        minlength="20"
                        maxlength="1000"
                        rows="3"
                        fluid
                    /><label class="flex gap-3"
                        ><Checkbox v-model="conciliacion.confirmacion_revision" binary /><span
                            >Revisé el intento con el proveedor y no hay un resultado acreditable.
                            No incluyo credenciales en esta captura.</span
                        ></label
                    ><Message
                        v-for="(message, key) in conciliacion.errors"
                        :key="key"
                        severity="error"
                        :closable="false"
                        >{{ message }}</Message
                    ><Button
                        label="Cerrar intento sin resultado"
                        type="submit"
                        :disabled="!conciliacion.confirmacion_revision"
                        :loading="conciliacion.processing"
                    />
                </form>
                <Message
                    v-if="['error', 'cancelada'].includes(consulta.estado_operacion)"
                    severity="error"
                    :closable="false"
                    >{{
                        consulta.estado_operacion === "cancelada"
                            ? "El envío se canceló porque sus requisitos cambiaron."
                            : `El proveedor no entregó un resultado utilizable${consulta.http_status ? ` (HTTP ${consulta.http_status})` : ""}. Revisa la habilitación del servicio y sus credenciales QA.`
                    }}</Message
                >
                <div v-if="resultado?.scores?.length" class="grid gap-4 md:grid-cols-2">
                    <section v-for="(score, i) in resultado.scores" :key="i" class="k-surface p-6">
                        <p class="text-sm text-muted-color">{{ score.modelo }}</p>
                        <p class="k-financial my-4 text-5xl font-semibold text-primary">
                            {{ score.valor }}
                        </p>
                        <p class="text-sm">
                            {{
                                resultado.demostrativo || !consulta.ambiente
                                    ? "Valor conservado en este registro."
                                    : "Valor informado por Círculo de Crédito."
                            }}
                            Su interpretación depende del modelo.
                        </p>
                        <p v-if="score.razones?.length" class="mt-3 text-sm">
                            Razones: {{ score.razones.join(" · ") }}
                        </p>
                    </section>
                </div>
                <Message v-if="resultado?.estado === 'sin_score'" severity="info" :closable="false"
                    >El proveedor no entregó un score positivo utilizable. Revisa las razones y la
                    política del producto.</Message
                >
                <Message v-if="resultado?.estado === 'bloqueado'" severity="warn" :closable="false"
                    >La respuesta contiene una condición que requiere revisión. No acredita SIC para
                    aprobación.</Message
                >
                <Message
                    v-if="!resultado || resultado.estado === 'formato_desconocido'"
                    severity="info"
                    :closable="false"
                    >No hay un resultado interpretable disponible. La evidencia original se
                    conserva; no se inventan valores.</Message
                >
                <SerieFinanciera
                    v-if="evolucion.length > 1"
                    titulo="Evolución del mismo modelo"
                    descripcion="Consultas del mismo servicio, modelo y ambiente. No es una evaluación automática de riesgo."
                    :filas="evolucion"
                    :series="[{ clave: 'score', nombre: 'Score informado' }]"
                    :moneda="false"
                />
                <ResumenSic :resumen="resumen" />
                <section
                    v-for="(items, key) in resultado?.secciones ?? {}"
                    :key="key"
                    class="k-surface space-y-4 p-5"
                >
                    <h2 class="text-lg font-semibold">
                        {{ labels[key] }}
                        <span class="text-sm text-muted-color">({{ items.length }})</span>
                    </h2>
                    <p v-if="!items.length" class="text-muted-color">
                        El proveedor no reportó registros en esta sección.
                    </p>
                    <div
                        v-for="(item, i) in items.slice(paginas[key] ?? 0, (paginas[key] ?? 0) + 5)"
                        :key="`${paginas[key]}-${i}`"
                        class="rounded-xl border border-surface p-4"
                    >
                        <dl
                            v-if="typeof item === 'object' && item"
                            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3"
                        >
                            <div v-for="(v, k) in item" :key="k" class="min-w-0">
                                <dt class="text-xs text-muted-color">{{ title(k) }}</dt>
                                <dd class="mt-1 break-words text-sm">{{ value(v) }}</dd>
                            </div>
                        </dl>
                        <p v-else>{{ value(item) }}</p>
                    </div>
                    <Paginator
                        v-if="items.length > 5"
                        :first="paginas[key] ?? 0"
                        :rows="5"
                        :total-records="items.length"
                        @page="(event) => (paginas[key] = event.first)"
                    />
                </section>
                <section class="k-surface p-5">
                    <h2 class="mb-3 font-semibold">Actividad de la consulta</h2>
                    <ol class="space-y-3">
                        <li
                            v-for="e in eventos"
                            :key="e.id"
                            class="flex flex-wrap justify-between gap-2 text-sm"
                        >
                            <span>{{ estado(e.estado) }} · actor #{{ e.actor_id }}</span
                            ><span class="text-muted-color">{{ fecha(e.created_at) }}</span>
                        </li>
                    </ol>
                    <p v-if="!eventos.length" class="text-sm text-muted-color">
                        Este histórico no conserva eventos operativos del adaptador nuevo.
                    </p>
                </section>
            </div></template
        ></AppLayout
    >
</template>
