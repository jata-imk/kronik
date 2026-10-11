<script setup>
import { computed, reactive } from "vue";
import SerieFinanciera from "./SerieFinanciera.vue";
const props = defineProps({ resumen: Object });
const paginas = reactive({});
const nombres = {
    creditos: "Cuentas reportadas",
    consultas: "Consultas reportadas",
    domicilios: "Domicilios",
    empleos: "Empleos",
    mensajes: "Mensajes",
    persona: "Identidad reportada",
};
const unidades = { MX: "MXN", US: "USD", UD: "UDI" };
const conteos = computed(() =>
    Object.entries(props.resumen?.conteos ?? {}).filter(
        ([key]) => key !== "persona" && key !== "mensajes",
    ),
);
const formato = (v, codigo) =>
    v == null
        ? "Sin dato"
        : `${new Intl.NumberFormat("es-MX", { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(v))} ${unidades[codigo] ?? codigo}`;
</script>
<template>
    <div v-if="resumen" class="space-y-5">
        <div v-if="conteos.length" class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <section v-for="[key, cantidad] in conteos" :key="key" class="k-surface p-5">
                <p class="text-sm text-muted-color">{{ nombres[key] }}</p>
                <p class="k-financial mt-2 text-3xl font-semibold">{{ cantidad }}</p>
            </section>
        </div>
        <section v-for="grupo in resumen.monedas" :key="grupo.moneda" class="space-y-4">
            <div class="k-surface p-5">
                <h2 class="font-semibold">
                    Saldos reportados · {{ unidades[grupo.moneda] ?? grupo.moneda }}
                </h2>
                <dl class="mt-4 grid gap-4 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm text-muted-color">Saldo actual informado</dt>
                        <dd class="k-financial mt-1 text-2xl font-semibold">
                            {{ formato(grupo.saldo_actual, grupo.moneda) }}
                        </dd>
                        <p class="mt-1 text-sm text-muted-color">
                            {{ grupo.cuentas_con_saldo }} de {{ grupo.cuentas.length }} cuentas con
                            dato
                        </p>
                    </div>
                    <div>
                        <dt class="text-sm text-muted-color">Saldo vencido informado</dt>
                        <dd class="k-financial mt-1 text-2xl font-semibold">
                            {{ formato(grupo.saldo_vencido, grupo.moneda) }}
                        </dd>
                        <p class="mt-1 text-sm text-muted-color">
                            {{ grupo.cuentas_con_vencido }} de {{ grupo.cuentas.length }} cuentas
                            con dato
                        </p>
                    </div>
                </dl>
                <p class="mt-4 text-sm text-muted-color">
                    Son sumas de los saldos informados por cada otorgante, con sus propias fechas de
                    actualización. El vencido puede formar parte del saldo actual; no se suman entre
                    sí. Las monedas se muestran por separado.
                </p>
            </div>
            <SerieFinanciera
                v-if="grupo.cuentas_con_saldo || grupo.cuentas_con_vencido"
                :titulo="`Saldos por cuenta · ${unidades[grupo.moneda] ?? grupo.moneda}`"
                descripcion="Cada cuenta se identifica por su posición en el detalle. Los datos ausentes se muestran como sin dato."
                :filas="
                    grupo.cuentas.slice(
                        paginas[grupo.moneda] ?? 0,
                        (paginas[grupo.moneda] ?? 0) + 8,
                    )
                "
                :series="[
                    { clave: 'actual', nombre: 'Saldo actual' },
                    { clave: 'vencido', nombre: 'Saldo vencido' },
                ]"
                tipo="bar"
                :moneda="false"
                :unidad="unidades[grupo.moneda] ?? grupo.moneda"
            />
            <Paginator
                v-if="grupo.cuentas.length > 8"
                :first="paginas[grupo.moneda] ?? 0"
                :rows="8"
                :total-records="grupo.cuentas.length"
                @page="(event) => (paginas[grupo.moneda] = event.first)"
            />
        </section>
        <div v-if="resumen.tipos.length" class="grid min-w-0 gap-5 xl:grid-cols-2">
            <SerieFinanciera
                titulo="Composición de cuentas"
                descripcion="Cantidad por código de tipo de crédito informado por el proveedor. El detalle conserva el código original."
                :filas="resumen.tipos"
                :series="[{ clave: 'cantidad', nombre: 'Cuentas' }]"
                tipo="bar"
                :moneda="false"
            /><SerieFinanciera
                titulo="Cuentas con saldo vencido"
                descripcion="Clasificación según el saldo vencido informado; no equivale a una calificación de riesgo."
                :filas="resumen.atraso"
                :series="[{ clave: 'cantidad', nombre: 'Cuentas' }]"
                tipo="bar"
                :moneda="false"
            />
        </div>
        <SerieFinanciera
            v-if="resumen.consultas_meses.length"
            titulo="Consultas registradas por mes"
            :descripcion="`Solo consultas incluidas en este reporte. Registros sin fecha válida: ${resumen.consultas_sin_fecha}.`"
            :filas="resumen.consultas_meses"
            :series="[{ clave: 'cantidad', nombre: 'Consultas' }]"
            tipo="bar"
            :moneda="false"
        />
    </div>
</template>
