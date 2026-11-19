<script setup>
import { reactive, toRef } from "vue";
import { usePreviaCalendarioCrediticio } from "@/Composables/usePreviaCalendarioCrediticio";

const props = defineProps({ version: Object });
const escenario = reactive({
    inicio: props.version.configuracion.desde <= "2026-11-07" && props.version.configuracion.hasta >= "2026-11-17"
        ? "2026-11-07" : props.version.configuracion.desde,
    periodicidad: "semanal", metodo: "capital_fijo", plazo: 1, gracia: 3, politica_bullet: "periodicos",
});
const { resultado, error, cargando, calcular } = usePreviaCalendarioCrediticio(toRef(props, "version"), escenario);
const metodos = [{ label: "Capital fijo", value: "capital_fijo" }, { label: "Cuota nivelada", value: "cuota_nivelada" }, { label: "Bullet", value: "bullet" }];
const periodicidades = [{ label: "Semanal", value: "semanal" }, { label: "Quincenal", value: "quincenal" }, { label: "Mensual", value: "mensual" }];
const politicas = [{ label: "Intereses periódicos", value: "periodicos" }, { label: "Intereses al vencimiento", value: "al_vencimiento" }];
const monedas = new Intl.NumberFormat("es-MX", { style: "currency", currency: "MXN" });
</script>

<template>
    <section class="space-y-5">
        <div>
            <h2 class="mb-1 text-xl font-semibold">Compara dos políticas de vencimiento</h2>
            <p class="mb-0 text-surface-600 dark:text-surface-300">Ejemplo sintético con $12,000 MXN, interés ordinario de 36% anual, moratorio de 18% y gracia efectiva. El cálculo es una vista previa.</p>
        </div>
        <form class="grid items-end gap-3 sm:grid-cols-2 xl:grid-cols-3" @submit.prevent="calcular">
            <div>
                <label for="ejemplo-inicio" class="mb-1 block font-medium">Fecha de disposición</label>
                <DatePicker input-id="ejemplo-inicio" v-model="escenario.inicio" date-format="yy-mm-dd" update-model-type="string" fluid />
            </div>
            <div>
                <label for="ejemplo-periodicidad" class="mb-1 block font-medium">Periodicidad</label>
                <Select input-id="ejemplo-periodicidad" v-model="escenario.periodicidad" :options="periodicidades" option-label="label" option-value="value" fluid />
            </div>
            <div>
                <label for="ejemplo-metodo" class="mb-1 block font-medium">Amortización</label>
                <Select input-id="ejemplo-metodo" v-model="escenario.metodo" :options="metodos" option-label="label" option-value="value" fluid />
            </div>
            <div v-if="escenario.metodo === 'bullet'">
                <label for="ejemplo-bullet" class="mb-1 block font-medium">Exigibilidad de intereses</label>
                <Select input-id="ejemplo-bullet" v-model="escenario.politica_bullet" :options="politicas" option-label="label" option-value="value" fluid />
            </div>
            <div>
                <label for="ejemplo-plazo" class="mb-1 block font-medium">Periodos</label>
                <InputNumber input-id="ejemplo-plazo" v-model="escenario.plazo" :min="1" :max="12" fluid />
            </div>
            <div>
                <label for="ejemplo-gracia" class="mb-1 block font-medium">Días naturales de gracia</label>
                <InputNumber input-id="ejemplo-gracia" v-model="escenario.gracia" :min="0" :max="30" fluid />
            </div>
            <Button type="submit" label="Calcular ejemplo" icon="pi pi-calculator" :loading="cargando" />
        </form>
        <Message v-if="error" severity="error">{{ error }}</Message>
        <div v-if="resultado" class="space-y-3" aria-live="polite">
            <Accordion value="0">
                <AccordionPanel v-for="(cambio, indice) in resultado.cambios" :key="cambio.fecha_original" :value="String(indice)">
                    <AccordionHeader>Periodo {{ indice + 1 }} · {{ cambio.fecha_original }} → {{ cambio.fecha }}</AccordionHeader>
                    <AccordionContent>
                        <ul v-if="cambio.inhabiles.length" class="mb-3 space-y-1">
                            <li v-for="dia in cambio.inhabiles" :key="dia.fecha"><i class="pi pi-flag mr-1" aria-hidden="true" />{{ dia.fecha }} · {{ dia.motivo }}</li>
                        </ul>
                        <p v-else class="mb-3">La fecha original es hábil.</p>
                        <div class="grid gap-3 sm:grid-cols-2">
                            <section v-for="{clave, titulo} in [{clave:'original', titulo:'Conservar la fecha'}, {clave:'ajustado', titulo:'Siguiente día hábil'}]" :key="clave" class="rounded-lg bg-surface-100 p-3 dark:bg-surface-800">
                                <h3 class="mb-2 font-semibold">{{ titulo }}</h3>
                                <dl v-for="fila in [resultado[clave].periodos[indice]]" :key="fila.fecha" class="space-y-1">
                                    <div><dt class="inline font-medium">Cierre de periodo </dt><dd class="inline">{{ fila.fecha }} · {{ fila.dias }} días</dd></div>
                                    <div><dt class="inline font-medium">Interés del periodo </dt><dd class="inline">{{ monedas.format(fila.devengado) }}</dd></div>
                                    <div><dt class="inline font-medium">Interés exigible </dt><dd class="inline">{{ monedas.format(fila.interes) }}</dd></div>
                                    <template v-if="fila.exigible">
                                        <div><dt class="inline font-medium">Exigibilidad </dt><dd class="inline">{{ fila.fecha }}</dd></div>
                                        <div><dt class="inline font-medium">Atraso si queda impago </dt><dd class="inline">{{ fila.atraso_desde }}</dd></div>
                                        <div><dt class="inline font-medium">Gracia hasta </dt><dd class="inline">{{ fila.gracia_hasta ?? 'Sin días de gracia' }}</dd></div>
                                        <div><dt class="inline font-medium">Interés moratorio desde </dt><dd class="inline">{{ fila.mora_desde }}</dd></div>
                                    </template>
                                    <p v-else class="mb-0">Corte de devengo sin obligación de pago en esta fecha.</p>
                                </dl>
                            </section>
                        </div>
                    </AccordionContent>
                </AccordionPanel>
            </Accordion>
        </div>
    </section>
</template>
