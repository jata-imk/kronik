<script setup>
import { useToast } from "primevue/usetoast";
import { nextTick, ref } from "vue";
import { useGuardedForm } from "@/Composables/useGuardedForm";
import { confirmFormDiscard } from "@/Utils/unsavedChanges";

const props = defineProps({ version: Object, plantilla: Object, hoy: String, readonly: Boolean });
const emit = defineEmits(["guardado"]);
const toast = useToast();
const editor = ref(null);
const resumenErrores = ref(null);
const copia = (dato) => JSON.parse(JSON.stringify(dato));
const form = useGuardedForm({
    revision: props.version?.publicada_en ? undefined : props.version?.revision,
    configuracion: copia(props.version?.configuracion ?? {
        desde: props.hoy, hasta: props.hoy, semana_habil: [1, 2, 3, 4, 5], fuente: "", feriados: [], excepciones: [],
    }),
});
defineExpose({ confirmarSalida: () => props.readonly || confirmFormDiscard(form) });
const semana = ["Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado", "Domingo"].map((texto, indice) => ({ texto, dia: indice + 1 }));
const grupos = [{ clave: "feriados", titulo: "Feriados" }, { clave: "excepciones", titulo: "Excepciones de apertura o cierre" }];
const tipos = [{ texto: "Apertura", habil: true }, { texto: "Cierre", habil: false }];

function limpiarErroresDe(prefijo) {
    const campos = Object.keys(form.errors).filter(campo => campo.startsWith(prefijo));
    if (campos.length) form.clearErrors(...campos);
}

function cargarPlantilla() {
    form.configuracion = copia(props.plantilla);
    limpiarErroresDe("configuracion");
}

function agregar(clave) {
    form.configuracion[clave].push({ fecha: "", nombre: "", ...(clave === "excepciones" ? { habil: false } : {}) });
}

function quitar(clave, indice) {
    form.configuracion[clave].splice(indice, 1);
    const prefijo = `configuracion.${clave}.`;
    const movidos = {};
    for (const [campo, mensaje] of Object.entries(form.errors)) {
        if (!campo.startsWith(prefijo)) continue;
        const [posicion, ...resto] = campo.slice(prefijo.length).split(".");
        const fila = Number(posicion);
        if (!Number.isInteger(fila) || fila < indice) continue;
        form.clearErrors(campo);
        if (fila > indice) movidos[[`configuracion.${clave}`, fila - 1, ...resto].join(".")] = mensaje;
    }
    form.setError(movidos);
}

async function mostrarErrores() {
    await nextTick();
    const campo = editor.value?.querySelector('[aria-invalid="true"]') ?? resumenErrores.value;
    campo?.focus();
    toast.add({ severity: "error", summary: "Revisa la configuración del calendario", life: 5000 });
}

function guardar() {
    if (props.readonly || form.processing) return;
    const opciones = { onSuccess: () => emit("guardado"), onError: mostrarErrores };
    if (props.version && !props.version.publicada_en) {
        form.put(route("productos-crediticios.calendario.update", props.version.id), opciones);
    } else {
        form.post(route("productos-crediticios.calendario.store"), opciones);
    }
}
</script>

<template>
    <form ref="editor" class="space-y-5" @submit.prevent="guardar">
        <Button v-if="!readonly && plantilla" type="button" label="Cargar plantilla CNBV 2026" icon="pi pi-download" severity="secondary" outlined
            @click="cargarPlantilla" />
        <p v-if="!readonly" id="cal-formato-fecha" class="text-sm text-surface-600 dark:text-surface-300">Escribe las fechas en formato AAAA-MM-DD.</p>
        <div class="grid gap-3 sm:grid-cols-2">
            <div v-for="{ clave, etiqueta } in [{ clave: 'desde', etiqueta: 'Cobertura desde' }, { clave: 'hasta', etiqueta: 'Cobertura hasta' }]" :key="clave">
                <label :for="`cal-${clave}`" class="mb-1 block font-medium">{{ etiqueta }}</label>
                <InputText :id="`cal-${clave}`" v-model="form.configuracion[clave]" :readonly="readonly"
                    :invalid="!!form.errors[`configuracion.${clave}`]" :aria-describedby="readonly ? undefined : 'cal-formato-fecha'" fluid />
            </div>
            <div class="sm:col-span-2">
                <label for="cal-fuente" class="mb-1 block font-medium">Fuente de las fechas</label>
                <InputText id="cal-fuente" v-model="form.configuracion.fuente" :readonly="readonly" fluid />
            </div>
        </div>
        <fieldset>
            <legend class="mb-2 font-medium">Días hábiles de la semana</legend>
            <div class="flex flex-wrap gap-x-4 gap-y-2">
                <div v-for="{dia, texto} in semana" :key="dia" class="flex items-center gap-2">
                    <Checkbox v-model="form.configuracion.semana_habil" :input-id="`semana-${dia}`" :value="dia" :disabled="readonly" />
                    <label :for="`semana-${dia}`">{{ texto }}</label>
                </div>
            </div>
        </fieldset>
        <Accordion :value="['feriados']" multiple>
            <AccordionPanel v-for="{clave, titulo} in grupos" :key="clave" :value="clave">
                <AccordionHeader>{{ titulo }} · {{ form.configuracion[clave].length }}</AccordionHeader>
                <AccordionContent>
                    <p v-if="!form.configuracion[clave].length" class="mt-2 text-surface-500">Aún no hay fechas registradas.</p>
                    <div v-for="(dia, indice) in form.configuracion[clave]" :key="indice" class="mb-2 grid gap-2 sm:grid-cols-[10rem_1fr_auto_auto]">
                        <InputText v-model="dia.fecha" :readonly="readonly" aria-label="Fecha"
                            :invalid="!!form.errors[`configuracion.${clave}.${indice}.fecha`]" :aria-describedby="readonly ? undefined : 'cal-formato-fecha'" fluid />
                        <InputText v-model="dia.nombre" :readonly="readonly" aria-label="Nombre o motivo" placeholder="Nombre o motivo" fluid />
                        <Select v-if="clave === 'excepciones'" v-model="dia.habil" :options="tipos" option-label="texto" option-value="habil" :disabled="readonly" aria-label="Tipo de excepción" />
                        <Button v-if="!readonly" type="button" icon="pi pi-trash" severity="danger" text aria-label="Quitar fecha" @click="quitar(clave, indice)" />
                    </div>
                    <Button v-if="!readonly" type="button" icon="pi pi-plus" label="Agregar fecha" text @click="agregar(clave)" />
                </AccordionContent>
            </AccordionPanel>
        </Accordion>
        <div v-if="form.hasErrors" ref="resumenErrores" tabindex="-1" aria-live="polite">
            <Message v-for="(mensaje, campo) in form.errors" :key="campo" severity="error" class="mb-2">{{ mensaje }}</Message>
        </div>
        <Button v-if="!readonly" type="submit" label="Guardar borrador" icon="pi pi-save" :loading="form.processing" />
    </form>
</template>
