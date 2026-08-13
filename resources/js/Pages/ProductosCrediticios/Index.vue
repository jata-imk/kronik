<script setup>
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import axios from "axios";
import ProductVersionStatus from "@/Components/Products/ProductVersionStatus.vue";
import { computed, ref, watch } from "vue";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

const props = defineProps({ productos: Array, conceptosComision: Array });
const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const query = ref("");
const selectedId = ref(props.productos?.[0]?.id ?? null);
const editorVisible = ref(false);
const simulatorVisible = ref(false);
const catalogVisible = ref(false);
const editingVersion = ref(null);
const simulation = ref(null);
const simulating = ref(false);

const can = (permission) =>
    page.props.auth.is_super_admin ||
    page.props.auth.permissions?.[permission.replaceAll(" ", "-")] === true;
const products = computed(() =>
    (props.productos ?? []).filter((product) =>
        `${product.nombre} ${product.clave}`
            .toLowerCase()
            .includes(query.value.toLowerCase()),
    ),
);
const selected = computed(
    () =>
        props.productos?.find((product) => product.id === selectedId.value) ??
        products.value[0] ??
        null,
);
const latest = computed(() => selected.value?.versiones?.[0] ?? null);
const money = (value) =>
    new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
        maximumFractionDigits: 0,
    }).format(Number(value ?? 0));
const percent = (value) =>
    `${Number(value ?? 0).toLocaleString("es-MX", { maximumFractionDigits: 4 })}%`;
const stateMeta = (state) =>
    ({
        borrador: ["Borrador", "secondary"],
        programada: ["Programada", "warn"],
        activa: ["Activa", "success"],
        retirada: ["Retirada", "contrast"],
    })[state] ?? [state, "secondary"];
const periodicityLabel = {
    semanal: "Semanal",
    quincenal: "Quincenal",
    mensual: "Mensual",
};
const methodLabel = {
    cuota_nivelada: "Cuota nivelada",
    capital_fijo: "Capital fijo",
};

const emptyVersion = () => ({
    monto_minimo: 5000,
    monto_maximo: 100000,
    tasa_ordinaria_anual: 36,
    tasa_moratoria_anual: 72,
    dias_gracia_mora: 3,
    cat_aplica: true,
    cat_no_aplica_motivo: "",
    vigente_desde: null,
    periodicidades: [
        {
            periodicidad: "mensual",
            plazo_minimo: 3,
            plazo_maximo: 24,
            plazo_predeterminado: 12,
        },
    ],
    reglas: {
        metodos_amortizacion: ["cuota_nivelada"],
        permite_prepago_parcial: true,
        permite_liquidacion_anticipada: true,
        monto_minimo_prepago: null,
        aplicacion_prepago: "reducir_plazo",
    },
    comisiones: [],
});
const form = useForm({
    clave: "",
    nombre: "",
    descripcion: "",
    version: emptyVersion(),
});
const commissionForm = useForm({
    clave: "",
    nombre: "",
    descripcion: "",
    referencia_reco: "",
    es_oficial_reco: false,
    revisado: false,
    activo: true,
});
const saveCommissionConcept = () =>
    commissionForm.post(route("conceptos-comision.store"), {
        preserveScroll: true,
        onSuccess: () => {
            commissionForm.reset();
            toast.add({ severity: "success", summary: "Concepto agregado", life: 3000 });
        },
    });
const retireCommissionConcept = (concept) =>
    confirm.require({
        header: "Retirar concepto",
        message: `Se retirará ${concept.nombre} para configuraciones nuevas.`,
        acceptLabel: "Retirar",
        rejectLabel: "Cancelar",
        acceptClass: "p-button-danger",
        accept: () => router.delete(route("conceptos-comision.destroy", concept.id), { preserveScroll: true }),
    });

const openCreate = () => {
    editingVersion.value = null;
    form.defaults({
        clave: "",
        nombre: "",
        descripcion: "",
        version: emptyVersion(),
    });
    form.reset();
    form.clearErrors();
    editorVisible.value = true;
};
const openEdit = (version) => {
    editingVersion.value = version;
    form.defaults({
        clave: selected.value.clave,
        nombre: selected.value.nombre,
        descripcion: selected.value.descripcion ?? "",
        version: {
            monto_minimo: Number(version.monto_minimo),
            monto_maximo: Number(version.monto_maximo),
            tasa_ordinaria_anual: Number(version.tasa_ordinaria_anual),
            tasa_moratoria_anual: Number(version.tasa_moratoria_anual),
            dias_gracia_mora: version.dias_gracia_mora,
            cat_aplica: version.cat_aplica,
            cat_no_aplica_motivo: version.cat_no_aplica_motivo ?? "",
            vigente_desde: version.vigente_desde,
            periodicidades: version.periodicidades.map((item) => ({ ...item })),
            reglas: {
                ...version.reglas,
                metodos_amortizacion: [...version.reglas.metodos_amortizacion],
            },
            comisiones: version.comisiones.map((item) => ({
                concepto_comision_id: item.concepto_comision_id,
                tipo_importe: item.tipo_importe,
                importe: Number(item.importe),
                base_calculo: item.base_calculo,
                momento_cobro: item.momento_cobro,
                obligatoria: item.obligatoria,
                incluye_cat: item.incluye_cat,
            })),
        },
    });
    form.reset();
    form.clearErrors();
    editorVisible.value = true;
};
const submit = () => {
    const editing = Boolean(editingVersion.value);
    const url = editing
        ? route("productos-crediticios.update", [
              selected.value.id,
              editingVersion.value.id,
          ])
        : route("productos-crediticios.store");
    form.submit(editing ? "put" : "post", url, {
        preserveScroll: true,
        onSuccess: () => {
            editorVisible.value = false;
            toast.add({
                severity: "success",
                summary: editing ? "Borrador actualizado" : "Producto creado",
                life: 3000,
            });
        },
        onError: () =>
            toast.add({
                severity: "error",
                summary: "Revisa la configuración",
                detail: "Hay campos que necesitan atención.",
                life: 5000,
            }),
    });
};
const addPeriodicity = () => {
    const available = ["semanal", "quincenal", "mensual"].find(
        (value) =>
            !form.version.periodicidades.some(
                (item) => item.periodicidad === value,
            ),
    );
    if (available)
        form.version.periodicidades.push({
            periodicidad: available,
            plazo_minimo: 1,
            plazo_maximo: 24,
            plazo_predeterminado: 12,
        });
};
const addCommission = () =>
    form.version.comisiones.push({
        concepto_comision_id: null,
        tipo_importe: "fijo",
        importe: 0,
        base_calculo: "no_aplica",
        momento_cobro: "firma",
        obligatoria: true,
        incluye_cat: true,
    });

const versionCopy = (version) =>
    router.post(
        route("productos-crediticios.versionar", [
            selected.value.id,
            version.id,
        ]),
        {},
        {
            preserveScroll: true,
            onSuccess: () =>
                toast.add({
                    severity: "success",
                    summary: "Nueva versión creada",
                    life: 3000,
                }),
        },
    );
const activate = (version) => {
    const date = version.vigente_desde ?? new Date().toISOString().slice(0, 10);
    confirm.require({
        header: "Activar versión",
        message: `La versión ${version.numero} quedará inmutable desde ${date}.`,
        icon: "pi pi-lock",
        acceptLabel: "Activar",
        rejectLabel: "Cancelar",
        accept: () =>
            router.post(
                route("productos-crediticios.activar", version.id),
                { vigente_desde: date },
                { preserveScroll: true },
            ),
    });
};
const retire = (version) =>
    confirm.require({
        header: "Retirar versión",
        message:
            "Se impedirá su uso en nuevas operaciones, pero el historial se conservará.",
        icon: "pi pi-exclamation-triangle",
        acceptLabel: "Retirar",
        rejectLabel: "Cancelar",
        acceptClass: "p-button-danger",
        accept: () =>
            router.post(
                route("productos-crediticios.retirar", version.id),
                {},
                { preserveScroll: true },
            ),
    });

const simForm = ref({
    monto: 15000,
    periodicidad: "mensual",
    plazo: 12,
    metodo: "cuota_nivelada",
    fecha: new Date().toISOString().slice(0, 10),
});
const simulatorVersion = ref(null);
const simulatorPeriodOptions = computed(() =>
    (simulatorVersion.value?.periodicidades ?? []).map((item) => ({
        label: periodicityLabel[item.periodicidad],
        value: item.periodicidad,
    })),
);
const simulatorMethodOptions = computed(() =>
    (simulatorVersion.value?.reglas?.metodos_amortizacion ?? []).map((value) => ({
        label: methodLabel[value],
        value,
    })),
);
const openSimulator = (version) => {
    simulatorVersion.value = version;
    const period = version.periodicidades[0];
    simForm.value = {
        monto: Number(version.monto_minimo),
        periodicidad: period?.periodicidad ?? "mensual",
        plazo: period?.plazo_predeterminado ?? 12,
        metodo: version.reglas?.metodos_amortizacion?.[0] ?? "cuota_nivelada",
        fecha: new Date().toISOString().slice(0, 10),
    };
    simulation.value = null;
    simulatorVisible.value = true;
};
const simulate = async () => {
    simulating.value = true;
    try {
        simulation.value = (
            await axios.post(
                route(
                    "productos-crediticios.simular",
                    simulatorVersion.value.id,
                ),
                simForm.value,
            )
        ).data;
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "No se pudo simular",
            detail:
                Object.values(error.response?.data?.errors ?? {})[0]?.[0] ??
                "Revise los datos.",
            life: 5000,
        });
    } finally {
        simulating.value = false;
    }
};

watch(
    () => props.productos,
    (value) => {
        if (!value?.some((product) => product.id === selectedId.value))
            selectedId.value = value?.[0]?.id ?? null;
    },
);
</script>

<template>
    <AppLayout title="Productos crediticios">
        <template #card-header>
            <div class="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
                <div><p class="text-sm font-semibold uppercase tracking-widest text-primary">Configuración comercial</p><h1 class="mt-1 text-2xl font-bold">Productos crediticios</h1><p class="mt-1 text-sm text-surface-500">Crédito simple V1 · parámetros versionados y simulación transparente</p></div>
                <div class="flex gap-2"><Button v-if="can('manage commissions productos-crediticios')" label="Catálogo de comisiones" icon="pi pi-list" severity="secondary" @click="catalogVisible = true" /><Button v-if="can('create productos-crediticios')" label="Nuevo producto" icon="pi pi-plus" @click="openCreate" /></div>
            </div>
        </template>
        <template #card-content>
            <ConfirmDialog />
            <div class="grid min-h-[36rem] gap-5 lg:grid-cols-[19rem_minmax(0,1fr)]">
                <aside class="rounded-2xl border border-surface-200 bg-surface-50/70 p-3 dark:border-surface-700 dark:bg-surface-900/50">
                    <IconField class="mb-3"><InputIcon class="pi pi-search" /><InputText v-model="query" aria-label="Buscar productos" placeholder="Buscar producto" fluid /></IconField>
                    <div v-if="products.length" class="space-y-2">
                        <button v-for="product in products" :key="product.id" type="button" class="w-full rounded-xl border p-3 text-left transition" :class="selected?.id === product.id ? 'border-primary bg-primary-50 shadow-sm dark:bg-primary-950/30' : 'border-transparent bg-surface-0 hover:border-surface-300 dark:bg-surface-800'" @click="selectedId = product.id">
                            <div class="flex items-start justify-between gap-2"><div class="min-w-0"><p class="truncate font-semibold">{{ product.nombre }}</p><p class="truncate text-xs text-surface-500">{{ product.clave }}</p></div><ProductVersionStatus :state="product.versiones[0]?.estado ?? 'borrador'" /></div>
                            <div class="mt-3 flex items-center gap-2 text-xs text-surface-500"><i class="pi pi-clone" /><span>{{ product.versiones.length }} {{ product.versiones.length === 1 ? 'versión' : 'versiones' }}</span><span>·</span><span>{{ product.versiones[0] ? money(product.versiones[0].monto_maximo) : 'Sin configurar' }}</span></div>
                        </button>
                    </div>
                    <div v-else class="px-4 py-12 text-center"><i class="pi pi-wallet text-3xl text-surface-300" /><p class="mt-3 font-medium">Sin productos</p><p class="mt-1 text-sm text-surface-500">Crea el primer producto de crédito simple.</p></div>
                </aside>

                <main v-if="selected" class="min-w-0 space-y-5">
                    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-primary-700 via-primary-600 to-indigo-600 p-5 text-white shadow-lg shadow-primary-900/10">
                        <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold">{{ selected.clave }}</span><span class="rounded-full bg-white/15 px-2.5 py-1 text-xs">Crédito simple</span></div><h2 class="mt-3 truncate text-2xl font-bold">{{ selected.nombre }}</h2><p class="mt-1 max-w-2xl text-sm text-white/75">{{ selected.descripcion || 'Sin descripción comercial.' }}</p></div><Button v-if="latest && can('simulate productos-crediticios')" label="Simular" icon="pi pi-calculator" severity="secondary" @click="openSimulator(latest)" /></div>
                        <div v-if="latest" class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4"><div class="rounded-xl bg-white/10 p-3"><p class="text-xs text-white/70">Montos</p><p class="mt-1 font-semibold">{{ money(latest.monto_minimo) }} – {{ money(latest.monto_maximo) }}</p></div><div class="rounded-xl bg-white/10 p-3"><p class="text-xs text-white/70">Tasa ordinaria anual</p><p class="mt-1 text-lg font-semibold">{{ percent(latest.tasa_ordinaria_anual) }}</p></div><div class="rounded-xl bg-white/10 p-3"><p class="text-xs text-white/70">Mora anual</p><p class="mt-1 text-lg font-semibold">{{ percent(latest.tasa_moratoria_anual) }}</p></div><div class="rounded-xl bg-white/10 p-3"><p class="text-xs text-white/70">Versión actual</p><p class="mt-1 text-lg font-semibold">v{{ latest.numero }} · {{ stateMeta(latest.estado)[0] }}</p></div></div>
                    </section>

                    <section class="rounded-2xl border border-surface-200 bg-surface-0 p-4 dark:border-surface-700 dark:bg-surface-900">
                        <div class="mb-4 flex flex-wrap items-center justify-between gap-3"><div><h3 class="font-semibold">Versiones y vigencia</h3><p class="text-sm text-surface-500">Las versiones activadas son históricas e inmutables.</p></div></div>
                        <DataTable :value="selected.versiones" striped-rows responsive-layout="scroll" :table-style="{ minWidth: '52rem' }">
                            <Column header="Versión" style="width: 16%"><template #body="{ data }"><div class="flex items-center gap-2"><Avatar :label="`v${data.numero}`" shape="circle" class="bg-primary-100 font-bold text-primary-700" /><ProductVersionStatus :state="data.estado" :used="data.usos_count > 0" /></div></template></Column>
                            <Column header="Condiciones" style="width: 27%"><template #body="{ data }"><p class="font-medium">{{ money(data.monto_minimo) }} – {{ money(data.monto_maximo) }}</p><p class="text-sm text-surface-500">{{ percent(data.tasa_ordinaria_anual) }} ordinaria · {{ data.dias_gracia_mora }} días de gracia</p></template></Column>
                            <Column header="Periodicidad" style="width: 22%"><template #body="{ data }"><div class="flex flex-wrap gap-1"><Chip v-for="item in data.periodicidades" :key="item.id" :label="`${periodicityLabel[item.periodicidad]} ${item.plazo_minimo}–${item.plazo_maximo}`" /></div></template></Column>
                            <Column header="Vigencia" style="width: 15%"><template #body="{ data }"><p class="text-sm">{{ data.vigente_desde || 'Sin programar' }}</p><p class="text-xs text-surface-500">{{ data.cat_aplica ? 'CAT aplicable' : 'CAT no aplicable' }}</p></template></Column>
                            <Column header="Acciones" style="width: 20%"><template #body="{ data }"><div class="flex flex-nowrap gap-1"><Button v-if="data.estado === 'borrador' && can('update productos-crediticios')" v-tooltip.top="'Editar borrador'" icon="pi pi-pencil" text rounded :aria-label="`Editar versión ${data.numero}`" @click="openEdit(data)" /><Button v-if="can('simulate productos-crediticios')" v-tooltip.top="'Simular'" icon="pi pi-calculator" text rounded :aria-label="`Simular versión ${data.numero}`" @click="openSimulator(data)" /><Button v-if="can('version productos-crediticios')" v-tooltip.top="'Crear nueva versión'" icon="pi pi-copy" text rounded :aria-label="`Duplicar versión ${data.numero}`" @click="versionCopy(data)" /><Button v-if="data.estado === 'borrador' && can('activate productos-crediticios')" v-tooltip.top="'Activar'" icon="pi pi-check-circle" text rounded severity="success" :aria-label="`Activar versión ${data.numero}`" @click="activate(data)" /><Button v-if="['activa', 'programada'].includes(data.estado) && can('retire productos-crediticios')" v-tooltip.top="'Retirar'" icon="pi pi-ban" text rounded severity="danger" :aria-label="`Retirar versión ${data.numero}`" @click="retire(data)" /></div></template></Column>
                        </DataTable>
                    </section>
                </main>
                <main v-else class="grid place-items-center rounded-2xl border border-dashed border-surface-300 p-12 text-center"><div><i class="pi pi-wallet text-5xl text-surface-300" /><h2 class="mt-4 text-xl font-semibold">Configura tu primer producto</h2><p class="mt-2 text-surface-500">Define montos, plazos, tasas y reglas en un borrador seguro.</p><Button v-if="can('create productos-crediticios')" class="mt-5" label="Crear producto" icon="pi pi-plus" @click="openCreate" /></div></main>
            </div>

            <Dialog v-model:visible="editorVisible" :header="editingVersion ? `Editar versión ${editingVersion.numero}` : 'Nuevo producto crediticio'" modal maximizable :style="{ width: 'min(980px, 96vw)' }">
                <form class="space-y-6" @submit.prevent="submit">
                    <Message severity="info" :closable="false"><strong>Configuración segura.</strong> El borrador puede editarse; al activarlo se congela y cualquier cambio posterior requiere una nueva versión.</Message>
                    <Tabs value="general"><TabList><Tab value="general">Información</Tab><Tab value="condiciones">Montos y tasas</Tab><Tab value="reglas">Reglas</Tab><Tab value="comisiones">Comisiones</Tab></TabList><TabPanels>
                        <TabPanel value="general"><div class="grid gap-4 pt-3 md:grid-cols-2"><div><label for="product-key" class="mb-1 block text-sm font-medium">Clave *</label><InputText id="product-key" v-model="form.clave" :invalid="!!form.errors.clave" fluid /><small class="text-red-500">{{ form.errors.clave }}</small></div><div><label for="product-name" class="mb-1 block text-sm font-medium">Nombre *</label><InputText id="product-name" v-model="form.nombre" :invalid="!!form.errors.nombre" fluid /><small class="text-red-500">{{ form.errors.nombre }}</small></div><div class="md:col-span-2"><label for="product-description" class="mb-1 block text-sm font-medium">Descripción comercial</label><Textarea id="product-description" v-model="form.descripcion" rows="3" fluid /></div><div class="flex items-center gap-3"><Checkbox v-model="form.version.cat_aplica" input-id="cat-applies" binary /><label for="cat-applies">Mostrar CAT informativo</label></div><div v-if="!form.version.cat_aplica"><label class="mb-1 block text-sm font-medium">Motivo de no aplicación *</label><InputText v-model="form.version.cat_no_aplica_motivo" fluid /></div></div></TabPanel>
                        <TabPanel value="condiciones"><div class="grid gap-4 pt-3 md:grid-cols-2 lg:grid-cols-4"><div><label class="mb-1 block text-sm font-medium">Monto mínimo *</label><InputNumber v-model="form.version.monto_minimo" mode="currency" currency="MXN" locale="es-MX" fluid /></div><div><label class="mb-1 block text-sm font-medium">Monto máximo *</label><InputNumber v-model="form.version.monto_maximo" mode="currency" currency="MXN" locale="es-MX" fluid /></div><div><label class="mb-1 block text-sm font-medium">Tasa ordinaria anual *</label><InputNumber v-model="form.version.tasa_ordinaria_anual" suffix=" %" :min-fraction-digits="2" fluid /></div><div><label class="mb-1 block text-sm font-medium">Tasa moratoria anual *</label><InputNumber v-model="form.version.tasa_moratoria_anual" suffix=" %" :min-fraction-digits="2" fluid /></div><div><label class="mb-1 block text-sm font-medium">Días de gracia de mora</label><InputNumber v-model="form.version.dias_gracia_mora" :min="0" fluid /></div></div><Divider /><div class="mb-3 flex items-center justify-between"><div><h4 class="font-semibold">Plazos por periodicidad</h4><p class="text-sm text-surface-500">El plazo se expresa en número de pagos.</p></div><Button label="Agregar" icon="pi pi-plus" text :disabled="form.version.periodicidades.length >= 3" @click="addPeriodicity" /></div><div v-for="(item, index) in form.version.periodicidades" :key="index" class="mb-3 grid items-end gap-3 rounded-xl border border-surface-200 p-3 md:grid-cols-[1.2fr_1fr_1fr_1fr_auto]"><div><label class="mb-1 block text-xs font-medium">Periodicidad</label><Select v-model="item.periodicidad" :options="Object.keys(periodicityLabel)" :option-label="(value) => periodicityLabel[value]" fluid /></div><div><label class="mb-1 block text-xs font-medium">Mínimo</label><InputNumber v-model="item.plazo_minimo" :min="1" fluid /></div><div><label class="mb-1 block text-xs font-medium">Máximo</label><InputNumber v-model="item.plazo_maximo" :min="1" fluid /></div><div><label class="mb-1 block text-xs font-medium">Predeterminado</label><InputNumber v-model="item.plazo_predeterminado" :min="1" fluid /></div><Button icon="pi pi-trash" text severity="danger" aria-label="Quitar periodicidad" :disabled="form.version.periodicidades.length === 1" @click="form.version.periodicidades.splice(index, 1)" /></div></TabPanel>
                        <TabPanel value="reglas"><div class="grid gap-5 pt-3 md:grid-cols-2"><div><label class="mb-2 block text-sm font-medium">Métodos de amortización *</label><div class="flex flex-col gap-3 rounded-xl border border-surface-200 p-4"><div v-for="method in Object.keys(methodLabel)" :key="method" class="flex items-center gap-2"><Checkbox v-model="form.version.reglas.metodos_amortizacion" :input-id="method" :value="method" /><label :for="method">{{ methodLabel[method] }}</label></div></div></div><div class="rounded-xl border border-surface-200 p-4"><p class="font-medium">Convenciones V1</p><ul class="mt-3 space-y-2 text-sm text-surface-600"><li><i class="pi pi-check mr-2 text-green-500" />Interés por días reales / 360</li><li><i class="pi pi-check mr-2 text-green-500" />Mora sobre capital vencido</li><li><i class="pi pi-check mr-2 text-green-500" />Redondeo half-up a centavos</li><li><i class="pi pi-info-circle mr-2 text-blue-500" />Sin ajuste por días inhábiles ni IVA en V1</li></ul></div><div class="space-y-4"><div class="flex items-center gap-3"><Checkbox v-model="form.version.reglas.permite_prepago_parcial" input-id="partial-prepay" binary /><label for="partial-prepay">Permitir prepago parcial</label></div><div class="flex items-center gap-3"><Checkbox v-model="form.version.reglas.permite_liquidacion_anticipada" input-id="full-prepay" binary /><label for="full-prepay">Permitir liquidación anticipada</label></div></div><div><label class="mb-1 block text-sm font-medium">Aplicar prepago para</label><Select v-model="form.version.reglas.aplicacion_prepago" :options="[{label:'Reducir plazo',value:'reducir_plazo'},{label:'Reducir pago',value:'reducir_pago'}]" option-label="label" option-value="value" fluid /><label class="mb-1 mt-3 block text-sm font-medium">Monto mínimo de prepago</label><InputNumber v-model="form.version.reglas.monto_minimo_prepago" mode="currency" currency="MXN" locale="es-MX" fluid /></div></div></TabPanel>
                        <TabPanel value="comisiones"><div class="mb-3 flex items-center justify-between pt-3"><div><h4 class="font-semibold">Comisiones configurables</h4><p class="text-sm text-surface-500">Solo pueden elegirse conceptos vigentes del catálogo.</p></div><Button label="Agregar comisión" icon="pi pi-plus" text @click="addCommission" /></div><div v-if="!form.version.comisiones.length" class="rounded-xl border border-dashed border-surface-300 p-8 text-center text-sm text-surface-500">Este producto no tiene comisiones.</div><div v-for="(item, index) in form.version.comisiones" :key="index" class="mb-3 grid items-end gap-3 rounded-xl border border-surface-200 p-3 md:grid-cols-[1.5fr_1fr_1fr_1.2fr_auto]"><div><label class="mb-1 block text-xs font-medium">Concepto</label><Select v-model="item.concepto_comision_id" :options="conceptosComision" option-label="nombre" option-value="id" fluid /></div><div><label class="mb-1 block text-xs font-medium">Tipo</label><Select v-model="item.tipo_importe" :options="[{label:'Importe fijo',value:'fijo'},{label:'Porcentaje',value:'porcentaje'}]" option-label="label" option-value="value" fluid @change="item.base_calculo = item.tipo_importe === 'porcentaje' ? 'monto_credito' : 'no_aplica'" /></div><div><label class="mb-1 block text-xs font-medium">Importe</label><InputNumber v-model="item.importe" :suffix="item.tipo_importe === 'porcentaje' ? ' %' : ''" :mode="item.tipo_importe === 'fijo' ? 'currency' : 'decimal'" currency="MXN" locale="es-MX" fluid /></div><div><label class="mb-1 block text-xs font-medium">Momento</label><Select v-model="item.momento_cobro" :options="[{label:'Firma',value:'firma'},{label:'Descuento al desembolso',value:'desembolso_descuento'},{label:'Cada pago',value:'cada_pago'},{label:'Evento',value:'evento'},{label:'Liquidación',value:'liquidacion'}]" option-label="label" option-value="value" fluid /></div><Button icon="pi pi-trash" text severity="danger" aria-label="Quitar comisión" @click="form.version.comisiones.splice(index, 1)" /></div></TabPanel>
                    </TabPanels></Tabs>
                    <Message v-if="form.errors.version" severity="error" :closable="false">{{ form.errors.version }}</Message>
                    <div class="flex justify-end gap-2 border-t border-surface-200 pt-4"><Button label="Cancelar" severity="secondary" @click="editorVisible = false" /><Button type="submit" label="Guardar borrador" icon="pi pi-save" :loading="form.processing" /></div>
                </form>
            </Dialog>

            <Dialog v-model:visible="catalogVisible" header="Catálogo de comisiones" modal :style="{ width: 'min(760px, 96vw)' }">
                <div class="grid gap-5 md:grid-cols-[1fr_1.15fr]"><form class="space-y-3 rounded-xl border border-surface-200 p-4" @submit.prevent="saveCommissionConcept"><h4 class="font-semibold">Nuevo concepto</h4><div><label class="mb-1 block text-sm font-medium">Clave *</label><InputText v-model="commissionForm.clave" fluid /></div><div><label class="mb-1 block text-sm font-medium">Nombre *</label><InputText v-model="commissionForm.nombre" fluid /></div><div><label class="mb-1 block text-sm font-medium">Descripción</label><Textarea v-model="commissionForm.descripcion" rows="3" fluid /></div><div class="flex items-center gap-2"><Checkbox v-model="commissionForm.es_oficial_reco" input-id="reco-official" binary /><label for="reco-official">Concepto tomado de RECO</label></div><Button type="submit" label="Agregar concepto" icon="pi pi-plus" :loading="commissionForm.processing" fluid /></form><div><h4 class="mb-3 font-semibold">Conceptos disponibles</h4><div class="max-h-[28rem] space-y-2 overflow-auto"><div v-for="concept in conceptosComision" :key="concept.id" class="flex items-center justify-between gap-3 rounded-xl bg-surface-50 p-3 dark:bg-surface-800"><div class="min-w-0"><p class="truncate font-medium">{{ concept.nombre }}</p><p class="truncate text-xs text-surface-500">{{ concept.clave }}<span v-if="concept.es_oficial_reco"> · RECO revisado</span></p></div><Button icon="pi pi-ban" text rounded severity="danger" :aria-label="`Retirar ${concept.nombre}`" @click="retireCommissionConcept(concept)" /></div></div></div></div>
            </Dialog>

            <Drawer v-model:visible="simulatorVisible" header="Simulador de crédito simple" position="right" class="!w-full md:!w-[46rem]">
                <div class="space-y-5"><div class="rounded-xl bg-primary-50 p-4 dark:bg-primary-950/30"><p class="text-sm text-surface-500">Simulando</p><p class="font-semibold">{{ selected?.nombre }} · versión {{ simulatorVersion?.numero }}</p></div><div class="grid gap-3 sm:grid-cols-2"><div><label class="mb-1 block text-sm font-medium">Monto</label><InputNumber v-model="simForm.monto" mode="currency" currency="MXN" locale="es-MX" fluid /></div><div><label class="mb-1 block text-sm font-medium">Fecha de disposición</label><DatePicker v-model="simForm.fecha" date-format="yy-mm-dd" update-model-type="string" fluid /></div><div><label class="mb-1 block text-sm font-medium">Periodicidad</label><Select v-model="simForm.periodicidad" :options="simulatorPeriodOptions" option-label="label" option-value="value" fluid /></div><div><label class="mb-1 block text-sm font-medium">Número de pagos</label><InputNumber v-model="simForm.plazo" :min="1" fluid /></div><div><label class="mb-1 block text-sm font-medium">Amortización</label><Select v-model="simForm.metodo" :options="simulatorMethodOptions" option-label="label" option-value="value" fluid /></div><div class="flex items-end"><Button label="Calcular escenario" icon="pi pi-calculator" :loading="simulating" fluid @click="simulate" /></div></div>
                    <div v-if="simulation" class="space-y-4"><div class="grid grid-cols-2 gap-3 sm:grid-cols-4"><div class="rounded-xl border border-surface-200 p-3"><p class="text-xs text-surface-500">Pago total</p><p class="mt-1 font-bold">{{ money(simulation.total_pagar) }}</p></div><div class="rounded-xl border border-surface-200 p-3"><p class="text-xs text-surface-500">Intereses</p><p class="mt-1 font-bold">{{ money(simulation.total_intereses) }}</p></div><div class="rounded-xl border border-surface-200 p-3"><p class="text-xs text-surface-500">Comisión inicial</p><p class="mt-1 font-bold">{{ money(simulation.comisiones_iniciales) }}</p></div><div class="rounded-xl bg-primary p-3 text-primary-contrast"><p class="text-xs opacity-75">CAT informativo</p><p class="mt-1 text-xl font-bold">{{ simulation.cat ? `${simulation.cat}%` : 'N/A' }}</p></div></div><Message severity="secondary" :closable="false">{{ simulation.cat_leyenda }}</Message><DataTable :value="simulation.tabla" size="small" paginator :rows="8" scrollable><Column field="numero" header="#" /><Column field="fecha" header="Fecha" /><Column field="capital" header="Capital"><template #body="{data}">{{ money(data.capital) }}</template></Column><Column field="interes" header="Interés"><template #body="{data}">{{ money(data.interes) }}</template></Column><Column field="pago_total" header="Pago"><template #body="{data}"><strong>{{ money(data.pago_total) }}</strong></template></Column><Column field="saldo" header="Saldo"><template #body="{data}">{{ money(data.saldo) }}</template></Column></DataTable></div>
                </div>
            </Drawer>
        </template>
    </AppLayout>
</template>
