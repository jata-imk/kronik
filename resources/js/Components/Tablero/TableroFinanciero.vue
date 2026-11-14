<script setup>
import { computed } from "vue";
import { Link } from "@inertiajs/vue3";
import IndicadorFinanciero from "./IndicadorFinanciero.vue";
import ComposicionCobros from "./ComposicionCobros.vue";
import DetalleFinanciero from "./DetalleFinanciero.vue";
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
</script>

<template>
    <div v-if="datos" class="space-y-6">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <p class="k-eyebrow">Lectura financiera</p>
                <h2 class="text-xl font-semibold">Cartera y movimientos</h2>
            </div>
            <p class="text-sm text-muted-color">
                {{ corte }} · {{ datos.zona_horaria }} · MXN
            </p>
        </div>
        <Message
            v-if="datos.cobertura.capturas_pendientes"
            severity="warn"
            :closable="false"
            >Hay {{ datos.cobertura.capturas_pendientes }} capturas históricas
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
                :ayuda="`${corte} · capital prestado, sin efectivo disponible`"
                icono="pi pi-wallet"
                @abrir="$emit('detalle', 'capital')"
            />
            <IndicadorFinanciero
                titulo="Importe vencido pendiente"
                :valor="datos.saldos?.vencido"
                :ayuda="`${corte} · obligaciones vencidas e impuestos`"
                icono="pi pi-calendar-times"
                @abrir="$emit('detalle', 'vencido')"
            />
            <IndicadorFinanciero
                titulo="Créditos con atraso"
                :valor="datos.saldos?.con_atraso"
                :monetario="false"
                :ayuda="
                    datos.saldos
                        ? `${datos.saldos.clientes_atraso} clientes · vencimiento contractual`
                        : 'Lectura pendiente de verificar'
                "
                icono="pi pi-clock"
                @abrir="$emit('detalle', 'atraso')"
            />
            <IndicadorFinanciero
                titulo="Cobros registrados"
                :valor="datos.cobros.total"
                :ayuda="`${fechaCorta(filtros.desde)}–${fechaCorta(filtros.hasta)} · netos de reversos`"
                icono="pi pi-arrow-down-left"
                @abrir="$emit('detalle', 'cobros')"
            />
        </div>
        <DetalleFinanciero
            :listado="detalle"
            :titulo="titulos[filtros.detalle]"
            @cerrar="$emit('detalle', null)"
        />
        <div class="k-tablero-columnas">
            <ComposicionCobros :cobros="datos.cobros" />
            <section
                class="k-surface k-panel space-y-4"
                aria-labelledby="flujos-periodo"
            >
                <div>
                    <p class="k-eyebrow">Movimientos registrados</p>
                    <h2 id="flujos-periodo" class="text-lg font-semibold">
                        Cobros y desembolsos del periodo
                    </h2>
                </div>
                <p class="text-sm text-muted-color">
                    {{ fechaCorta(filtros.desde) }}–{{
                        fechaCorta(filtros.hasta)
                    }}
                </p>
                <dl class="space-y-4">
                    <div class="flex flex-wrap justify-between gap-2">
                        <dt>Cobros registrados</dt>
                        <dd class="k-financial font-semibold">
                            {{ importeMXN(datos.cobros.total) }}
                        </dd>
                    </div>
                    <div class="flex flex-wrap justify-between gap-2">
                        <dt>Desembolsos efectivos</dt>
                        <dd class="k-financial font-semibold">
                            {{ importeMXN(datos.desembolsos) }}
                        </dd>
                    </div>
                </dl>
                <Button
                    label="Ver desembolsos"
                    severity="secondary"
                    outlined
                    @click="$emit('detalle', 'desembolsos')"
                />
                <hr class="border-surface" />
                <p class="text-sm text-muted-color">
                    Capital recuperado acumulado {{ corte.toLowerCase() }}
                </p>
                <p class="k-financial text-2xl font-semibold">
                    {{ importeMXN(datos.recuperado) }}
                </p>
                <p class="text-sm text-muted-color">
                    Incluye el capital pagado hasta el corte, con correcciones
                    conocidas al consultar. Estos movimientos no representan un
                    saldo bancario.
                </p>
            </section>
        </div>
        <div v-if="datos.cobertura.disponible" class="k-tablero-columnas">
            <section
                class="k-surface k-panel space-y-4"
                aria-labelledby="antiguedad-title"
            >
                <div>
                    <p class="k-eyebrow">Atención prioritaria</p>
                    <h2 id="antiguedad-title" class="text-lg font-semibold">
                        Antigüedad del atraso
                    </h2>
                </div>
                <p class="text-sm text-muted-color">
                    {{ corte }} · agrupado por la obligación pendiente más
                    antigua de cada crédito.
                </p>
                <dl class="space-y-3">
                    <div
                        v-for="fila in datos.antiguedad"
                        :key="fila.etiqueta"
                        class="flex flex-wrap justify-between gap-2"
                    >
                        <dt>{{ fila.etiqueta }}</dt>
                        <dd class="k-financial font-semibold">
                            {{ importeMXN(fila.importe) }}
                        </dd>
                    </div>
                </dl>
                <p class="text-sm text-muted-color">
                    La gracia regula la mora según el contrato; no desplaza el
                    vencimiento. Un Bullet puede tener intereses vencidos y
                    capital aún no vencido.
                </p>
                <Button
                    label="Ver obligaciones vencidas"
                    severity="secondary"
                    outlined
                    @click="$emit('detalle', 'vencido')"
                />
            </section>
            <section class="space-y-3">
                <SerieFinanciera
                    titulo="Próximos vencimientos"
                    :descripcion="`${corte} · siguientes 28 días · programación contractual, no dinero recibido`"
                    :filas="datos.proximos"
                    :series="[
                        { clave: 'capital', nombre: 'Capital' },
                        { clave: 'intereses', nombre: 'Intereses' },
                        {
                            clave: 'total',
                            nombre: 'Total con cargos e impuestos',
                        },
                    ]"
                    tipo="bar"
                /><Button
                    label="Ver vencimientos"
                    severity="secondary"
                    outlined
                    @click="$emit('detalle', 'proximos')"
                />
            </section>
        </div>
        <div class="k-tablero-columnas">
            <SerieFinanciera
                titulo="Cobros y desembolsos por mes"
                descripcion="Seis meses completos anteriores al mes de fin del periodo; importes registrados netos de reversos."
                :filas="datos.movimientos_mensuales"
                :series="[
                    { clave: 'cobros', nombre: 'Cobros' },
                    { clave: 'desembolsos', nombre: 'Desembolsos efectivos' },
                ]"
                tipo="bar"
            /><SerieFinanciera
                titulo="Capital pendiente al cierre mensual"
                descripcion="Últimos seis cierres hasta el corte. Los cortes sin lectura verificada aparecen sin dato."
                :filas="datos.capital_mensual"
                :series="[{ clave: 'capital', nombre: 'Capital pendiente' }]"
            />
        </div>
        <p class="text-sm text-muted-color">
            {{
                datos.cobertura.calculada_at
                    ? `Lecturas calculadas desde ${datos.cobertura.calculada_at}. `
                    : ""
            }}Cada cifra conserva su corte o periodo. Las gráficas permiten
            consultar sus datos en tabla.
        </p>
    </div>
</template>
