<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import IndicadorFinanciero from "./IndicadorFinanciero.vue";
import ComposicionCobros from "./ComposicionCobros.vue";
import DetalleFinanciero from "./DetalleFinanciero.vue";
import AtencionCartera from "./AtencionCartera.vue";
import AntiguedadCartera from "./AntiguedadCartera.vue";
import MovimientosPeriodo from "./MovimientosPeriodo.vue";
import SerieFinanciera from "@/Components/Creditos/SerieFinanciera.vue";
import { importeMXN, fechaCorta } from "@/Utils/formatosFinancieros";
const props = defineProps({ datos: Object, filtros: Object, detalle: Object });
defineEmits(["detalle"]);
const titulos = {
    capital: "Capital pendiente",
    vencido: "Importe vencido pendiente",
    atraso: "Créditos con atraso",
    cobros: "Cobros registrados",
    desembolsos: "Desembolsos registrados",
    proximos: "Próximos vencimientos",
};
const corte = computed(() => `Al ${fechaCorta(props.filtros.corte)}`);
const periodo = computed(
    () =>
        `${fechaCorta(props.filtros.desde)}–${fechaCorta(props.filtros.hasta)}`,
);
const movimientos = [
    { clave: "cobros", nombre: "Cobros", tono: "principal" },
    {
        clave: "desembolsos",
        nombre: "Desembolsos efectivos",
        tono: "secundario",
    },
];
const programacion = [
    { clave: "capital", nombre: "Capital", tono: "principal" },
    { clave: "intereses", nombre: "Intereses", tono: "secundario" },
    {
        clave: "cargos_impuestos",
        nombre: "Cargos e impuestos",
        tono: "advertencia",
    },
];
</script>
<template>
    <div v-if="datos" class="space-y-5">
        <div class="k-tablero-panel-head">
            <div>
                <p class="k-eyebrow">Lectura financiera</p>
                <h2 class="text-xl font-semibold">Cartera y movimientos</h2>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="k-tablero-badge">{{ corte }}</span
                ><span class="k-tablero-badge">MXN</span>
            </div>
        </div>
        <Message
            v-if="datos.cobertura.capturas_pendientes"
            severity="warn"
            :closable="false"
        >
            Hay {{ datos.cobertura.capturas_pendientes }} capturas históricas
            pendientes. Completa y verifica su historial para consultar saldos
            consolidados. Los movimientos muestran únicamente lo registrado
            hasta ahora.
            <Link
                class="font-semibold underline"
                :href="
                    route('creditos.index', {
                        scope: filtros.scope,
                        sucursal_id:
                            filtros.scope === 'all'
                                ? filtros.sucursal_id
                                : undefined,
                    })
                "
                >Revisar créditos</Link
            >
        </Message>
        <Message
            v-else-if="!datos.cobertura.disponible"
            severity="warn"
            :closable="false"
            >Los saldos y vencimientos requieren actualización o revisión. Hay
            {{ datos.cobertura.verificados }} de
            {{ datos.cobertura.creditos }} créditos con lectura verificada para
            este corte. Los movimientos registrados se muestran por
            separado.</Message
        >
        <div class="k-tablero-indicadores">
            <IndicadorFinanciero
                titulo="Capital pendiente de recuperar"
                :valor="datos.saldos?.capital"
                :ayuda="`${datos.saldos?.creditos ?? 'Sin dato'} créditos · capital prestado, sin efectivo disponible`"
                icono="pi pi-wallet"
                badge="Saldo al corte"
                @abrir="$emit('detalle', 'capital')"
            />
            <IndicadorFinanciero
                titulo="Importe vencido pendiente"
                :valor="datos.saldos?.vencido"
                :ayuda="`Obligaciones e impuestos pendientes · ${corte.toLowerCase()}`"
                icono="pi pi-calendar-times"
                tono="advertencia"
                badge="Vencido"
                @abrir="$emit('detalle', 'vencido')"
            />
            <IndicadorFinanciero
                titulo="Créditos con atraso"
                :valor="datos.saldos?.con_atraso"
                :monetario="false"
                :ayuda="
                    datos.saldos
                        ? `${datos.saldos.clientes_atraso} ${datos.saldos.clientes_atraso === 1 ? 'cliente' : 'clientes'} · vencimiento contractual`
                        : 'Lectura pendiente de verificar'
                "
                icono="pi pi-clock"
                tono="secundario"
                badge="Al corte"
                @abrir="$emit('detalle', 'atraso')"
            />
            <IndicadorFinanciero
                titulo="Cobros registrados"
                :valor="datos.cobros.total"
                :ayuda="`${periodo} · netos de reversos`"
                icono="pi pi-arrow-down-left"
                badge="Periodo"
                @abrir="$emit('detalle', 'cobros')"
            />
        </div>
        <DetalleFinanciero
            :listado="detalle"
            :titulo="titulos[filtros.detalle]"
            @cerrar="$emit('detalle', null)"
        />
        <SerieFinanciera
            eyebrow="Comparativa histórica · meses completos"
            titulo="¿Cómo se mueve la operación?"
            descripcion="Cobros y desembolsos registrados de seis meses completos anteriores al mes de fin del periodo. No representan utilidad ni saldo de caja."
            badge="MXN · meses completos"
            :filas="datos.movimientos_mensuales"
            :series="movimientos"
            tipo="bar"
        />
        <div class="k-tablero-columnas">
            <SerieFinanciera
                eyebrow="Evolución de cartera"
                titulo="Capital pendiente al cierre mensual"
                descripcion="Últimos seis cierres hasta el corte. Los cierres sin lectura verificada aparecen sin dato."
                badge="MXN · saldos al cierre"
                :filas="datos.capital_mensual"
                :series="[
                    {
                        clave: 'capital',
                        nombre: 'Capital pendiente',
                        tono: 'principal',
                    },
                ]"
                area
            />
            <ComposicionCobros
                :cobros="datos.cobros"
                :periodo="periodo"
                @detalle="$emit('detalle', $event)"
            />
        </div>
        <div v-if="datos.cobertura.disponible" class="k-tablero-columnas">
            <AtencionCartera
                :atencion="datos.atencion"
                :corte="filtros.corte"
                @detalle="$emit('detalle', $event)"
            />
            <AntiguedadCartera
                :filas="datos.antiguedad"
                :total="datos.saldos.vencido"
                :corte="corte"
                @detalle="$emit('detalle', $event)"
            />
        </div>
        <div class="k-tablero-columnas">
            <MovimientosPeriodo
                :cobros="datos.cobros.total"
                :desembolsos="datos.desembolsos"
                :recuperado="datos.recuperado"
                :periodo="periodo"
                :corte="corte"
                @detalle="$emit('detalle', $event)"
            />
            <section
                v-if="datos.cobertura.disponible"
                class="k-tablero-programacion space-y-3"
                aria-label="Programación futura"
            >
                <SerieFinanciera
                    eyebrow="Programación futura"
                    titulo="Próximos vencimientos"
                    :descripcion="`Programado por cobrar · ${fechaCorta(datos.proximos_desde)}–${fechaCorta(datos.proximos_hasta)} · siguientes 28 días`"
                    :badge="importeMXN(datos.proximos_total)"
                    :filas="datos.proximos"
                    :series="programacion"
                    tipo="bar"
                    apilada
                />
                <div class="px-5 pb-2 space-y-2">
                    <button
                        type="button"
                        class="k-tablero-text-link"
                        @click="$emit('detalle', 'proximos')"
                    >
                        Ver programación completa
                        <i class="pi pi-arrow-right" aria-hidden="true" />
                    </button>
                    <p class="k-tablero-ayuda">
                        Importes contractuales, no dinero recibido ni
                        garantizado. Incluye cargos e impuestos; no anticipa
                        mora futura.
                    </p>
                </div>
            </section>
        </div>
        <p class="k-tablero-ayuda">
            Zona institucional: {{ datos.zona_horaria }}.
            {{
                datos.cobertura.calculada_at
                    ? `Lecturas calculadas desde ${datos.cobertura.calculada_at}. `
                    : ""
            }}Cada cifra conserva su corte o periodo. Las gráficas ofrecen sus
            datos en tabla.
        </p>
    </div>
</template>
