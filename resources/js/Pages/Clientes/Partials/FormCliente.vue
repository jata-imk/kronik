<script setup>
import { useForm, usePage } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import { nextTick, reactive, ref, watch } from "vue";

import FormularioDatosFiscales from "@/Components/FormularioDatosFiscales.vue";
import FormularioDireccion from "@/Components/FormularioDireccion.vue";
import PaisSelect from "@/Components/PaisSelect.vue";
import IntlTelInput from "@components/IntlTelInput.vue";
import MapLibreMap from "@components/MapLibre/MapLibreMap.vue";

import { useDireccionMapConnector } from "@/Composables/MapLibre/useDireccionMapConnector";
import { useTelefonoInternacional } from "@/Composables/useTelefonoInternacional";
import { formatDateOnly, parseDateOnly } from "@/Utils/date";

const toast = useToast();
const page = usePage();
const readOnly = ref(page.props.readOnly || false);
const cliente = ref(page.props.cliente);

const formDatosFiscales = reactive({
    tipo_persona: cliente.value?.datos_fiscales?.tipo_persona ?? "",
    regimen_fiscal_id: cliente.value?.datos_fiscales?.regimen_fiscal_id ?? null,
    curp: cliente.value?.datos_fiscales?.curp ?? "",
    rfc: cliente.value?.datos_fiscales?.rfc ?? "",
    razon_social: cliente.value?.datos_fiscales?.razon_social ?? "",
});

const clienteDireccion = { ...cliente.value?.direcciones[0] };
const formDireccion = reactive({
    tipo: clienteDireccion.tipo ?? "",
    linea_uno: clienteDireccion.linea_uno ?? "",
    linea_dos: clienteDireccion.linea_dos ?? "",
    linea_tres: clienteDireccion.linea_tres ?? "",
    codigo_postal: clienteDireccion.codigo_postal?.codigo ?? "",
    codigo_postal_id: clienteDireccion.codigo_postal_id ?? null,
    pais_id: clienteDireccion.pais_id ?? null,
    division_admin_uno_id: clienteDireccion.division_admin_uno_id ?? null,
    division_admin_dos_id: clienteDireccion.division_admin_dos_id ?? null,
    division_admin_tres_id: clienteDireccion.division_admin_tres_id ?? null,
    datos_adicionales: clienteDireccion.datos_adicionales ?? "",
    coordenadas: {
        lat: clienteDireccion.coordenadas?.lat ?? 0,
        lng: clienteDireccion.coordenadas?.lng ?? 0,
    },
});

const form = useForm({
    primer_nombre: cliente.value?.primer_nombre ?? "",
    segundo_nombre: cliente.value?.segundo_nombre ?? "",
    apellido_paterno: cliente.value?.apellido_paterno ?? "",
    apellido_materno: cliente.value?.apellido_materno ?? "",
    fecha_nacimiento: parseDateOnly(cliente.value?.fecha_nacimiento),
    pais_nacimiento_id: cliente.value?.pais_nacimiento_id ?? null,
    email: cliente.value?.email ?? "",
    sexo: cliente.value?.sexo ?? "",
    telefono_codigo_pais: cliente.value?.telefono_codigo_pais ?? "",
    telefono: cliente.value?.telefono ?? "",
    datos_fiscales: { ...formDatosFiscales },
    direcciones: [{ ...formDireccion }],
});

const paises = ref(page.props.paises);

const { telefonoInternacional, onChangeNumber: onClienteTelefonoChange } =
    useTelefonoInternacional(form);

const sexos = page.props.sexos;
const fechaHoy = new Date();
const fechaMinimaAdultos = ref(new Date());
fechaMinimaAdultos.value.setFullYear(fechaHoy.getFullYear() - 18);

watch(
    () => formDireccion,
    (newVal) => {
        form.direcciones[0] = { ...newVal };
    },
    { deep: true },
);

watch(
    () => formDatosFiscales,
    (newVal) => {
        form.datos_fiscales = { ...newVal };
    },
    { deep: true },
);

const formDireccionErrors = reactive({});
watch(
    () => form.errors,
    () => {
        // Limpiar todo
        for (const k of Object.keys(formDireccionErrors)) {
            delete formDireccionErrors[k];
        }

        // Agregar nuevos
        const newErrors = Object.fromEntries(
            Object.entries(form.errors)
                .filter(([key]) => key.startsWith("direcciones.0"))
                .map(([key, value]) => [
                    key.replace("direcciones.0.", ""),
                    value,
                ]),
        );

        Object.assign(formDireccionErrors, newErrors);
    },
);

const formDatosFiscalesErrors = reactive({});
watch(
    () => form.errors,
    () => {
        // Limpiar todo
        for (const k of Object.keys(formDatosFiscalesErrors)) {
            delete formDatosFiscalesErrors[k];
        }

        // Agregar nuevos
        const newErrors = Object.fromEntries(
            Object.entries(form.errors)
                .filter(([key]) => key.startsWith("datos_fiscales."))
                .map(([key, value]) => [
                    key.replace("datos_fiscales.", ""),
                    value,
                ]),
        );

        Object.assign(formDatosFiscalesErrors, newErrors);
    },
);

const initialLoadFormDireccion = ref({
    divisionesAdministrativas: {
        uno: cliente.value?.direcciones[0]?.division_admin_uno_id
            ? [
                  {
                      id: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.padre.padre.id,
                      nombre: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.padre.padre.nombre,
                      codigo: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.padre.padre.codigo,
                      nivel: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.padre.padre.nivel,
                      tipo: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.padre.padre.tipo,
                  },
              ]
            : [],
        dos: cliente.value?.direcciones[0]?.division_admin_dos_id
            ? [
                  {
                      id: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.padre.id,
                      nombre: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.padre.nombre,
                      codigo: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.padre.codigo,
                      nivel: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.padre.nivel,
                      tipo: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.padre.tipo,
                  },
              ]
            : [],
        tres: cliente.value?.direcciones[0]?.division_admin_tres_id
            ? [
                  {
                      id: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.id,
                      nombre: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.nombre,
                      codigo: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.codigo,
                      nivel: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.nivel,
                      tipo: cliente.value?.direcciones[0].codigo_postal
                          .division_administrativa.tipo,
                      codigoPostalId:
                          cliente.value?.direcciones[0].codigo_postal.id,
                  },
              ]
            : [],
    },
});

const initialLoadFormDatosFiscales = ref({
    tiposPersona: page.props.tiposPersona,
    regimenesFiscales: page.props.regimenesFiscales,
});

const onSubmit = () => {
    const updating = page.props.action === "clientes.update";
    form.transform((data) => ({
        ...data,
        fecha_nacimiento: formatDateOnly(data.fecha_nacimiento),
    }));
    form[updating ? "patch" : "post"](
        route(
            page.props.action ?? "clientes.store",
            page.props.action === "clientes.store" ? null : cliente.value?.id,
        ),
        {
            preserveScroll: true,
            onSuccess: () => {
                if (updating) {
                    form.defaults(form.data());
                }
                toast.add({
                    severity: "success",
                    summary: updating
                        ? "Cliente actualizado exitosamente"
                        : "Cliente creado exitosamente",
                    life: 3000,
                });
            },
            onError: async (errors) => {
                await nextTick();

                const firstInvalidField = document.querySelector(
                    '[aria-invalid="true"]',
                );

                firstInvalidField?.scrollIntoView({
                    behavior: "smooth",
                    block: "center",
                });
                firstInvalidField?.focus();

                toast.add({
                    severity: "error",
                    summary: "No se pudo guardar el cliente",
                    detail: `${Object.keys(errors).length} campo(s) requieren atención.`,
                    life: 5000,
                });
            },
        },
    );
};
</script>

<template>
    <div class="card !p-0 max-w-4xl mx-auto">
        <form
            @submit.prevent="onSubmit"
            class="grid gap-4"
        >
            <Fieldset legend="Datos Personales">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1" for="primer_nombre">Primer Nombre</label>
                        <InputText
                            id="primer_nombre" name="primer_nombre"
                            v-model="form.primer_nombre" :disabled="readOnly"
                            fluid :invalid="!!form.errors.primer_nombre " />
                        <Message v-if="form.errors.primer_nombre" severity="error" size="small">
                            {{ form.errors.primer_nombre }}
                        </Message
                        >
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1" for="primer_nombre">Segundo Nombre</label>
                        <InputText
                            id="segundo_nombre" name="segundo_nombre"
                            v-model="form.segundo_nombre" :disabled="readOnly"
                            fluid :invalid="!!form.errors.segundo_nombre " />
                        <Message v-if="form.errors.segundo_nombre" severity="error" size="small">
                            {{ form.errors.segundo_nombre }}
                        </Message
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" for="apellido_paterno">Primer Apellido</label>
                        <InputText
                            id="apellido_paterno" name="apellido_paterno"
                            v-model="form.apellido_paterno" :disabled="readOnly"
                            fluid :invalid="!!form.errors.apellido_paterno " />
                        <Message v-if="form.errors.apellido_paterno" severity="error" size="small">
                            {{ form.errors.apellido_paterno }}
                        </Message
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" for="apellido_materno">Segundo Apellido</label>
                        <InputText
                            id="apellido_materno" name="apellido_materno"
                            v-model="form.apellido_materno" :disabled="readOnly"
                            fluid :invalid="!!form.errors.apellido_materno " />
                        <Message v-if="form.errors.apellido_materno" severity="error" size="small">
                            {{ form.errors.apellido_materno }}
                        </Message
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" for="fecha_nacimiento">Fecha de nacimiento</label>
                        <DatePicker
                            id="fecha_nacimiento" name="fecha_nacimiento"
                            v-model="form.fecha_nacimiento" :disabled="readOnly"
                            fluid
                            :maxDate="fechaMinimaAdultos" :invalid="!!form.errors.fecha_nacimiento"
                            dateFormat="dd-mm-yy"
                            showIcon iconDisplay="input" />
                        <Message v-if="form.errors.fecha_nacimiento" severity="error" size="small">
                            {{ form.errors.fecha_nacimiento }}
                        </Message
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" for="pais_nacimiento_id">País de nacimiento</label>
                        <PaisSelect
                            id="pais_nacimiento_id" name="pais_nacimiento_id"
                            v-model="form.pais_nacimiento_id" :disabled="readOnly"
                            :options="paises"
                            option-value="id"
                            fluid :invalid="!!form.errors.pais_nacimiento_id" >
                        </PaisSelect>
                        <Message v-if="form.errors.pais_nacimiento_id" severity="error" size="small">
                            {{ form.errors.pais_nacimiento_id }}
                        </Message
                        >

                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" for="sexo">Sexo</label>
                        <Select
                            id="sexo" name="sexo"
                            v-model="form.sexo" :disabled="readOnly"
                            :options="sexos"
                            optionLabel="label"
                            optionValue="value"
                            fluid :invalid="!!form.errors.sexo" />
                        <Message v-if="form.errors.sexo" severity="error" size="small">
                            {{ form.errors.sexo }}
                        </Message
                        >
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <InputText
                            id="email" name="email"
                            v-model="form.email" :disabled="readOnly"
                            fluid :invalid="!!form.errors.email" />
                        <Message v-if="form.errors.email" severity="error" size="small">
                            {{ form.errors.email }}
                        </Message
                        >
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-1" for="telefono">Número de teléfono</label>
                        <IntlTelInput
                            id="telefono" name="telefono"
                            v-model="telefonoInternacional" :disabled="readOnly"
                            emit-e164
                            @change-number="onClienteTelefonoChange"
                            fluid :invalid="!!form.errors.telefono" />
                        <Message v-if="form.errors.telefono" severity="error" size="small">
                            {{ form.errors.telefono }}
                        </Message
                        >
                    </div>
                </div>
            </Fieldset>

            <Fieldset legend="Datos Fiscales" :toggleable="true" :collapsed="false">
                <FormularioDatosFiscales
                    :form="formDatosFiscales"
                    :formErrors="formDatosFiscalesErrors" 
                    :newRecord="!cliente" 
                    :initialLoad="initialLoadFormDatosFiscales"
                    :readOnly="readOnly"
                />
            </Fieldset>

            <Fieldset legend="Dirección" :toggleable="true" :collapsed="false">
                <FormularioDireccion 
                    :form="formDireccion"
                    :formErrors="formDireccionErrors" 
                    :newRecord="!cliente" 
                    :initialLoad="initialLoadFormDireccion"
                    :readOnly="readOnly"
                    :direccionMapConnector="useDireccionMapConnector" />

                <div class="mt-4 w-full h-[400px] sm:h-[300px]">
                    <MapLibreMap />
                </div>
            </Fieldset>

            <Button v-if="!readOnly" label="Guardar Cliente" type="submit" :disabled="form.processing" :loading="form.processing" />
        </form>
    </div>
</template>
