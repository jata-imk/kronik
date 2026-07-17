<script setup>
import { computed } from "vue";
import { router, useForm } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { useToast } from "primevue/usetoast";

const props = defineProps({
    team: Object,
    configuracion: Object,
    regimenesFiscales: Array,
    timezones: Array,
});

const toast = useToast();

const defaults = {
    domicilio_fiscal: {},
    horario_operacion: {},
    dias_inhabiles: [],
    reglas_cobranza: {},
    formatos_contrato: {},
    cuentas_bancarias: [],
    contactos: [],
    integraciones: {
        circulo_credito: {
            habilitado: false,
            env_prefix: "CDC",
        },
        geocoding: {
            habilitado: false,
            env_key: "GEOCODING_API_KEY",
        },
    },
};

const normalize = (value, fallback) => value ?? structuredClone(fallback);

const form = useForm({
    razon_social: props.configuracion?.razon_social ?? "",
    nombre_comercial: props.configuracion?.nombre_comercial ?? "",
    rfc: props.configuracion?.rfc ?? "",
    regimen_fiscal_id: props.configuracion?.regimen_fiscal_id ?? null,
    email: props.configuracion?.email ?? "",
    telefono: props.configuracion?.telefono ?? "",
    sitio_web: props.configuracion?.sitio_web ?? "",
    logo_path: props.configuracion?.logo_path ?? "",
    domicilio_fiscal: normalize(props.configuracion?.domicilio_fiscal, defaults.domicilio_fiscal),
    moneda: props.configuracion?.moneda ?? "MXN",
    zona_horaria: props.configuracion?.zona_horaria ?? "America/Mexico_City",
    horario_operacion: normalize(props.configuracion?.horario_operacion, defaults.horario_operacion),
    folio_credito_prefijo: props.configuracion?.folio_credito_prefijo ?? "",
    folio_credito_siguiente: props.configuracion?.folio_credito_siguiente ?? 1,
    dias_inhabiles: normalize(props.configuracion?.dias_inhabiles, defaults.dias_inhabiles),
    reglas_cobranza: normalize(props.configuracion?.reglas_cobranza, defaults.reglas_cobranza),
    formatos_contrato: normalize(props.configuracion?.formatos_contrato, defaults.formatos_contrato),
    cuentas_bancarias: normalize(props.configuracion?.cuentas_bancarias, defaults.cuentas_bancarias),
    contactos: normalize(props.configuracion?.contactos, defaults.contactos),
    integraciones: normalize(props.configuracion?.integraciones, defaults.integraciones),
    activa: Boolean(props.configuracion?.activa),
});

const regimenOptions = computed(() =>
    props.regimenesFiscales.map((regimen) => ({
        label: `${regimen.clave} - ${regimen.descripcion}`,
        value: regimen.id,
    })),
);

const activeRequirements = computed(() => [
    { label: "Razón social", complete: Boolean(form.razon_social) },
    { label: "RFC", complete: Boolean(form.rfc) },
    { label: "Régimen fiscal", complete: Boolean(form.regimen_fiscal_id) },
    { label: "Correo operativo", complete: Boolean(form.email) },
    { label: "Domicilio fiscal", complete: Boolean(form.domicilio_fiscal.calle && form.domicilio_fiscal.codigo_postal && form.domicilio_fiscal.estado && form.domicilio_fiscal.pais) },
]);

const addCuenta = () => {
    form.cuentas_bancarias.push({ banco: "", clabe: "", uso: "" });
};

const removeCuenta = (index) => {
    form.cuentas_bancarias.splice(index, 1);
};

const addContacto = () => {
    form.contactos.push({ nombre: "", email: "", telefono: "" });
};

const removeContacto = (index) => {
    form.contactos.splice(index, 1);
};

const addDiaInhabil = () => {
    form.dias_inhabiles.push("");
};

const removeDiaInhabil = (index) => {
    form.dias_inhabiles.splice(index, 1);
};

const submit = () => {
    form.transform((data) => ({
        ...data,
        rfc: data.rfc?.toUpperCase(),
        moneda: data.moneda?.toUpperCase(),
        integraciones: {
            circulo_credito: {
                habilitado: Boolean(data.integraciones?.circulo_credito?.habilitado),
                env_prefix: data.integraciones?.circulo_credito?.env_prefix?.toUpperCase() || "CDC",
            },
            geocoding: {
                habilitado: Boolean(data.integraciones?.geocoding?.habilitado),
                env_key: data.integraciones?.geocoding?.env_key?.toUpperCase() || "GEOCODING_API_KEY",
            },
        },
    })).put(route("admin.empresa-configuracion.update"), {
        preserveScroll: true,
        onSuccess: () => toast.add({ severity: "success", summary: "Configuración guardada", life: 3000 }),
    });
};
</script>

<template>
    <AppLayout title="Configuración de empresa">
        <template #card-header>
            <div class="flex flex-col gap-2 p-4 md:flex-row md:items-center md:justify-between">
                <div class="flex items-center gap-3">
                    <Button icon="pi pi-arrow-left" text rounded as="a" :href="route('admin.dashboard')" />
                    <div>
                        <h2 class="text-xl font-semibold">Configuración de empresa</h2>
                        <p class="text-sm text-surface-500">{{ team.name }}</p>
                    </div>
                </div>
                <Tag :severity="form.activa ? 'success' : 'warn'" :value="form.activa ? 'Activa' : 'Borrador'" />
            </div>
        </template>

        <template #card-content>
            <form class="flex flex-col gap-8" @submit.prevent="submit">
                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">Razón social</label>
                        <InputText v-model="form.razon_social" fluid />
                        <Message v-if="form.errors.razon_social" severity="error" size="small" variant="simple">{{ form.errors.razon_social }}</Message>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Nombre comercial</label>
                        <InputText v-model="form.nombre_comercial" fluid />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">RFC</label>
                        <InputText v-model="form.rfc" fluid maxlength="13" />
                        <Message v-if="form.errors.rfc" severity="error" size="small" variant="simple">{{ form.errors.rfc }}</Message>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Régimen fiscal</label>
                        <select v-model="form.regimen_fiscal_id" class="w-full rounded-md border border-surface-300 bg-white px-3 py-2 text-sm text-surface-900 shadow-sm outline-none transition-colors focus:border-primary dark:border-surface-700 dark:bg-surface-900 dark:text-surface-0">
                            <option :value="null">Sin régimen</option>
                            <option v-for="regimen in regimenOptions" :key="regimen.value" :value="regimen.value">
                                {{ regimen.label }}
                            </option>
                        </select>
                        <Message v-if="form.errors.regimen_fiscal_id" severity="error" size="small" variant="simple">{{ form.errors.regimen_fiscal_id }}</Message>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Correo operativo</label>
                        <InputText v-model="form.email" fluid />
                        <Message v-if="form.errors.email" severity="error" size="small" variant="simple">{{ form.errors.email }}</Message>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Teléfono</label>
                        <InputText v-model="form.telefono" fluid />
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Sitio web</label>
                        <InputText v-model="form.sitio_web" fluid />
                        <Message v-if="form.errors.sitio_web" severity="error" size="small" variant="simple">{{ form.errors.sitio_web }}</Message>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-medium">Logo</label>
                        <InputText v-model="form.logo_path" fluid placeholder="/storage/logos/kronik.png" />
                    </div>
                </section>

                <Divider />

                <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="xl:col-span-2">
                        <h3 class="mb-3 text-base font-semibold">Domicilio fiscal</h3>
                        <div class="grid gap-3 md:grid-cols-2">
                            <InputText v-model="form.domicilio_fiscal.calle" placeholder="Calle" fluid />
                            <InputText v-model="form.domicilio_fiscal.numero_exterior" placeholder="Número exterior" fluid />
                            <InputText v-model="form.domicilio_fiscal.numero_interior" placeholder="Número interior" fluid />
                            <InputText v-model="form.domicilio_fiscal.colonia" placeholder="Colonia" fluid />
                            <InputText v-model="form.domicilio_fiscal.municipio" placeholder="Municipio" fluid />
                            <InputText v-model="form.domicilio_fiscal.estado" placeholder="Estado" fluid />
                            <InputText v-model="form.domicilio_fiscal.codigo_postal" placeholder="Código postal" fluid />
                            <InputText v-model="form.domicilio_fiscal.pais" placeholder="País" fluid />
                        </div>
                        <Message v-if="form.errors['domicilio_fiscal.calle'] || form.errors['domicilio_fiscal.codigo_postal'] || form.errors['domicilio_fiscal.estado'] || form.errors['domicilio_fiscal.pais']" severity="error" size="small" variant="simple">
                            Completa calle, código postal, estado y país para activar la financiera.
                        </Message>
                    </div>

                    <div>
                        <h3 class="mb-3 text-base font-semibold">Operación</h3>
                        <div class="flex flex-col gap-3">
                            <InputText v-model="form.moneda" maxlength="3" placeholder="MXN" fluid />
                            <select v-model="form.zona_horaria" class="w-full rounded-md border border-surface-300 bg-white px-3 py-2 text-sm text-surface-900 shadow-sm outline-none transition-colors focus:border-primary dark:border-surface-700 dark:bg-surface-900 dark:text-surface-0">
                                <option v-for="timezone in timezones" :key="timezone" :value="timezone">
                                    {{ timezone }}
                                </option>
                            </select>
                            <InputText v-model="form.horario_operacion.lunes_viernes" placeholder="L-V 09:00-18:00" fluid />
                            <InputText v-model="form.horario_operacion.sabado" placeholder="Sábado 10:00-14:00" fluid />
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-base font-semibold">Folios</h3>
                        <div class="flex flex-col gap-3">
                            <InputText v-model="form.folio_credito_prefijo" placeholder="KRN" fluid />
                            <InputText v-model.number="form.folio_credito_siguiente" type="number" min="1" fluid />
                        </div>
                    </div>
                </section>

                <Divider />

                <section class="grid gap-6 lg:grid-cols-3">
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-base font-semibold">Días inhábiles</h3>
                            <Button type="button" icon="pi pi-plus" text rounded @click="addDiaInhabil" />
                        </div>
                        <div class="flex flex-col gap-2">
                            <div v-for="(_, index) in form.dias_inhabiles" :key="index" class="flex gap-2">
                                <InputText v-model="form.dias_inhabiles[index]" placeholder="YYYY-MM-DD" fluid />
                                <Button type="button" icon="pi pi-trash" severity="danger" text rounded @click="removeDiaInhabil(index)" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-base font-semibold">Reglas de cobranza</h3>
                        <div class="flex flex-col gap-3">
                            <InputText v-model.number="form.reglas_cobranza.dias_gracia" type="number" min="0" placeholder="Días de gracia" fluid />
                            <InputText v-model.number="form.reglas_cobranza.contactar_desde_dia" type="number" min="0" placeholder="Contactar desde día" fluid />
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-base font-semibold">Formatos de contrato</h3>
                        <InputText v-model="form.formatos_contrato.contrato_credito_simple" placeholder="plantillas/contratos/credito-simple.docx" fluid />
                    </div>
                </section>

                <Divider />

                <section class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-base font-semibold">Cuentas bancarias</h3>
                            <Button type="button" icon="pi pi-plus" text rounded @click="addCuenta" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <div v-for="(cuenta, index) in form.cuentas_bancarias" :key="index" class="grid gap-2 md:grid-cols-[1fr_1fr_1fr_auto]">
                                <InputText v-model="cuenta.banco" placeholder="Banco" fluid />
                                <InputText v-model="cuenta.clabe" placeholder="CLABE" fluid />
                                <InputText v-model="cuenta.uso" placeholder="Uso" fluid />
                                <Button type="button" icon="pi pi-trash" severity="danger" text rounded @click="removeCuenta(index)" />
                            </div>
                        </div>
                    </div>

                    <div>
                        <div class="mb-3 flex items-center justify-between">
                            <h3 class="text-base font-semibold">Contactos</h3>
                            <Button type="button" icon="pi pi-plus" text rounded @click="addContacto" />
                        </div>
                        <div class="flex flex-col gap-3">
                            <div v-for="(contacto, index) in form.contactos" :key="index" class="grid gap-2 md:grid-cols-[1fr_1fr_1fr_auto]">
                                <InputText v-model="contacto.nombre" placeholder="Nombre" fluid />
                                <InputText v-model="contacto.email" placeholder="Email" fluid />
                                <InputText v-model="contacto.telefono" placeholder="Teléfono" fluid />
                                <Button type="button" icon="pi pi-trash" severity="danger" text rounded @click="removeContacto(index)" />
                            </div>
                        </div>
                    </div>
                </section>

                <Divider />

                <section class="grid gap-6 lg:grid-cols-2">
                    <div>
                        <h3 class="mb-3 text-base font-semibold">Integraciones</h3>
                        <div class="flex flex-col gap-4">
                            <div class="grid gap-3 md:grid-cols-[auto_1fr] md:items-center">
                                <ToggleSwitch v-model="form.integraciones.circulo_credito.habilitado" />
                                <InputText v-model="form.integraciones.circulo_credito.env_prefix" placeholder="CDC" fluid />
                            </div>
                            <div class="grid gap-3 md:grid-cols-[auto_1fr] md:items-center">
                                <ToggleSwitch v-model="form.integraciones.geocoding.habilitado" />
                                <InputText v-model="form.integraciones.geocoding.env_key" placeholder="GEOCODING_API_KEY" fluid />
                            </div>
                        </div>
                    </div>

                    <div>
                        <h3 class="mb-3 text-base font-semibold">Activación</h3>
                        <div class="mb-4 flex items-center gap-3">
                            <ToggleSwitch v-model="form.activa" />
                            <span class="font-medium">Financiera activa</span>
                        </div>
                        <ul class="grid gap-2 md:grid-cols-2">
                            <li v-for="item in activeRequirements" :key="item.label" class="flex items-center gap-2 text-sm">
                                <span :class="item.complete ? 'pi pi-check-circle text-green-600' : 'pi pi-circle text-surface-400'"></span>
                                <span>{{ item.label }}</span>
                            </li>
                        </ul>
                    </div>
                </section>

                <div class="sticky bottom-0 z-10 flex justify-end gap-2 border-t border-surface-200 bg-white py-4 dark:border-surface-700 dark:bg-surface-900">
                    <Button type="button" label="Descartar" icon="pi pi-refresh" severity="secondary" outlined @click="router.reload()" />
                    <Button type="submit" label="Guardar cambios" icon="pi pi-save" :loading="form.processing" />
                </div>
            </form>
        </template>
    </AppLayout>
</template>
