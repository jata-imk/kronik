<script setup>
import FinancialFieldHelp from "@/Components/Products/FinancialFieldHelp.vue";
import ProductVersionStatus from "@/Components/Products/ProductVersionStatus.vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import axios from "axios";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { computed, nextTick, ref, watch } from "vue";
import { dateInTimeZone } from "./activationDate";
import {
    commissionCatReason,
    commissionIncludesCat,
    isConditionalCommission,
    isSelectableOptionalCommission,
} from "./commissionRules";
import {
    countProductTabErrors,
    formatMoneyWithCents,
    tabForProductError,
} from "./productValidation";

const props = defineProps({
    productos: { type: Array, default: () => [] },
    conceptosComision: { type: Array, default: () => [] },
    simuladorDefaults: { type: Object, default: () => ({}) },
    activacionDefaults: { type: Object, default: () => ({}) },
});
const page = usePage();
const toast = useToast();
const confirm = useConfirm();
const query = ref("");
const selectedId = ref(props.productos[0]?.id ?? null);
const editorVisible = ref(false);
const simulatorVisible = ref(false);
const catalogVisible = ref(false);
const helpGuideVisible = ref(false);
const editingVersion = ref(null);
const activeEditorTab = ref("general");
const simulation = ref(null);
const simulating = ref(false);
const activationVisible = ref(false);
const activatingVersion = ref(null);

const can = (permission) =>
    page.props.auth.is_super_admin ||
    page.props.auth.permissions?.[permission.replaceAll(" ", "-")] === true;
const products = computed(() =>
    props.productos.filter((product) =>
        `${product.nombre} ${product.clave}`
            .toLowerCase()
            .includes(query.value.toLowerCase()),
    ),
);
const selected = computed(
    () =>
        props.productos.find((product) => product.id === selectedId.value) ??
        products.value[0] ??
        null,
);
const latest = computed(() => selected.value?.versiones?.[0] ?? null);
const money = formatMoneyWithCents;
const moneyCompact = (value) =>
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
const modalityOptions = [
    { label: "Pago separado al inicio", value: "pago_separado" },
    { label: "Retener del desembolso", value: "descuento_desembolso" },
    { label: "Financiar en el saldo", value: "financiada" },
];
const modalityLabel = (value) =>
    modalityOptions.find((option) => option.value === value)?.label;
const momentOptions = [
    { label: "Inicio del crédito", value: "inicio" },
    { label: "Cada pago", value: "cada_pago" },
    { label: "Evento", value: "evento" },
    { label: "Liquidación", value: "liquidacion" },
];
const help = {
    key: [
        "Clave",
        "Identificador estable y único del producto. Se utiliza en búsquedas, contratos e integraciones.",
        "CS-FLEX-01",
    ],
    amount: [
        "Rango de monto",
        "Límites permitidos para el saldo total financiado. Las comisiones financiadas también consumen este límite.",
        "$5,000 a $100,000",
    ],
    rate: [
        "Tasa anual",
        "Porcentaje anual usado para calcular interés por días reales/360. No es una tasa mensual.",
        "36% × 31/360 = 3.10% para un periodo de 31 días",
    ],
    grace: [
        "Días de gracia",
        "Días posteriores al vencimiento antes de aplicar las reglas de mora configuradas.",
        "3 días",
    ],
    term: [
        "Plazo",
        "Número de pagos permitidos para una periodicidad, no número de meses salvo que sea mensual.",
        "12 pagos mensuales",
    ],
    method: [
        "Amortización",
        "Cuota nivelada mantiene el pago base; capital fijo mantiene la amortización de principal.",
        "La comisión de cada pago se suma después",
    ],
    prepay: [
        "Prepago",
        "Define si el cliente puede abonar capital antes del vencimiento y si reduce plazo o pago.",
        "Reducir plazo conserva la cuota",
    ],
    commission: [
        "Comisión",
        "Cargo distinto al interés. Puede pagarse al inicio, retenerse, financiarse o cobrarse en pagos.",
        "1% de $5,000 = $50",
    ],
    cat: [
        "CAT informativo",
        "Compara el costo del crédito a partir de disposiciones y pagos obligatorios. Se muestra sin IVA.",
        "Incluye apertura y administración obligatorias",
        "Debe revisarse legalmente antes de uso contractual o publicitario.",
    ],
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

const error = (key) => form.errors[key];
const commissionError = (key) => commissionForm.errors[key];
const tabErrorCount = (tab) => countProductTabErrors(form.errors, tab);
const focusFirstError = async () => {
    await nextTick();
    document.querySelector("#product-editor [aria-invalid='true']")?.focus();
};

const saveCommissionConcept = () =>
    commissionForm.post(route("conceptos-comision.store"), {
        preserveScroll: true,
        errorBag: "conceptoComision",
        only: ["conceptosComision"],
        onSuccess: () => {
            commissionForm.reset();
            toast.add({
                severity: "success",
                summary: "Concepto agregado",
                detail: "El concepto ya está disponible para productos nuevos.",
                life: 3500,
            });
        },
        onError: (errors) =>
            toast.add({
                severity: "error",
                summary: "No se pudo agregar",
                detail:
                    Object.values(errors)[0] ?? "Revisa los campos marcados.",
                life: 5000,
            }),
    });
const retireCommissionConcept = (concept) =>
    confirm.require({
        header: "Retirar concepto",
        message: `Se retirará ${concept.nombre} para configuraciones nuevas.`,
        acceptLabel: "Retirar",
        rejectLabel: "Cancelar",
        acceptClass: "p-button-danger",
        accept: () =>
            router.delete(route("conceptos-comision.destroy", concept.id), {
                preserveScroll: true,
                only: ["conceptosComision"],
                onSuccess: () =>
                    toast.add({
                        severity: "success",
                        summary: "Concepto retirado",
                        detail: "Las versiones históricas lo conservan.",
                        life: 3500,
                    }),
                onError: (errors) =>
                    toast.add({
                        severity: "error",
                        summary: "No se pudo retirar",
                        detail: Object.values(errors)[0] ?? "Intenta de nuevo.",
                        life: 5000,
                    }),
            }),
    });

const normalizeCommission = (item) => {
    const legacyMode =
        item.momento_cobro === "desembolso_descuento"
            ? "descuento_desembolso"
            : "pago_separado";
    const initial = ["firma", "desembolso_descuento", "inicio"].includes(
        item.momento_cobro,
    );
    return {
        concepto_comision_id: item.concepto_comision_id,
        tipo_importe: item.tipo_importe,
        importe: Number(item.importe),
        base_calculo: item.base_calculo,
        momento_cobro: initial ? "inicio" : item.momento_cobro,
        modalidad_cobro: initial ? (item.modalidad_cobro ?? legacyMode) : null,
        obligatoria: item.obligatoria,
        incluye_cat: commissionIncludesCat(item),
    };
};
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
    activeEditorTab.value = "general";
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
            comisiones: version.comisiones.map(normalizeCommission),
        },
    });
    form.reset();
    form.clearErrors();
    activeEditorTab.value = "general";
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
        errorBag: "productoCrediticio",
        only: ["productos"],
        onSuccess: () => {
            editorVisible.value = false;
            toast.add({
                severity: "success",
                summary: editing ? "Borrador actualizado" : "Producto creado",
                life: 3000,
            });
        },
        onError: async (errors) => {
            activeEditorTab.value = tabForProductError(
                Object.keys(errors)[0] ?? "clave",
            );
            await focusFirstError();
            toast.add({
                severity: "error",
                summary: "Revisa la configuración",
                detail: `${Object.keys(errors).length} campo(s) necesitan atención.`,
                life: 5000,
            });
        },
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
        momento_cobro: "inicio",
        modalidad_cobro: "pago_separado",
        obligatoria: true,
        incluye_cat: true,
    });
const changeCommissionType = (item) => {
    item.base_calculo =
        item.tipo_importe === "porcentaje" ? "monto_credito" : "no_aplica";
};
const changeCommissionMoment = (item) => {
    item.modalidad_cobro =
        item.momento_cobro === "inicio"
            ? (item.modalidad_cobro ?? "pago_separado")
            : null;
    updateCommissionCat(item);
};
const updateCommissionCat = (item) => {
    item.incluye_cat = commissionIncludesCat(item);
};
const catReason = commissionCatReason;
const unhandledCommissionErrors = (index) => {
    const visible = [
        "concepto_comision_id",
        "tipo_importe",
        "importe",
        "momento_cobro",
        "modalidad_cobro",
        "obligatoria",
    ];
    const prefix = `version.comisiones.${index}.`;

    return Object.entries(form.errors)
        .filter(
            ([key]) =>
                key.startsWith(prefix) &&
                !visible.includes(key.slice(prefix.length)),
        )
        .map(([, message]) => message);
};

const versionCopy = (version) =>
    router.post(
        route("productos-crediticios.versionar", [
            selected.value.id,
            version.id,
        ]),
        {},
        {
            preserveScroll: true,
            only: ["productos"],
            onSuccess: () =>
                toast.add({
                    severity: "success",
                    summary: "Nueva versión creada",
                    life: 3000,
                }),
        },
    );
const companyToday = () =>
    dateInTimeZone(new Date(), props.activacionDefaults.zona_horaria);
const activationForm = useForm({ vigente_desde: "" });
const activationIsScheduled = computed(
    () => activationForm.vigente_desde > companyToday(),
);
const activate = (version) => {
    activatingVersion.value = version;
    activationForm.defaults({ vigente_desde: companyToday() });
    activationForm.reset();
    activationForm.clearErrors();
    activationVisible.value = true;
};
const submitActivation = () => {
    activationForm.post(
        route("productos-crediticios.activar", activatingVersion.value.id),
        {
            preserveScroll: true,
            errorBag: "activarProductoVersion",
            only: ["productos"],
            onSuccess: () => {
                activationVisible.value = false;
                toast.add({
                    severity: "success",
                    summary: activationIsScheduled.value
                        ? "Activación programada"
                        : "Versión activada",
                    detail: activationIsScheduled.value
                        ? `Entrará en vigor el ${activationForm.vigente_desde}.`
                        : "Ya está disponible para nuevas operaciones.",
                    life: 3500,
                });
            },
        },
    );
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
                {
                    preserveScroll: true,
                    only: ["productos"],
                    onSuccess: () =>
                        toast.add({
                            severity: "success",
                            summary: "Versión retirada",
                            life: 3000,
                        }),
                },
            ),
    });

const simForm = ref({
    monto: 15000,
    periodicidad: "mensual",
    plazo: 12,
    metodo: "cuota_nivelada",
    fecha: props.simuladorDefaults.fecha_disposicion,
    comisiones_opcionales: [],
});
const simulatorVersion = ref(null);
const simulatorPeriodOptions = computed(() =>
    (simulatorVersion.value?.periodicidades ?? []).map((item) => ({
        label: periodicityLabel[item.periodicidad],
        value: item.periodicidad,
    })),
);
const simulatorMethodOptions = computed(() =>
    (simulatorVersion.value?.reglas?.metodos_amortizacion ?? []).map(
        (value) => ({ label: methodLabel[value], value }),
    ),
);
const simulatorOptionalCommissions = computed(() =>
    (simulatorVersion.value?.comisiones ?? []).filter(
        isSelectableOptionalCommission,
    ),
);
const simulatorConditionalCommissions = computed(() =>
    (simulatorVersion.value?.comisiones ?? []).filter(isConditionalCommission),
);
const openSimulator = (version) => {
    simulatorVersion.value = version;
    const period = version.periodicidades[0];
    simForm.value = {
        monto: Number(version.monto_minimo),
        periodicidad: period?.periodicidad ?? "mensual",
        plazo: period?.plazo_predeterminado ?? 12,
        metodo: version.reglas?.metodos_amortizacion?.[0] ?? "cuota_nivelada",
        fecha: props.simuladorDefaults.fecha_disposicion,
        comisiones_opcionales: [],
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
        if (!value.some((product) => product.id === selectedId.value))
            selectedId.value = value[0]?.id ?? null;
    },
);
</script>

<template>
    <AppLayout title="Productos crediticios">
        <template #card-header>
            <div class="flex flex-col gap-4 p-5 md:flex-row md:items-center md:justify-between">
                <div><p class="text-sm font-semibold uppercase tracking-widest text-primary">Configuración comercial</p><h1 class="mt-1 text-2xl font-bold">Productos crediticios</h1><p class="mt-1 text-sm text-surface-500">Crédito simple V1 · parámetros versionados y simulación transparente</p></div>
                <div class="flex flex-wrap gap-2"><Button v-if="can('manage commissions productos-crediticios')" label="Catálogo de comisiones" icon="pi pi-list" severity="secondary" @click="catalogVisible = true" /><Button v-if="can('create productos-crediticios')" label="Nuevo producto" icon="pi pi-plus" @click="openCreate" /></div>
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
                            <div class="mt-3 flex items-center gap-2 text-xs text-surface-500"><i class="pi pi-clone" /><span>{{ product.versiones.length }} {{ product.versiones.length === 1 ? 'versión' : 'versiones' }}</span><span>·</span><span>{{ product.versiones[0] ? moneyCompact(product.versiones[0].monto_maximo) : 'Sin configurar' }}</span></div>
                        </button>
                    </div>
                    <div v-else class="px-4 py-12 text-center"><i class="pi pi-wallet text-3xl text-surface-300" /><p class="mt-3 font-medium">Sin productos</p><p class="mt-1 text-sm text-surface-500">Crea el primer producto de crédito simple.</p></div>
                </aside>

                <main v-if="selected" class="min-w-0 space-y-5">
                    <section class="overflow-hidden rounded-2xl bg-gradient-to-br from-primary-700 via-primary-600 to-indigo-600 p-5 text-white shadow-lg shadow-primary-900/10">
                        <div class="flex flex-col gap-5 md:flex-row md:items-start md:justify-between"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="rounded-full bg-white/15 px-2.5 py-1 text-xs font-semibold">{{ selected.clave }}</span><span class="rounded-full bg-white/15 px-2.5 py-1 text-xs">Crédito simple</span></div><h2 class="mt-3 truncate text-2xl font-bold">{{ selected.nombre }}</h2><p class="mt-1 max-w-2xl text-sm text-white/75">{{ selected.descripcion || 'Sin descripción comercial.' }}</p></div><Button v-if="latest && can('simulate productos-crediticios')" label="Simular" icon="pi pi-calculator" severity="secondary" @click="openSimulator(latest)" /></div>
                        <div v-if="latest" class="mt-6 grid grid-cols-2 gap-3 md:grid-cols-4"><div class="rounded-xl bg-white/10 p-3"><p class="text-xs text-white/70">Montos</p><p class="mt-1 font-semibold">{{ moneyCompact(latest.monto_minimo) }} – {{ moneyCompact(latest.monto_maximo) }}</p></div><div class="rounded-xl bg-white/10 p-3"><p class="text-xs text-white/70">Tasa ordinaria anual</p><p class="mt-1 text-lg font-semibold">{{ percent(latest.tasa_ordinaria_anual) }}</p></div><div class="rounded-xl bg-white/10 p-3"><p class="text-xs text-white/70">Mora anual</p><p class="mt-1 text-lg font-semibold">{{ percent(latest.tasa_moratoria_anual) }}</p></div><div class="rounded-xl bg-white/10 p-3"><p class="text-xs text-white/70">Versión actual</p><p class="mt-1 text-lg font-semibold">v{{ latest.numero }} · {{ stateMeta(latest.estado)[0] }}</p></div></div>
                    </section>
                    <section class="rounded-2xl border border-surface-200 bg-surface-0 p-4 dark:border-surface-700 dark:bg-surface-900">
                        <div class="mb-4"><h3 class="font-semibold">Versiones y vigencia</h3><p class="text-sm text-surface-500">Las versiones activadas son históricas e inmutables.</p></div>
                        <DataTable :value="selected.versiones" striped-rows responsive-layout="scroll" :table-style="{ minWidth: '52rem' }">
                            <Column header="Versión" style="width:16%"><template #body="{ data }"><div class="flex items-center gap-2"><Avatar :label="`v${data.numero}`" shape="circle" class="bg-primary-100 font-bold text-primary-700" /><ProductVersionStatus :state="data.estado" :used="data.usos_count > 0" /></div></template></Column>
                            <Column header="Condiciones"><template #body="{ data }"><p class="font-medium">{{ moneyCompact(data.monto_minimo) }} – {{ moneyCompact(data.monto_maximo) }}</p><p class="text-sm text-surface-500">{{ percent(data.tasa_ordinaria_anual) }} ordinaria · {{ data.dias_gracia_mora }} días de gracia</p></template></Column>
                            <Column header="Periodicidad"><template #body="{ data }"><div class="flex flex-wrap gap-1"><Chip v-for="item in data.periodicidades" :key="item.id" :label="`${periodicityLabel[item.periodicidad]} ${item.plazo_minimo}–${item.plazo_maximo}`" /></div></template></Column>
                            <Column header="Vigencia"><template #body="{ data }"><p class="text-sm">{{ data.vigente_desde || 'Sin programar' }}</p><p class="text-xs text-surface-500">{{ data.cat_aplica ? 'CAT aplicable' : 'CAT no aplicable' }}</p></template></Column>
                            <Column header="Acciones"><template #body="{ data }"><div class="flex flex-nowrap gap-1"><Button v-if="data.estado === 'borrador' && can('update productos-crediticios')" v-tooltip.top="'Editar borrador'" icon="pi pi-pencil" text rounded :aria-label="`Editar versión ${data.numero}`" @click="openEdit(data)" /><Button v-if="can('simulate productos-crediticios')" v-tooltip.top="'Simular'" icon="pi pi-calculator" text rounded :aria-label="`Simular versión ${data.numero}`" @click="openSimulator(data)" /><Button v-if="can('version productos-crediticios')" v-tooltip.top="'Crear nueva versión'" icon="pi pi-copy" text rounded :aria-label="`Duplicar versión ${data.numero}`" @click="versionCopy(data)" /><Button v-if="data.estado === 'borrador' && can('activate productos-crediticios')" v-tooltip.top="'Activar'" icon="pi pi-check-circle" text rounded severity="success" :aria-label="`Activar versión ${data.numero}`" @click="activate(data)" /><Button v-if="['activa','programada'].includes(data.estado) && can('retire productos-crediticios')" v-tooltip.top="'Retirar'" icon="pi pi-ban" text rounded severity="danger" :aria-label="`Retirar versión ${data.numero}`" @click="retire(data)" /></div></template></Column>
                        </DataTable>
                    </section>
                </main>
            </div>

            <Dialog id="product-editor" v-model:visible="editorVisible" :header="editingVersion ? `Editar versión ${editingVersion.numero}` : 'Nuevo producto crediticio'" modal maximizable :style="{ width: 'min(1080px, 96vw)' }">
                <form class="space-y-5" @submit.prevent="submit">
                    <div class="flex flex-col gap-3 rounded-xl bg-primary-50 p-4 dark:bg-primary-950/30 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-semibold">Configuración segura y explicada</p><p class="text-sm text-surface-600 dark:text-surface-300">El borrador se congela al activarse. Usa los botones de ayuda para revisar cada concepto.</p></div><Button type="button" label="Guía financiera" icon="pi pi-book" text @click="helpGuideVisible = true" /></div>
                    <Tabs v-model:value="activeEditorTab">
                        <TabList>
                            <Tab v-for="tab in [{v:'general',l:'Información'},{v:'condiciones',l:'Montos y tasas'},{v:'reglas',l:'Reglas'},{v:'comisiones',l:'Comisiones'}]" :key="tab.v" :value="tab.v"><span class="flex items-center gap-2">{{ tab.l }}<Badge v-if="tabErrorCount(tab.v)" :value="tabErrorCount(tab.v)" severity="danger" /></span></Tab>
                        </TabList>
                        <TabPanels>
                            <TabPanel value="general"><div class="grid gap-4 pt-3 md:grid-cols-2">
                                <div><div class="flex items-center"><label for="product-key" class="text-sm font-medium">Clave *</label><FinancialFieldHelp :title="help.key[0]" :description="help.key[1]" :example="help.key[2]" /></div><InputText id="product-key" v-model="form.clave" :invalid="!!error('clave')" :aria-invalid="!!error('clave')" fluid /><Message v-if="error('clave')" severity="error" size="small">{{ error('clave') }}</Message></div>
                                <div><label for="product-name" class="mb-1 block text-sm font-medium">Nombre comercial *</label><InputText id="product-name" v-model="form.nombre" :invalid="!!error('nombre')" :aria-invalid="!!error('nombre')" fluid /><Message v-if="error('nombre')" severity="error" size="small">{{ error('nombre') }}</Message></div>
                                <div class="md:col-span-2"><label for="product-description" class="mb-1 block text-sm font-medium">Descripción comercial</label><Textarea id="product-description" v-model="form.descripcion" rows="3" fluid /></div>
                                <div class="flex items-center gap-2"><Checkbox v-model="form.version.cat_aplica" input-id="cat-applies" binary /><label for="cat-applies">Mostrar CAT informativo</label><FinancialFieldHelp :title="help.cat[0]" :description="help.cat[1]" :example="help.cat[2]" :note="help.cat[3]" /></div>
                                <div v-if="!form.version.cat_aplica"><label for="cat-reason" class="mb-1 block text-sm font-medium">Motivo de no aplicación *</label><InputText id="cat-reason" v-model="form.version.cat_no_aplica_motivo" :invalid="!!error('version.cat_no_aplica_motivo')" :aria-invalid="!!error('version.cat_no_aplica_motivo')" fluid /><Message v-if="error('version.cat_no_aplica_motivo')" severity="error" size="small">{{ error('version.cat_no_aplica_motivo') }}</Message></div>
                            </div></TabPanel>
                            <TabPanel value="condiciones"><div class="grid gap-4 pt-3 md:grid-cols-2 lg:grid-cols-4">
                                <div v-for="field in [{k:'monto_minimo',l:'Monto mínimo',h:'amount'},{k:'monto_maximo',l:'Monto máximo',h:'amount'}]" :key="field.k"><div class="flex items-center"><label :for="field.k" class="text-sm font-medium">{{ field.l }} *</label><FinancialFieldHelp :title="help[field.h][0]" :description="help[field.h][1]" :example="help[field.h][2]" /></div><InputNumber :id="field.k" v-model="form.version[field.k]" mode="currency" currency="MXN" locale="es-MX" :invalid="!!error(`version.${field.k}`)" :aria-invalid="!!error(`version.${field.k}`)" fluid /><Message v-if="error(`version.${field.k}`)" severity="error" size="small">{{ error(`version.${field.k}`) }}</Message></div>
                                <div v-for="field in [{k:'tasa_ordinaria_anual',l:'Tasa ordinaria anual'},{k:'tasa_moratoria_anual',l:'Tasa moratoria anual'}]" :key="field.k"><div class="flex items-center"><label :for="field.k" class="text-sm font-medium">{{ field.l }} *</label><FinancialFieldHelp :title="help.rate[0]" :description="help.rate[1]" :example="help.rate[2]" /></div><InputNumber :id="field.k" v-model="form.version[field.k]" suffix=" %" :min-fraction-digits="2" :invalid="!!error(`version.${field.k}`)" :aria-invalid="!!error(`version.${field.k}`)" fluid /><Message v-if="error(`version.${field.k}`)" severity="error" size="small">{{ error(`version.${field.k}`) }}</Message></div>
                                <div><div class="flex items-center"><label for="grace-days" class="text-sm font-medium">Días de gracia de mora</label><FinancialFieldHelp :title="help.grace[0]" :description="help.grace[1]" :example="help.grace[2]" /></div><InputNumber id="grace-days" v-model="form.version.dias_gracia_mora" :min="0" :invalid="!!error('version.dias_gracia_mora')" :aria-invalid="!!error('version.dias_gracia_mora')" fluid /><Message v-if="error('version.dias_gracia_mora')" severity="error" size="small">{{ error('version.dias_gracia_mora') }}</Message></div>
                            </div><Divider /><div class="mb-3 flex items-center justify-between"><div><div class="flex items-center"><h4 class="font-semibold">Plazos por periodicidad</h4><FinancialFieldHelp :title="help.term[0]" :description="help.term[1]" :example="help.term[2]" /></div><p class="text-sm text-surface-500">V1 admite semanal, quincenal y mensual.</p></div><Button type="button" label="Agregar" icon="pi pi-plus" text :disabled="form.version.periodicidades.length >= 3" @click="addPeriodicity" /></div>
                                <Message v-if="error('version.periodicidades')" severity="error" size="small">{{ error('version.periodicidades') }}</Message>
                                <div v-for="(item,index) in form.version.periodicidades" :key="index" class="mb-3 grid items-start gap-3 rounded-xl border border-surface-200 p-3 md:grid-cols-[1.2fr_1fr_1fr_1fr_auto]"><div><label class="mb-1 block text-xs font-medium">Periodicidad</label><Select v-model="item.periodicidad" :options="Object.keys(periodicityLabel)" :option-label="value => periodicityLabel[value]" :invalid="!!error(`version.periodicidades.${index}.periodicidad`)" :aria-invalid="!!error(`version.periodicidades.${index}.periodicidad`)" fluid /><Message v-if="error(`version.periodicidades.${index}.periodicidad`)" severity="error" size="small">{{ error(`version.periodicidades.${index}.periodicidad`) }}</Message></div><div v-for="field in [{k:'plazo_minimo',l:'Mínimo'},{k:'plazo_maximo',l:'Máximo'},{k:'plazo_predeterminado',l:'Predeterminado'}]" :key="field.k"><label class="mb-1 block text-xs font-medium">{{ field.l }}</label><InputNumber v-model="item[field.k]" :min="1" :invalid="!!error(`version.periodicidades.${index}.${field.k}`)" :aria-invalid="!!error(`version.periodicidades.${index}.${field.k}`)" fluid /><Message v-if="error(`version.periodicidades.${index}.${field.k}`)" severity="error" size="small">{{ error(`version.periodicidades.${index}.${field.k}`) }}</Message></div><Button type="button" icon="pi pi-trash" text severity="danger" aria-label="Quitar periodicidad" :disabled="form.version.periodicidades.length === 1" @click="form.version.periodicidades.splice(index,1)" /></div>
                            </TabPanel>
                            <TabPanel value="reglas"><div class="grid gap-5 pt-3 md:grid-cols-2"><div><div class="flex items-center"><label class="text-sm font-medium">Métodos de amortización *</label><FinancialFieldHelp :title="help.method[0]" :description="help.method[1]" :example="help.method[2]" /></div><div class="mt-2 flex flex-col gap-3 rounded-xl border border-surface-200 p-4"><div v-for="method in Object.keys(methodLabel)" :key="method" class="flex items-center gap-2"><Checkbox v-model="form.version.reglas.metodos_amortizacion" :input-id="method" :value="method" /><label :for="method">{{ methodLabel[method] }}</label></div></div><Message v-if="error('version.reglas.metodos_amortizacion')" severity="error" size="small">{{ error('version.reglas.metodos_amortizacion') }}</Message></div><div class="rounded-xl border border-surface-200 p-4"><p class="font-medium">Convenciones V1</p><ul class="mt-3 space-y-2 text-sm text-surface-600"><li>Interés por días reales / 360</li><li>Mora sobre capital vencido</li><li>Redondeo half-up a centavos</li><li>Sin ajuste por inhábiles ni IVA</li></ul></div><div class="space-y-4"><div class="flex items-center gap-3"><Checkbox v-model="form.version.reglas.permite_prepago_parcial" input-id="partial-prepay" binary /><label for="partial-prepay">Permitir prepago parcial</label></div><div class="flex items-center gap-3"><Checkbox v-model="form.version.reglas.permite_liquidacion_anticipada" input-id="full-prepay" binary /><label for="full-prepay">Permitir liquidación anticipada</label></div></div><div><div class="flex items-center"><label class="text-sm font-medium">Aplicar prepago para</label><FinancialFieldHelp :title="help.prepay[0]" :description="help.prepay[1]" :example="help.prepay[2]" /></div><Select v-model="form.version.reglas.aplicacion_prepago" :options="[{label:'Reducir plazo',value:'reducir_plazo'},{label:'Reducir pago',value:'reducir_pago'}]" option-label="label" option-value="value" fluid /><label class="mb-1 mt-3 block text-sm font-medium">Monto mínimo de prepago</label><InputNumber v-model="form.version.reglas.monto_minimo_prepago" mode="currency" currency="MXN" locale="es-MX" :invalid="!!error('version.reglas.monto_minimo_prepago')" :aria-invalid="!!error('version.reglas.monto_minimo_prepago')" fluid /><Message v-if="error('version.reglas.monto_minimo_prepago')" severity="error" size="small">{{ error('version.reglas.monto_minimo_prepago') }}</Message></div></div></TabPanel>
                            <TabPanel value="comisiones"><div class="mb-3 flex items-center justify-between pt-3"><div><div class="flex items-center"><h4 class="font-semibold">Comisiones configurables</h4><FinancialFieldHelp :title="help.commission[0]" :description="help.commission[1]" :example="help.commission[2]" /></div><p class="text-sm text-surface-500">El cobro, saldo y CAT dependen de su modalidad.</p></div><Button type="button" label="Agregar comisión" icon="pi pi-plus" text @click="addCommission" /></div><div v-if="!form.version.comisiones.length" class="rounded-xl border border-dashed border-surface-300 p-8 text-center text-sm text-surface-500">Este producto no tiene comisiones.</div>
                                <div v-for="(item,index) in form.version.comisiones" :key="index" class="mb-4 rounded-xl border border-surface-200 p-4">
                                    <div class="grid items-start gap-3 md:grid-cols-2 lg:grid-cols-4">
                                        <div><label class="mb-1 block text-xs font-medium">Concepto *</label><Select v-model="item.concepto_comision_id" :options="conceptosComision" option-label="nombre" option-value="id" :invalid="!!error(`version.comisiones.${index}.concepto_comision_id`)" :aria-invalid="!!error(`version.comisiones.${index}.concepto_comision_id`)" fluid /><Message v-if="error(`version.comisiones.${index}.concepto_comision_id`)" severity="error" size="small">{{ error(`version.comisiones.${index}.concepto_comision_id`) }}</Message></div>
                                        <div><label class="mb-1 block text-xs font-medium">Tipo *</label><Select v-model="item.tipo_importe" :options="[{label:'Importe fijo',value:'fijo'},{label:'Porcentaje del monto solicitado',value:'porcentaje'}]" option-label="label" option-value="value" :invalid="!!error(`version.comisiones.${index}.tipo_importe`)" :aria-invalid="!!error(`version.comisiones.${index}.tipo_importe`)" fluid @change="changeCommissionType(item)" /><Message v-if="error(`version.comisiones.${index}.tipo_importe`)" severity="error" size="small">{{ error(`version.comisiones.${index}.tipo_importe`) }}</Message></div>
                                        <div><label class="mb-1 block text-xs font-medium">Importe *</label><InputNumber v-model="item.importe" :suffix="item.tipo_importe === 'porcentaje' ? ' %' : ''" :mode="item.tipo_importe === 'fijo' ? 'currency' : 'decimal'" currency="MXN" locale="es-MX" :invalid="!!error(`version.comisiones.${index}.importe`)" :aria-invalid="!!error(`version.comisiones.${index}.importe`)" fluid /><Message v-if="error(`version.comisiones.${index}.importe`)" severity="error" size="small">{{ error(`version.comisiones.${index}.importe`) }}</Message></div>
                                        <div><label class="mb-1 block text-xs font-medium">Momento *</label><Select v-model="item.momento_cobro" :options="momentOptions" option-label="label" option-value="value" :invalid="!!error(`version.comisiones.${index}.momento_cobro`)" :aria-invalid="!!error(`version.comisiones.${index}.momento_cobro`)" fluid @change="changeCommissionMoment(item)" /><Message v-if="error(`version.comisiones.${index}.momento_cobro`)" severity="error" size="small">{{ error(`version.comisiones.${index}.momento_cobro`) }}</Message></div>
                                        <div v-if="item.momento_cobro === 'inicio'" class="md:col-span-2"><label class="mb-1 block text-xs font-medium">Modalidad inicial *</label><Select v-model="item.modalidad_cobro" :options="modalityOptions" option-label="label" option-value="value" :invalid="!!error(`version.comisiones.${index}.modalidad_cobro`)" :aria-invalid="!!error(`version.comisiones.${index}.modalidad_cobro`)" fluid /><Message v-if="error(`version.comisiones.${index}.modalidad_cobro`)" severity="error" size="small">{{ error(`version.comisiones.${index}.modalidad_cobro`) }}</Message></div>
                                        <div class="pt-6"><div class="flex items-center gap-2"><Checkbox v-model="item.obligatoria" :input-id="`required-${index}`" binary @change="updateCommissionCat(item)" /><label :for="`required-${index}`">Obligatoria</label></div><p class="mt-1 text-xs text-surface-500">{{ item.obligatoria ? 'Se cobra sin contratar un servicio adicional.' : 'El cliente decide si contrata este cargo adicional.' }}</p><Message v-if="error(`version.comisiones.${index}.obligatoria`)" severity="error" size="small">{{ error(`version.comisiones.${index}.obligatoria`) }}</Message></div>
                                        <div class="pt-6"><div class="flex items-center gap-2"><Checkbox v-model="item.incluye_cat" :input-id="`cat-${index}`" binary disabled /><label :for="`cat-${index}`">Incluir en CAT base</label></div><p class="mt-1 text-xs" :class="item.incluye_cat ? 'text-green-700 dark:text-green-400' : 'text-surface-500'">{{ catReason(item) }}</p></div>
                                    </div>
                                    <Message v-for="message in unhandledCommissionErrors(index)" :key="message" class="mt-3" severity="error" size="small">{{ message }}</Message>
                                    <div class="mt-3 flex justify-end"><Button type="button" label="Quitar" icon="pi pi-trash" text severity="danger" @click="form.version.comisiones.splice(index,1)" /></div>
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                    <Message v-if="form.errors.version" severity="error" :closable="false">{{ form.errors.version }}</Message>
                    <div class="flex justify-end gap-2 border-t border-surface-200 pt-4"><Button type="button" label="Cancelar" severity="secondary" @click="editorVisible = false" /><Button type="submit" label="Guardar borrador" icon="pi pi-save" :loading="form.processing" /></div>
                </form>
            </Dialog>

            <Dialog v-model:visible="activationVisible" :header="`Activar versión ${activatingVersion?.numero ?? ''}`" modal :style="{ width: 'min(34rem, 94vw)' }">
                <form class="space-y-4" @submit.prevent="submitActivation">
                    <Message severity="info" :closable="false">La versión quedará inmutable desde que confirmes. La fecha define cuándo podrá utilizarse en nuevas operaciones.</Message>
                    <div><label for="activation-date" class="mb-1 block text-sm font-medium">Fecha de vigencia *</label><DatePicker id="activation-date" v-model="activationForm.vigente_desde" date-format="yy-mm-dd" update-model-type="string" :min-date="new Date(`${companyToday()}T00:00:00`)" :invalid="!!activationForm.errors.vigente_desde" :aria-invalid="!!activationForm.errors.vigente_desde" fluid /><Message v-if="activationForm.errors.vigente_desde" severity="error" size="small">{{ activationForm.errors.vigente_desde }}</Message><p class="mt-1 text-xs text-surface-500">Zona horaria: {{ activacionDefaults.zona_horaria }}</p></div>
                    <div class="rounded-xl border border-surface-200 p-3 text-sm"><p class="font-medium">{{ activationIsScheduled ? 'Activación programada' : 'Activación inmediata' }}</p><p class="mt-1 text-surface-600 dark:text-surface-300">{{ activationIsScheduled ? `La versión activa actual continuará disponible hasta ${activationForm.vigente_desde}; después se retirará automáticamente.` : 'Esta versión sustituirá ahora a la versión activa para nuevas operaciones.' }}</p><p class="mt-2 text-xs text-surface-500">Los créditos existentes conservarán la versión y el snapshot con los que fueron originados.</p></div>
                    <div class="flex justify-end gap-2"><Button type="button" label="Cancelar" severity="secondary" @click="activationVisible = false" /><Button type="submit" :label="activationIsScheduled ? 'Programar activación' : 'Activar ahora'" icon="pi pi-lock" :loading="activationForm.processing" /></div>
                </form>
            </Dialog>

            <Drawer v-model:visible="helpGuideVisible" header="Guía de configuración financiera" position="right" class="!w-full md:!w-[36rem]"><div class="space-y-5 text-sm leading-relaxed"><Message severity="info" :closable="false">Estas explicaciones describen el comportamiento de Kronik. No sustituyen una revisión legal o contractual.</Message><section><h3 class="font-semibold">Días reales / 360</h3><p class="mt-1 text-surface-600">Cada interés usa los días naturales del periodo: saldo × tasa anual × días / 360. Por eso febrero y un mes de 31 días pueden generar intereses diferentes.</p></section><section><h3 class="font-semibold">Fechas mensuales</h3><p class="mt-1 text-surface-600">Se conserva el día de la disposición. Si ese día no existe, se usa el último del mes sin perder el ancla: 30-ene → 28-feb → 30-mar. Si la disposición fue al cierre del mes, todos los pagos permanecen al cierre: 31-ene → 28-feb → 31-mar.</p></section><section><h3 class="font-semibold">Cuota nivelada</h3><p class="mt-1 text-surface-600">Busca un pago base constante considerando los factores de cada periodo. La última cuota absorbe únicamente el residuo de centavos.</p></section><section><h3 class="font-semibold">Comisión inicial</h3><p class="mt-1 text-surface-600">Puede pagarse separadamente, retenerse del desembolso o financiarse. Financiarla aumenta el saldo, los intereses y consume el límite máximo del producto.</p></section><section><h3 class="font-semibold">CAT base</h3><p class="mt-1 text-surface-600">Incluye cargos obligatorios de inicio y de cada pago. Excluye IVA, servicios opcionales, mora, incumplimiento y prepago. Las opcionales seleccionadas sí cambian la tabla y el costo total del escenario.</p></section></div></Drawer>

            <Dialog v-model:visible="catalogVisible" header="Catálogo de comisiones" modal :style="{ width: 'min(820px, 96vw)' }"><div class="grid gap-5 md:grid-cols-[1fr_1.15fr]"><form class="space-y-3 rounded-xl border border-surface-200 p-4" @submit.prevent="saveCommissionConcept"><h4 class="font-semibold">Nuevo concepto</h4><div><label for="commission-key" class="mb-1 block text-sm font-medium">Clave *</label><InputText id="commission-key" v-model="commissionForm.clave" :invalid="!!commissionError('clave')" :aria-invalid="!!commissionError('clave')" fluid /><Message v-if="commissionError('clave')" severity="error" size="small">{{ commissionError('clave') }}</Message></div><div><label for="commission-name" class="mb-1 block text-sm font-medium">Nombre *</label><InputText id="commission-name" v-model="commissionForm.nombre" :invalid="!!commissionError('nombre')" :aria-invalid="!!commissionError('nombre')" fluid /><Message v-if="commissionError('nombre')" severity="error" size="small">{{ commissionError('nombre') }}</Message></div><div><label class="mb-1 block text-sm font-medium">Descripción</label><Textarea v-model="commissionForm.descripcion" rows="3" fluid /></div><div><label class="mb-1 block text-sm font-medium">Referencia RECO</label><InputText v-model="commissionForm.referencia_reco" fluid /></div><div class="flex items-center gap-2"><Checkbox v-model="commissionForm.es_oficial_reco" input-id="reco-official" binary /><label for="reco-official">Concepto tomado de RECO</label></div><div class="flex items-center gap-2"><Checkbox v-model="commissionForm.revisado" input-id="reco-reviewed" binary /><label for="reco-reviewed">Revisión documental completada</label></div><Button type="submit" label="Agregar concepto" icon="pi pi-plus" :loading="commissionForm.processing" fluid /></form><div><h4 class="mb-3 font-semibold">Conceptos disponibles</h4><div class="max-h-[32rem] space-y-2 overflow-auto"><div v-for="concept in conceptosComision" :key="concept.id" class="flex items-center justify-between gap-3 rounded-xl bg-surface-50 p-3 dark:bg-surface-800"><div class="min-w-0"><p class="truncate font-medium">{{ concept.nombre }}</p><p class="truncate text-xs text-surface-500">{{ concept.clave }}<span v-if="concept.es_oficial_reco"> · RECO</span><span v-if="concept.revisado"> · revisado</span></p></div><Button icon="pi pi-ban" text rounded severity="danger" :aria-label="`Retirar ${concept.nombre}`" @click="retireCommissionConcept(concept)" /></div></div></div></div></Dialog>

            <Drawer v-model:visible="simulatorVisible" header="Simulador de crédito simple" position="right" class="!w-full lg:!w-[min(92vw,88rem)]"><div class="space-y-5"><div class="rounded-xl bg-primary-50 p-4 dark:bg-primary-950/30"><p class="text-sm text-surface-500">Simulando</p><p class="font-semibold">{{ selected?.nombre }} · versión {{ simulatorVersion?.numero }}</p></div><div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-6"><div><div class="flex items-center"><label class="text-sm font-medium">Monto solicitado</label><FinancialFieldHelp :title="help.amount[0]" :description="help.amount[1]" :example="help.amount[2]" /></div><InputNumber v-model="simForm.monto" mode="currency" currency="MXN" locale="es-MX" fluid /></div><div><div class="flex items-center"><label class="text-sm font-medium">Fecha de disposición</label><FinancialFieldHelp title="Calendario mensual" description="Conserva el día original; si no existe en un mes, usa su último día y después recupera el ancla." example="30-ene → 28-feb → 30-mar" /></div><DatePicker v-model="simForm.fecha" date-format="yy-mm-dd" update-model-type="string" fluid /></div><div><label class="mb-1 block text-sm font-medium">Periodicidad</label><Select v-model="simForm.periodicidad" :options="simulatorPeriodOptions" option-label="label" option-value="value" fluid /></div><div><label class="mb-1 block text-sm font-medium">Número de pagos</label><InputNumber v-model="simForm.plazo" :min="1" fluid /></div><div><label class="mb-1 block text-sm font-medium">Amortización</label><Select v-model="simForm.metodo" :options="simulatorMethodOptions" option-label="label" option-value="value" fluid /></div><div class="flex items-end"><Button label="Calcular escenario" icon="pi pi-calculator" :loading="simulating" fluid @click="simulate" /></div></div>
                    <section v-if="simulatorOptionalCommissions.length || simulatorConditionalCommissions.length" class="rounded-xl border border-surface-200 p-4"><div class="mb-3"><h3 class="font-semibold">Comisiones del escenario</h3><p class="text-sm text-surface-500">Las opcionales modifican la tabla y los totales, pero no el CAT base del producto.</p></div><div v-if="simulatorOptionalCommissions.length" class="grid gap-2 md:grid-cols-2"><label v-for="item in simulatorOptionalCommissions" :key="item.id" :for="`sim-commission-${item.id}`" class="flex cursor-pointer items-start gap-3 rounded-lg bg-surface-50 p-3 dark:bg-surface-800"><Checkbox v-model="simForm.comisiones_opcionales" :input-id="`sim-commission-${item.id}`" :value="item.id" /><span class="min-w-0"><span class="block truncate font-medium">{{ item.concepto?.nombre ?? 'Comisión opcional' }}</span><span class="block text-xs text-surface-500">{{ item.momento_cobro === 'cada_pago' ? 'En cada pago' : 'Al inicio' }} · {{ item.tipo_importe === 'porcentaje' ? `${Number(item.importe)}%` : money(item.importe) }}</span></span></label></div><Message v-if="simulatorConditionalCommissions.length" class="mt-3" severity="secondary" :closable="false">{{ simulatorConditionalCommissions.map(item => item.concepto?.nombre ?? 'Comisión por evento').join(', ') }} requieren simular el evento o la liquidación y no se aplican en esta tabla base.</Message></section>
                    <div v-if="simulation" class="space-y-4"><div class="grid grid-cols-2 gap-3 lg:grid-cols-6"><div class="rounded-xl border border-surface-200 p-3"><p class="text-xs text-surface-500">Saldo financiado</p><p class="mt-1 font-bold">{{ money(simulation.escenario.saldo_financiado) }}</p></div><div class="rounded-xl border border-surface-200 p-3"><p class="text-xs text-surface-500">Efectivo entregado</p><p class="mt-1 font-bold">{{ money(simulation.escenario.efectivo_entregado) }}</p></div><div class="rounded-xl border border-surface-200 p-3"><p class="text-xs text-surface-500">Obligaciones</p><p class="mt-1 font-bold">{{ money(simulation.total_pagar) }}</p></div><div class="rounded-xl border border-surface-200 p-3"><p class="text-xs text-surface-500">Intereses</p><p class="mt-1 font-bold">{{ money(simulation.total_intereses) }}</p></div><div class="rounded-xl border border-surface-200 p-3"><p class="text-xs text-surface-500">Comisiones</p><p class="mt-1 font-bold">{{ money(simulation.total_comisiones) }}</p></div><div class="rounded-xl bg-primary p-3 text-primary-contrast"><p class="text-xs opacity-75">CAT base del producto</p><p class="mt-1 text-xl font-bold">{{ simulation.cat ? `${simulation.cat}%` : 'N/A' }}</p></div></div><Message :severity="simulation.comisiones_opcionales_seleccionadas?.length ? 'warn' : 'secondary'" :closable="false">{{ simulation.cat_leyenda }}<span v-if="simulation.comisiones_opcionales_seleccionadas?.length"> La tabla incluye {{ simulation.comisiones_opcionales_seleccionadas.length }} comisión(es) opcional(es) que no forman parte de este CAT.</span></Message>
                        <DataTable :value="simulation.tabla" size="small" paginator :rows="8" scrollable striped-rows :table-style="{ minWidth: '96rem' }"><Column field="numero" header="#" frozen /><Column field="tipo" header="Movimiento"><template #body="{data}"><Tag :value="data.tipo === 'disposicion' ? 'Disposición' : 'Pago'" :severity="data.tipo === 'disposicion' ? 'info' : 'secondary'" /></template></Column><Column field="fecha" header="Fecha" /><Column field="dias" header="Días" /><Column field="saldo_inicial" header="Saldo inicial"><template #body="{data}">{{ money(data.saldo_inicial) }}</template></Column><Column field="disposicion" header="Disposición"><template #body="{data}">{{ money(data.disposicion) }}</template></Column><Column field="capital" header="Capital"><template #body="{data}">{{ money(data.capital) }}</template></Column><Column field="interes" header="Interés"><template #body="{data}">{{ money(data.interes) }}</template></Column><Column field="comisiones" header="Comisiones"><template #body="{data}"><span class="font-medium">{{ money(data.comisiones) }}</span><span v-if="data.comisiones_detalle?.length > 1" class="ml-1 text-xs text-primary">+{{ data.comisiones_detalle.length - 1 }}</span><ul v-if="data.comisiones_detalle?.length" class="mt-1 text-xs text-surface-500"><li v-for="item in data.comisiones_detalle" :key="`${item.clave}-${item.modalidad}`" class="whitespace-nowrap">{{ item.concepto }} · {{ money(item.importe) }}<span v-if="item.modalidad"> · {{ modalityLabel(item.modalidad) }}</span></li></ul></template></Column><Column field="pago_total" header="Pago total"><template #body="{data}"><strong>{{ money(data.pago_total) }}</strong></template></Column><Column field="saldo_final" header="Saldo final"><template #body="{data}">{{ money(data.saldo_final) }}</template></Column><Column field="capital_acumulado" header="Capital acum."><template #body="{data}">{{ money(data.capital_acumulado) }}</template></Column><Column field="interes_acumulado" header="Interés acum."><template #body="{data}">{{ money(data.interes_acumulado) }}</template></Column><Column field="comisiones_acumuladas" header="Comisiones acum."><template #body="{data}">{{ money(data.comisiones_acumuladas) }}</template></Column><Column field="pagado_acumulado" header="Pagado acum."><template #body="{data}">{{ money(data.pagado_acumulado) }}</template></Column></DataTable>
                        <Accordion v-if="simulation.formula_debug"><AccordionPanel value="formula"><AccordionHeader>Ver fórmula y sustitución de desarrollo</AccordionHeader><AccordionContent><div class="space-y-4 text-sm"><Message severity="warn" :closable="false">Diagnóstico disponible en local o para Super Admin. Los cálculos operativos usan decimales; no dependen de los valores redondeados del navegador.</Message><div><p><strong>Convención:</strong> {{ simulation.formula_debug.convencion }}</p><p class="mt-1 text-surface-600 dark:text-surface-300">{{ simulation.formula_debug.calendario }}</p></div><code class="block overflow-x-auto rounded-lg bg-surface-900 p-3 text-surface-0">{{ simulation.formula_debug.interes }}</code><code class="block overflow-x-auto rounded-lg bg-surface-900 p-3 text-surface-0">{{ simulation.formula_debug[simForm.metodo] }}</code><div><h4 class="mb-2 font-semibold">Significado de los símbolos</h4><div class="grid gap-2 md:grid-cols-2"><div v-for="item in simulation.formula_debug.simbolos" :key="item.simbolo" class="flex gap-3 rounded-lg border border-surface-200 p-3"><code class="min-w-12 font-bold text-primary">{{ item.simbolo }}</code><span class="text-surface-600 dark:text-surface-300">{{ item.significado }}</span></div></div></div><p><strong>Cuota exacta:</strong> {{ simulation.formula_debug.cuota_exacta ?? 'No aplica' }} · <strong>redondeada:</strong> {{ simulation.formula_debug.cuota_redondeada ?? 'No aplica' }}</p><DataTable :value="simulation.formula_debug.periodos" size="small" paginator :rows="6" scrollable><Column field="numero" header="#" /><Column field="dias" header="Días" /><Column field="sustitucion_interes" header="Sustitución" /></DataTable></div></AccordionContent></AccordionPanel></Accordion>
                    </div>
                </div></Drawer>
        </template>
    </AppLayout>
</template>
