<script setup>
import { useForm } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";

const props = defineProps({
    configuracion: Object,
});

const toast = useToast();

const form = useForm({
    razon_social: props.configuracion?.razon_social ?? "",
    nombre_comercial: props.configuracion?.nombre_comercial ?? "",
    rfc: props.configuracion?.rfc ?? "",
    regimen_fiscal: props.configuracion?.regimen_fiscal ?? "",
    domicilio_fiscal: {
        calle: props.configuracion?.domicilio_fiscal?.calle ?? "",
        numero_exterior: props.configuracion?.domicilio_fiscal?.numero_exterior ?? "",
        numero_interior: props.configuracion?.domicilio_fiscal?.numero_interior ?? "",
        colonia: props.configuracion?.domicilio_fiscal?.colonia ?? "",
        municipio: props.configuracion?.domicilio_fiscal?.municipio ?? "",
        estado: props.configuracion?.domicilio_fiscal?.estado ?? "",
        codigo_postal: props.configuracion?.domicilio_fiscal?.codigo_postal ?? "",
    },
    telefono: props.configuracion?.telefono ?? "",
    email: props.configuracion?.email ?? "",
    sitio_web: props.configuracion?.sitio_web ?? "",
    moneda: props.configuracion?.moneda ?? "MXN",
    zona_horaria: props.configuracion?.zona_horaria ?? "America/Mexico_City",
    pais_base: props.configuracion?.pais_base ?? "MX",
    logotipo_path: props.configuracion?.logotipo_path ?? "",
    parametros_operativos: {
        dias_gracia_default: props.configuracion?.parametros_operativos?.dias_gracia_default ?? 0,
        hora_corte_operativo: props.configuracion?.parametros_operativos?.hora_corte_operativo ?? "18:00",
    },
    integraciones: {
        circulo_credito_host: props.configuracion?.integraciones?.circulo_credito_host ?? "",
        circulo_credito_sandbox: props.configuracion?.integraciones?.circulo_credito_sandbox ?? true,
        circulo_credito_api_key: "",
    },
    estatus: props.configuracion?.estatus ?? "borrador",
});

const estatusOptions = [
    { label: "Borrador", value: "borrador" },
    { label: "Activa", value: "activa" },
    { label: "Suspendida", value: "suspendida" },
];

const submit = () => {
    form.put(route("admin.configuracion-empresa.update"), {
        preserveScroll: true,
        onSuccess: () => {
            form.integraciones.circulo_credito_api_key = "";
            toast.add({ severity: "success", summary: "Configuracion actualizada", life: 3000 });
        },
    });
};
</script>

<template>
    <AppLayout title="Configuracion de empresa">
        <template #card-header>
            <div class="flex items-center p-4">
                <Button icon="pi pi-arrow-left" as="a" :href="route('admin.dashboard')" />
                <h2 class="text-2xl font-bold ml-4">Configuracion de empresa</h2>
            </div>
        </template>

        <template #card-content>
            <form class="p-4 flex flex-col gap-8" @submit.prevent="submit">
                <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold mb-2">Datos legales</h3>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Razon social</label>
                        <InputText v-model="form.razon_social" fluid />
                        <small v-if="form.errors.razon_social" class="text-red-500">{{ form.errors.razon_social }}</small>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Nombre comercial</label>
                        <InputText v-model="form.nombre_comercial" fluid />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">RFC</label>
                        <InputText v-model="form.rfc" fluid maxlength="13" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Regimen fiscal</label>
                        <InputText v-model="form.regimen_fiscal" fluid />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Estatus</label>
                        <Select v-model="form.estatus" :options="estatusOptions" option-label="label" option-value="value" fluid />
                    </div>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-3">
                        <h3 class="text-lg font-semibold mb-2">Domicilio fiscal</h3>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium mb-1">Calle</label>
                        <InputText v-model="form.domicilio_fiscal.calle" fluid />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Numero exterior</label>
                        <InputText v-model="form.domicilio_fiscal.numero_exterior" fluid />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Numero interior</label>
                        <InputText v-model="form.domicilio_fiscal.numero_interior" fluid />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Colonia</label>
                        <InputText v-model="form.domicilio_fiscal.colonia" fluid />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Municipio</label>
                        <InputText v-model="form.domicilio_fiscal.municipio" fluid />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Estado</label>
                        <InputText v-model="form.domicilio_fiscal.estado" fluid />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Codigo postal</label>
                        <InputText v-model="form.domicilio_fiscal.codigo_postal" fluid />
                    </div>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="md:col-span-3">
                        <h3 class="text-lg font-semibold mb-2">Operacion</h3>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Telefono</label>
                        <InputText v-model="form.telefono" fluid />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Email</label>
                        <InputText v-model="form.email" fluid />
                        <small v-if="form.errors.email" class="text-red-500">{{ form.errors.email }}</small>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Sitio web</label>
                        <InputText v-model="form.sitio_web" fluid placeholder="https://..." />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Moneda</label>
                        <InputText v-model="form.moneda" fluid maxlength="3" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Zona horaria</label>
                        <InputText v-model="form.zona_horaria" fluid />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Pais base</label>
                        <InputText v-model="form.pais_base" fluid maxlength="2" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Dias de gracia default</label>
                        <InputNumber v-model="form.parametros_operativos.dias_gracia_default" fluid :min="0" :max="365" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Hora de corte operativo</label>
                        <InputText v-model="form.parametros_operativos.hora_corte_operativo" fluid placeholder="18:00" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Logotipo path</label>
                        <InputText v-model="form.logotipo_path" fluid placeholder="/images/logo.png" />
                    </div>
                </section>

                <section class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <h3 class="text-lg font-semibold mb-2">Integracion Círculo de Crédito</h3>
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">Host</label>
                        <InputText v-model="form.integraciones.circulo_credito_host" fluid placeholder="https://services.circulodecredito.com.mx" />
                    </div>
                    <div>
                        <label class="block text-sm font-medium mb-1">API key</label>
                        <Password
                            v-model="form.integraciones.circulo_credito_api_key"
                            fluid
                            :feedback="false"
                            :placeholder="configuracion.integraciones?.circulo_credito_api_key_configurada ? 'Ya configurada; llenar solo para reemplazar' : 'Capturar API key'" />
                    </div>
                    <div class="flex items-center gap-3">
                        <Checkbox v-model="form.integraciones.circulo_credito_sandbox" input-id="cdc-sandbox" binary />
                        <label for="cdc-sandbox">Usar ambiente sandbox</label>
                    </div>
                </section>

                <div class="flex justify-end border-t border-surface-200 dark:border-surface-700 pt-4">
                    <Button label="Guardar configuracion" icon="pi pi-save" type="submit" :loading="form.processing" />
                </div>
            </form>
        </template>
    </AppLayout>
</template>
