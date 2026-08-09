<script setup>
import CodigoPostalAutocomplete from "@/Components/CodigoPostalAutocomplete.vue";
import IntlTelInput from "@/Components/IntlTelInput.vue";
import PaisSelect from "@/Components/PaisSelect.vue";
import { useDireccionCodigoPostal } from "@/Composables/useDireccionCodigoPostal";
import { useTelefonoInternacional } from "@/Composables/useTelefonoInternacional";
import { useForm } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { useToast } from "primevue/usetoast";
import { computed, nextTick, watch } from "vue";

const props = defineProps({
    configuracion: { type: Object, required: true },
    tiposPersona: { type: Array, default: () => [] },
    regimenesFiscales: { type: Array, default: () => [] },
    paises: { type: Array, default: () => [] },
    zonasHorarias: { type: Array, default: () => [] },
});

const toast = useToast();
const domicilio = props.configuracion?.domicilio_fiscal ?? {};

const form = useForm({
    razon_social: props.configuracion?.razon_social ?? "",
    nombre_comercial: props.configuracion?.nombre_comercial ?? "",
    tipo_persona: props.configuracion?.tipo_persona ?? "moral",
    rfc: props.configuracion?.rfc ?? "",
    regimen_fiscal_id: props.configuracion?.regimen_fiscal_id ?? null,
    domicilio_fiscal: {
        pais_id: domicilio.pais_id ?? null,
        pais_codigo_iso: domicilio.pais_codigo_iso ?? "",
        codigo_postal_id: domicilio.codigo_postal_id ?? null,
        division_admin_uno_id: domicilio.division_admin_uno_id ?? null,
        division_admin_dos_id: domicilio.division_admin_dos_id ?? null,
        division_admin_tres_id: domicilio.division_admin_tres_id ?? null,
        calle: domicilio.calle ?? "",
        numero_exterior: domicilio.numero_exterior ?? "",
        numero_interior: domicilio.numero_interior ?? "",
        colonia: domicilio.colonia ?? "",
        municipio: domicilio.municipio ?? "",
        estado: domicilio.estado ?? "",
        codigo_postal: domicilio.codigo_postal ?? "",
        pais: domicilio.pais ?? "",
    },
    telefono: props.configuracion?.telefono ?? "",
    email: props.configuracion?.email ?? "",
    sitio_web: props.configuracion?.sitio_web ?? "",
    moneda: props.configuracion?.moneda ?? "MXN",
    zona_horaria: props.configuracion?.zona_horaria ?? "America/Mexico_City",
    pais_base: props.configuracion?.pais_base ?? "MX",
    logotipo_path: props.configuracion?.logotipo_path ?? "",
    parametros_operativos: {
        dias_gracia_default:
            props.configuracion?.parametros_operativos?.dias_gracia_default ??
            0,
        hora_corte_operativo:
            props.configuracion?.parametros_operativos?.hora_corte_operativo ??
            "18:00",
    },
    integraciones: {
        circulo_credito_host:
            props.configuracion?.integraciones?.circulo_credito_host ?? "",
        circulo_credito_sandbox:
            props.configuracion?.integraciones?.circulo_credito_sandbox ?? true,
        circulo_credito_api_key: "",
    },
    estatus: props.configuracion?.estatus ?? "borrador",
});

const estatusOptions = [
    { label: "Borrador", value: "borrador" },
    { label: "Activa", value: "activa" },
    { label: "Suspendida", value: "suspendida" },
];

const regimenesCompatibles = computed(() =>
    props.regimenesFiscales.filter((regimen) =>
        form.tipo_persona === "fisica" ? regimen.fisica : regimen.moral,
    ),
);

watch(
    () => form.tipo_persona,
    () => {
        if (
            form.regimen_fiscal_id &&
            !regimenesCompatibles.value.some(
                (regimen) => regimen.id === form.regimen_fiscal_id,
            )
        ) {
            form.regimen_fiscal_id = null;
        }
    },
);

watch(
    () => form.rfc,
    (rfc) => {
        if (typeof rfc === "string") {
            form.rfc = rfc.toUpperCase().trimStart();
        }
    },
);

const {
    localidades,
    aplicarCodigoPostal,
    limpiarUbicacionPostal,
    onLocalidadChange,
} = useDireccionCodigoPostal(() => form.domicilio_fiscal);

const { telefonoInternacional, onChangeNumber: onEmpresaTelefonoChange } =
    useTelefonoInternacional(form, { e164Key: "telefono" });

const submit = () => {
    form.put(route("admin.configuracion-empresa.update"), {
        preserveScroll: true,
        onSuccess: () => {
            form.integraciones.circulo_credito_api_key = "";
            toast.add({
                severity: "success",
                summary: "Configuración actualizada",
                life: 3000,
            });
        },
        onError: async () => {
            await nextTick();
            document.querySelector('[aria-invalid="true"]')?.focus();
        },
    });
};

const error = (field) => form.errors[field];
</script>

<template>
    <AppLayout title="Configuración de empresa">
        <template #card-header>
            <div class="flex items-center p-4">
                <Button
                    icon="pi pi-arrow-left"
                    as="a"
                    :href="route('admin.dashboard')"
                />
                <h2 class="text-2xl font-bold ml-4">
                    Configuración de empresa
                </h2>
            </div>
        </template>

        <template #card-content>
            <form class="p-4 flex flex-col gap-8" @submit.prevent="submit">
                <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <h3 class="md:col-span-2 text-lg font-semibold">
                        Datos legales
                    </h3>

                    <div>
                        <label for="razon_social" class="block text-sm font-medium mb-1">
                            Razón social
                        </label>
                        <InputText
                            id="razon_social"
                            v-model="form.razon_social"
                            :invalid="!!error('razon_social')"
                            fluid
                        />
                        <Message v-if="error('razon_social')" severity="error" size="small">
                            {{ error("razon_social") }}
                        </Message>
                    </div>

                    <div>
                        <label for="nombre_comercial" class="block text-sm font-medium mb-1">
                            Nombre comercial
                        </label>
                        <InputText id="nombre_comercial" v-model="form.nombre_comercial" fluid />
                    </div>

                    <div>
                        <label for="tipo_persona" class="block text-sm font-medium mb-1">
                            Tipo de persona
                        </label>
                        <Select
                            id="tipo_persona"
                            v-model="form.tipo_persona"
                            :options="tiposPersona"
                            option-label="label"
                            option-value="value"
                            :invalid="!!error('tipo_persona')"
                            fluid
                        />
                        <Message v-if="error('tipo_persona')" severity="error" size="small">
                            {{ error("tipo_persona") }}
                        </Message>
                    </div>

                    <div>
                        <label for="rfc" class="block text-sm font-medium mb-1">RFC</label>
                        <InputText
                            id="rfc"
                            v-model="form.rfc"
                            :maxlength="form.tipo_persona === 'fisica' ? 13 : 12"
                            :invalid="!!error('rfc')"
                            fluid
                        />
                        <Message v-if="error('rfc')" severity="error" size="small">
                            {{ error("rfc") }}
                        </Message>
                    </div>

                    <div>
                        <label for="regimen_fiscal_id" class="block text-sm font-medium mb-1">
                            Régimen fiscal
                        </label>
                        <Select
                            id="regimen_fiscal_id"
                            v-model="form.regimen_fiscal_id"
                            :options="regimenesCompatibles"
                            option-value="id"
                            :invalid="!!error('regimen_fiscal_id')"
                            filter
                            fluid
                        >
                            <template #option="{ option }">
                                {{ option.clave }} — {{ option.descripcion }}
                            </template>
                            <template #value="{ value, placeholder }">
                                <span v-if="value">
                                    {{ regimenesCompatibles.find((item) => item.id === value)?.clave }}
                                    —
                                    {{ regimenesCompatibles.find((item) => item.id === value)?.descripcion }}
                                </span>
                                <span v-else>{{ placeholder ?? "Seleccione un régimen" }}</span>
                            </template>
                        </Select>
                        <Message v-if="error('regimen_fiscal_id')" severity="error" size="small">
                            {{ error("regimen_fiscal_id") }}
                        </Message>
                    </div>

                    <div>
                        <label for="estatus" class="block text-sm font-medium mb-1">Estatus</label>
                        <Select
                            id="estatus"
                            v-model="form.estatus"
                            :options="estatusOptions"
                            option-label="label"
                            option-value="value"
                            :invalid="!!error('estatus')"
                            fluid
                        />
                    </div>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <h3 class="md:col-span-3 text-lg font-semibold">Domicilio fiscal</h3>

                    <div>
                        <label for="codigo_postal" class="block text-sm font-medium mb-1">
                            Código postal
                        </label>
                        <CodigoPostalAutocomplete
                            v-model="form.domicilio_fiscal.codigo_postal"
                            input-id="codigo_postal"
                            :invalid="!!error('domicilio_fiscal.codigo_postal')"
                            @changed="limpiarUbicacionPostal"
                            @confirmed="aplicarCodigoPostal"
                        />
                        <Message
                            v-if="error('domicilio_fiscal.codigo_postal')"
                            severity="error"
                            size="small"
                        >
                            {{ error("domicilio_fiscal.codigo_postal") }}
                        </Message>
                    </div>

                    <div>
                        <label for="colonia" class="block text-sm font-medium mb-1">Colonia</label>
                        <Select
                            v-if="localidades.length"
                            id="colonia"
                            v-model="form.domicilio_fiscal.codigo_postal_id"
                            :options="localidades"
                            option-label="nombre"
                            option-value="codigoPostalId"
                            :invalid="!!(error('domicilio_fiscal.codigo_postal_id') || error('domicilio_fiscal.division_admin_tres_id'))"
                            fluid
                            @change="onLocalidadChange"
                        />
                        <InputText
                            v-else
                            id="colonia"
                            v-model="form.domicilio_fiscal.colonia"
                            :invalid="!!(error('domicilio_fiscal.codigo_postal_id') || error('domicilio_fiscal.division_admin_tres_id'))"
                            disabled
                            fluid
                        />
                        <Message
                            v-if="error('domicilio_fiscal.codigo_postal_id') || error('domicilio_fiscal.division_admin_tres_id')"
                            severity="error"
                            size="small"
                        >
                            {{ error("domicilio_fiscal.codigo_postal_id") ?? error("domicilio_fiscal.division_admin_tres_id") }}
                        </Message>
                    </div>

                    <div>
                        <label for="municipio" class="block text-sm font-medium mb-1">
                            Municipio o alcaldía
                        </label>
                        <InputText
                            id="municipio"
                            v-model="form.domicilio_fiscal.municipio"
                            disabled
                            fluid
                        />
                    </div>

                    <div>
                        <label for="estado" class="block text-sm font-medium mb-1">Estado</label>
                        <InputText
                            id="estado"
                            v-model="form.domicilio_fiscal.estado"
                            :invalid="!!error('domicilio_fiscal.estado')"
                            disabled
                            fluid
                        />
                        <Message
                            v-if="error('domicilio_fiscal.estado')"
                            severity="error"
                            size="small"
                        >
                            {{ error("domicilio_fiscal.estado") }}
                        </Message>
                    </div>

                    <div class="md:col-span-2">
                        <label for="calle" class="block text-sm font-medium mb-1">Calle</label>
                        <InputText
                            id="calle"
                            v-model="form.domicilio_fiscal.calle"
                            :invalid="!!error('domicilio_fiscal.calle')"
                            fluid
                        />
                        <Message
                            v-if="error('domicilio_fiscal.calle')"
                            severity="error"
                            size="small"
                        >
                            {{ error("domicilio_fiscal.calle") }}
                        </Message>
                    </div>

                    <div>
                        <label for="numero_exterior" class="block text-sm font-medium mb-1">
                            Número exterior
                        </label>
                        <InputText
                            id="numero_exterior"
                            v-model="form.domicilio_fiscal.numero_exterior"
                            fluid
                        />
                    </div>

                    <div>
                        <label for="numero_interior" class="block text-sm font-medium mb-1">
                            Número interior
                        </label>
                        <InputText
                            id="numero_interior"
                            v-model="form.domicilio_fiscal.numero_interior"
                            fluid
                        />
                    </div>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <h3 class="md:col-span-3 text-lg font-semibold">Operación</h3>

                    <div>
                        <label for="telefono" class="block text-sm font-medium mb-1">Teléfono</label>
                        <IntlTelInput
                            id="telefono"
                            v-model="telefonoInternacional"
                            emit-e164
                            @change-number="onEmpresaTelefonoChange"
                            :intl-tel-input-options="{
                                initialCountry: form.pais_base.toLowerCase(),
                            }"
                            :invalid="!!error('telefono')"
                            fluid
                        />
                        <Message v-if="error('telefono')" severity="error" size="small">
                            {{ error("telefono") }}
                        </Message>
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium mb-1">Email</label>
                        <InputText
                            id="email"
                            v-model="form.email"
                            :invalid="!!error('email')"
                            fluid
                        />
                        <Message v-if="error('email')" severity="error" size="small">
                            {{ error("email") }}
                        </Message>
                    </div>

                    <div>
                        <label for="sitio_web" class="block text-sm font-medium mb-1">Sitio web</label>
                        <InputText
                            id="sitio_web"
                            v-model="form.sitio_web"
                            placeholder="https://..."
                            fluid
                        />
                    </div>

                    <div>
                        <label for="moneda" class="block text-sm font-medium mb-1">Moneda</label>
                        <InputText id="moneda" v-model="form.moneda" maxlength="3" fluid />
                    </div>

                    <div>
                        <label for="zona_horaria" class="block text-sm font-medium mb-1">
                            Zona horaria
                        </label>
                        <Select
                            id="zona_horaria"
                            v-model="form.zona_horaria"
                            :options="zonasHorarias"
                            option-label="label"
                            option-value="value"
                            :invalid="!!error('zona_horaria')"
                            filter
                            fluid
                        />
                        <Message v-if="error('zona_horaria')" severity="error" size="small">
                            {{ error("zona_horaria") }}
                        </Message>
                    </div>

                    <div>
                        <label for="pais_base" class="block text-sm font-medium mb-1">País base</label>
                        <PaisSelect
                            id="pais_base"
                            v-model="form.pais_base"
                            :options="paises"
                            option-value="codigo_iso"
                            :invalid="!!error('pais_base')"
                            fluid
                        />
                        <Message v-if="error('pais_base')" severity="error" size="small">
                            {{ error("pais_base") }}
                        </Message>
                    </div>

                    <div>
                        <label for="dias_gracia" class="block text-sm font-medium mb-1">
                            Días de gracia predeterminados
                        </label>
                        <InputNumber
                            id="dias_gracia"
                            v-model="form.parametros_operativos.dias_gracia_default"
                            :min="0"
                            :max="365"
                            fluid
                        />
                    </div>

                    <div>
                        <label for="hora_corte" class="block text-sm font-medium mb-1">
                            Hora de corte operativo
                        </label>
                        <InputText
                            id="hora_corte"
                            v-model="form.parametros_operativos.hora_corte_operativo"
                            placeholder="18:00"
                            fluid
                        />
                    </div>

                    <div>
                        <label for="logotipo_path" class="block text-sm font-medium mb-1">
                            Ruta del logotipo
                        </label>
                        <InputText
                            id="logotipo_path"
                            v-model="form.logotipo_path"
                            placeholder="/images/logo.png"
                            fluid
                        />
                    </div>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <h3 class="md:col-span-2 text-lg font-semibold">
                        Integración Círculo de Crédito
                    </h3>

                    <div>
                        <label for="cdc_host" class="block text-sm font-medium mb-1">Host</label>
                        <InputText
                            id="cdc_host"
                            v-model="form.integraciones.circulo_credito_host"
                            placeholder="https://services.circulodecredito.com.mx"
                            fluid
                        />
                    </div>

                    <div>
                        <label for="cdc_key" class="block text-sm font-medium mb-1">API key</label>
                        <InputText
                            id="cdc_key"
                            v-model="form.integraciones.circulo_credito_api_key"
                            type="password"
                            autocomplete="new-password"
                            :placeholder="
                                configuracion.integraciones?.circulo_credito_api_key_configurada
                                    ? 'Ya configurada; llenar sólo para reemplazar'
                                    : 'Capturar API key'
                            "
                            fluid
                        />
                    </div>

                    <div class="flex items-center gap-3">
                        <Checkbox
                            v-model="form.integraciones.circulo_credito_sandbox"
                            input-id="cdc-sandbox"
                            binary
                        />
                        <label for="cdc-sandbox">Usar ambiente sandbox</label>
                    </div>
                </section>

                <div class="flex justify-end border-t border-surface-200 dark:border-surface-700 pt-4">
                    <Button
                        label="Guardar configuración"
                        icon="pi pi-save"
                        type="submit"
                        :loading="form.processing"
                    />
                </div>
            </form>
        </template>
    </AppLayout>
</template>
