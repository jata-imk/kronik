<script setup>
import { router, useForm } from "@inertiajs/vue3";
import {
    BadgeCheck,
    Download,
    Eye,
    FileArchive,
    FileCheck2,
    FileClock,
    FileText,
    Landmark,
    Link2,
    Pencil,
    Plus,
    ShieldCheck,
    Trash2,
    Upload,
    UserRound,
    UsersRound,
    WalletCards,
} from "lucide-vue-next";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import { computed, ref } from "vue";

import DocumentVersionStatus from "@/Components/Documents/DocumentVersionStatus.vue";
import PrivateDocumentViewer from "@/Components/Documents/PrivateDocumentViewer.vue";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";

const props = defineProps({
    cliente: { type: Object, required: true },
    relaciones: { type: Array, default: () => [] },
    clientesDisponibles: { type: Array, default: () => [] },
    resumen: { type: Object, required: true },
    opciones: { type: Object, required: true },
    can: { type: Object, required: true },
    plantillasDocumentos: { type: Array, default: () => [] },
});

const toast = useToast();
const confirm = useConfirm();
const activeTab = ref("perfil");
const viewerVisible = ref(false);
const viewer = ref({ url: "", downloadUrl: "", name: "" });
const generationDialog = ref(false);

const nombreCompleto = computed(() =>
    [
        props.cliente.primer_nombre,
        props.cliente.segundo_nombre,
        props.cliente.apellido_paterno,
        props.cliente.apellido_materno,
    ]
        .filter(Boolean)
        .join(" "),
);

const initials = computed(() =>
    [props.cliente.primer_nombre, props.cliente.apellido_paterno]
        .filter(Boolean)
        .map((part) => part[0])
        .join("")
        .toUpperCase(),
);

const profileProgress = computed(() =>
    Math.round(
        (props.resumen.perfil_completado / props.resumen.perfil_total) * 100,
    ),
);

const documentProgress = computed(() =>
    Math.min(
        100,
        Math.round(
            (props.resumen.documentos_recibidos /
                props.resumen.documentos_requeridos) *
                100,
        ),
    ),
);

const currentDocuments = computed(() =>
    props.cliente.documentos.filter((documento) => documento.es_actual),
);

const documentHistory = computed(() =>
    props.cliente.documentos.filter((documento) => !documento.es_actual),
);

const generationOptions = computed(() =>
    props.plantillasDocumentos.flatMap((template) =>
        template.versiones.map((version) => ({
            label: `${template.nombre} · v${version.numero}`,
            value: version.id,
            tipo: template.tipo,
        })),
    ),
);

const selectedGeneration = computed(() =>
    generationOptions.value.find(
        (option) => option.value === generationForm.version_id,
    ),
);

const generationForm = useForm({
    version_id: null,
    garantia_id: null,
    idempotency_key: crypto.randomUUID(),
});

function openGeneration() {
    generationForm.reset();
    generationForm.idempotency_key = crypto.randomUUID();
    generationDialog.value = true;
}

function generateDocument() {
    generationForm.post(route("documentos-generados.store", props.cliente.id), {
        preserveScroll: true,
        onSuccess: () => {
            generationDialog.value = false;
            toast.add({
                severity: "success",
                summary: "Generación solicitada",
                detail: "El PDF se está preparando de forma segura.",
                life: 4000,
            });
        },
        onError: (errors) =>
            toast.add({
                severity: "error",
                summary: "No se pudo generar",
                detail: Object.values(errors)[0] ?? "Revisa los datos.",
                life: 5000,
            }),
    });
}

function openViewer(url, downloadUrl, name) {
    viewer.value = { url, downloadUrl, name };
    viewerVisible.value = true;
}

const candidateOptions = computed(() =>
    props.clientesDisponibles.map((cliente) => ({
        id: cliente.id,
        label: [
            cliente.primer_nombre,
            cliente.segundo_nombre,
            cliente.apellido_paterno,
            cliente.apellido_materno,
        ]
            .filter(Boolean)
            .join(" "),
        rfc: cliente.datos_fiscales?.rfc ?? "Sin RFC",
    })),
);

const relatedClientOptions = computed(() => {
    const options = new Map();

    for (const relacion of props.relaciones) {
        const related = relacion.cliente;
        if (!related || options.has(related.id)) continue;

        options.set(related.id, {
            id: related.id,
            label: [
                related.primer_nombre,
                related.segundo_nombre,
                related.apellido_paterno,
                related.apellido_materno,
            ]
                .filter(Boolean)
                .join(" "),
            rfc: related.datos_fiscales?.rfc ?? "Sin RFC",
        });
    }

    return [...options.values()];
});

const profileForm = useForm({
    ocupacion: props.cliente.ocupacion ?? "",
    actividad_economica: props.cliente.actividad_economica ?? "",
    ingresos_mensuales: props.cliente.ingresos_mensuales
        ? Number(props.cliente.ingresos_mensuales)
        : null,
    egresos_mensuales: props.cliente.egresos_mensuales
        ? Number(props.cliente.egresos_mensuales)
        : null,
    origen_recursos: props.cliente.origen_recursos ?? "",
});

const declaredFlow = computed(
    () =>
        (profileForm.ingresos_mensuales ?? 0) -
        (profileForm.egresos_mensuales ?? 0),
);

const documentDialog = ref(false);
const reviewDialog = ref(false);
const selectedDocument = ref(null);
const documentForm = useForm({
    tipo: "adicional",
    nombre: "",
    archivo: null,
    reemplaza_documento_id: null,
    vence_en: null,
    notas: "",
});
const reviewForm = useForm({ estado: "validado", motivo_rechazo: "" });

const referenceDialog = ref(false);
const editingReference = ref(null);
const referenceForm = useForm({
    tipo: "personal",
    nombre: "",
    relacion: "",
    empresa: "",
    puesto: "",
    telefono_codigo_pais: "52",
    telefono: "",
    email: "",
    notas: "",
});

const linkDialog = ref(false);
const linkForm = useForm({
    cliente_vinculado_id: null,
    rol: "aval",
    notas: "",
});

const guaranteeDialog = ref(false);
const editingGuarantee = ref(null);
const guaranteeForm = useForm({
    propietario_cliente_id: null,
    tipo: "prendaria",
    descripcion: "",
    valor_estimado: null,
    moneda: props.opciones.moneda,
    notas: "",
});

const consentDialog = ref(false);
const consentForm = useForm({
    medio: "firma_autografa",
    otorgado_en: new Date(),
    vence_en: null,
    evidencia: null,
    notas: "",
});

const saveProfile = () => {
    profileForm.patch(
        route("clientes.expediente.perfil.update", props.cliente.id),
        formOptions("Perfil economico actualizado"),
    );
};

const openDocumentUpload = (documento = null) => {
    selectedDocument.value = documento;
    documentForm.reset();
    documentForm.clearErrors();
    documentForm.tipo = documento?.tipo ?? "adicional";
    documentForm.nombre = documento?.nombre ?? "";
    documentForm.reemplaza_documento_id = documento?.id ?? null;
    documentDialog.value = true;
};

const onDocumentSelected = (event) => {
    documentForm.archivo = event.files?.[0] ?? null;
};

const uploadDocument = () => {
    documentForm.post(route("clientes.documentos.store", props.cliente.id), {
        ...formOptions("Documento recibido", () => {
            documentDialog.value = false;
            documentForm.reset();
        }),
        forceFormData: true,
    });
};

const openReview = (documento) => {
    selectedDocument.value = documento;
    reviewForm.reset();
    reviewForm.clearErrors();
    reviewDialog.value = true;
};

const updateDocumentStatus = () => {
    reviewForm.patch(
        route("clientes.documentos.estado.update", [
            props.cliente.id,
            selectedDocument.value.id,
        ]),
        formOptions("Estado documental actualizado", () => {
            reviewDialog.value = false;
        }),
    );
};

const openReference = (reference = null) => {
    editingReference.value = reference;
    referenceForm.reset();
    referenceForm.clearErrors();
    if (reference) {
        Object.assign(referenceForm, {
            tipo: reference.tipo,
            nombre: reference.nombre,
            relacion: reference.relacion ?? "",
            empresa: reference.empresa ?? "",
            puesto: reference.puesto ?? "",
            telefono_codigo_pais: reference.telefono_codigo_pais ?? "52",
            telefono: reference.telefono,
            email: reference.email ?? "",
            notas: reference.notas ?? "",
        });
    }
    referenceDialog.value = true;
};

const saveReference = () => {
    const method = editingReference.value ? "put" : "post";
    const url = editingReference.value
        ? route("clientes.referencias.update", [
              props.cliente.id,
              editingReference.value.id,
          ])
        : route("clientes.referencias.store", props.cliente.id);
    referenceForm[method](
        url,
        formOptions("Referencia guardada", () => {
            referenceDialog.value = false;
        }),
    );
};

const saveLink = () => {
    linkForm.post(
        route("clientes.vinculos.store", props.cliente.id),
        formOptions("Persona vinculada", () => {
            linkDialog.value = false;
            linkForm.reset();
        }),
    );
};

const openGuarantee = (guarantee = null) => {
    editingGuarantee.value = guarantee;
    guaranteeForm.reset();
    guaranteeForm.clearErrors();
    guaranteeForm.moneda = props.opciones.moneda;
    if (guarantee) {
        Object.assign(guaranteeForm, {
            propietario_cliente_id: guarantee.propietario_cliente_id,
            tipo: guarantee.tipo,
            descripcion: guarantee.descripcion,
            valor_estimado: guarantee.valor_estimado
                ? Number(guarantee.valor_estimado)
                : null,
            moneda: guarantee.moneda,
            notas: guarantee.notas ?? "",
        });
    }
    guaranteeDialog.value = true;
};

const saveGuarantee = () => {
    const method = editingGuarantee.value ? "put" : "post";
    const url = editingGuarantee.value
        ? route("clientes.garantias.update", [
              props.cliente.id,
              editingGuarantee.value.id,
          ])
        : route("clientes.garantias.store", props.cliente.id);
    guaranteeForm[method](
        url,
        formOptions("Garantia guardada", () => {
            guaranteeDialog.value = false;
        }),
    );
};

const onConsentSelected = (event) => {
    consentForm.evidencia = event.files?.[0] ?? null;
};

const saveConsent = () => {
    consentForm.post(
        route("clientes.consentimientos-sic.store", props.cliente.id),
        {
            ...formOptions("Consentimiento SIC registrado", () => {
                consentDialog.value = false;
                consentForm.reset();
                consentForm.otorgado_en = new Date();
            }),
            forceFormData: true,
        },
    );
};

const confirmDelete = (message, url, success) => {
    confirm.require({
        message,
        header: "Confirmar cambio",
        icon: "pi pi-exclamation-triangle",
        rejectLabel: "Cancelar",
        acceptLabel: "Confirmar",
        acceptClass: "p-button-danger",
        accept: () =>
            router.delete(url, {
                preserveScroll: true,
                onSuccess: () => notify(success),
                onError: (errors) =>
                    toast.add({
                        severity: "error",
                        summary: "No se pudo completar el cambio",
                        detail:
                            Object.values(errors)[0] ??
                            "Revise la información e intente nuevamente.",
                        life: 4500,
                    }),
            }),
    });
};

const revokeConsent = (consent) => {
    confirm.require({
        message:
            "El consentimiento quedara revocado y se conservara su evidencia.",
        header: "Revocar consentimiento",
        icon: "pi pi-ban",
        rejectLabel: "Cancelar",
        acceptLabel: "Revocar",
        acceptClass: "p-button-danger",
        accept: () =>
            router.patch(
                route("clientes.consentimientos-sic.revoke", [
                    props.cliente.id,
                    consent.id,
                ]),
                {},
                {
                    preserveScroll: true,
                    onSuccess: () => notify("Consentimiento revocado"),
                },
            ),
    });
};

function formOptions(message, afterSuccess = null) {
    return {
        preserveScroll: true,
        onSuccess: () => {
            notify(message);
            afterSuccess?.();
        },
    };
}

function notify(detail) {
    toast.add({
        severity: "success",
        summary: "Expediente actualizado",
        detail,
        life: 2800,
    });
}

function download(url) {
    window.location.assign(url);
}

function viewRelatedClient(id) {
    router.visit(route("clientes.expediente.show", id));
}

function optionLabel(options, value) {
    return options.find((option) => option.value === value)?.label ?? value;
}

function statusSeverity(status) {
    return {
        pendiente: "secondary",
        recibido: "info",
        validado: "success",
        rechazado: "danger",
        vencido: "warn",
    }[status];
}

function formatDate(value) {
    if (!value) return "Sin fecha";
    return new Intl.DateTimeFormat("es-MX", { dateStyle: "medium" }).format(
        new Date(value),
    );
}

function formatCurrency(value, currency = props.opciones.moneda) {
    return new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency,
    }).format(Number(value ?? 0));
}
</script>

<template>
    <AppLayout :title="`Expediente de ${nombreCompleto}`">
        <ConfirmDialog />

        <div class="dossier-shell">
            <header class="dossier-header">
                <div class="identity-lockup">
                    <div class="identity-mark">{{ initials }}</div>
                    <div class="min-w-0">
                        <p class="eyebrow">Expediente KYC · Cliente #{{ cliente.id }}</p>
                        <h1>{{ nombreCompleto }}</h1>
                        <div class="identity-meta">
                            <span>{{ cliente.datos_fiscales?.rfc || "RFC pendiente" }}</span>
                            <span>{{ cliente.email }}</span>
                            <span>{{ cliente.telefono }}</span>
                        </div>
                    </div>
                </div>
                <div class="header-actions">
                    <Button
                        label="Datos generales"
                        severity="contrast"
                        outlined
                        @click="router.visit(route('clientes.edit', cliente.id))"
                    >
                        <template #icon><Pencil :size="17" /></template>
                    </Button>
                    <Button
                        label="Historial SIC"
                        severity="secondary"
                        @click="router.visit(route('clientes.historial-crediticio.show', cliente.id))"
                    >
                        <template #icon><ShieldCheck :size="17" /></template>
                    </Button>
                </div>
            </header>

            <div class="dossier-grid">
                <aside class="dossier-rail">
                    <div class="rail-metric">
                        <div class="metric-heading">
                            <span>Perfil económico</span>
                            <strong>{{ profileProgress }}%</strong>
                        </div>
                        <ProgressBar :value="profileProgress" :showValue="false" />
                    </div>
                    <div class="rail-metric">
                        <div class="metric-heading">
                            <span>Documentos recibidos</span>
                            <strong>{{ resumen.documentos_recibidos }}/{{ resumen.documentos_requeridos }}</strong>
                        </div>
                        <ProgressBar :value="documentProgress" :showValue="false" />
                    </div>
                    <div class="rail-stat-grid">
                        <div><span>Validados</span><strong>{{ resumen.documentos_validados }}</strong></div>
                        <div><span>Referencias</span><strong>{{ cliente.referencias.length }}</strong></div>
                        <div><span>Vinculados</span><strong>{{ cliente.vinculos.length }}</strong></div>
                        <div><span>Garantías</span><strong>{{ cliente.garantias.length }}</strong></div>
                    </div>
                    <nav class="rail-nav" aria-label="Secciones del expediente">
                        <button :class="{ active: activeTab === 'perfil' }" @click="activeTab = 'perfil'">
                            <WalletCards :size="18" /> Perfil económico
                        </button>
                        <button :class="{ active: activeTab === 'documentos' }" @click="activeTab = 'documentos'">
                            <FileArchive :size="18" /> Documentos
                        </button>
                        <button :class="{ active: activeTab === 'referencias' }" @click="activeTab = 'referencias'">
                            <UsersRound :size="18" /> Referencias
                        </button>
                        <button :class="{ active: activeTab === 'vinculados' }" @click="activeTab = 'vinculados'">
                            <Link2 :size="18" /> Vinculados
                        </button>
                        <button :class="{ active: activeTab === 'garantias' }" @click="activeTab = 'garantias'">
                            <Landmark :size="18" /> Garantías
                        </button>
                        <button :class="{ active: activeTab === 'consentimientos' }" @click="activeTab = 'consentimientos'">
                            <ShieldCheck :size="18" /> Consentimiento SIC
                        </button>
                    </nav>
                </aside>

                <main class="dossier-content">
                    <section v-show="activeTab === 'perfil'" class="workspace-section">
                        <div class="section-heading">
                            <div><p class="section-kicker">Capacidad declarada</p><h2>Perfil económico</h2></div>
                            <div class="flow-balance" :class="{ negative: declaredFlow < 0 }">
                                <span>Flujo mensual</span><strong>{{ formatCurrency(declaredFlow) }}</strong>
                            </div>
                        </div>
                        <form class="editor-grid" @submit.prevent="saveProfile">
                            <div class="field-span-1">
                                <label for="ocupacion">Ocupación</label>
                                <InputText id="ocupacion" v-model="profileForm.ocupacion" fluid :invalid="!!profileForm.errors.ocupacion" />
                                <Message v-if="profileForm.errors.ocupacion" severity="error" size="small">{{ profileForm.errors.ocupacion }}</Message>
                            </div>
                            <div class="field-span-1">
                                <label for="actividad">Actividad económica</label>
                                <InputText id="actividad" v-model="profileForm.actividad_economica" fluid :invalid="!!profileForm.errors.actividad_economica" />
                                <Message v-if="profileForm.errors.actividad_economica" severity="error" size="small">{{ profileForm.errors.actividad_economica }}</Message>
                            </div>
                            <div class="field-span-1">
                                <label for="ingresos">Ingresos mensuales</label>
                                <InputNumber id="ingresos" v-model="profileForm.ingresos_mensuales" mode="currency" :currency="opciones.moneda" locale="es-MX" fluid :min="0" />
                            </div>
                            <div class="field-span-1">
                                <label for="egresos">Egresos mensuales</label>
                                <InputNumber id="egresos" v-model="profileForm.egresos_mensuales" mode="currency" :currency="opciones.moneda" locale="es-MX" fluid :min="0" />
                            </div>
                            <div class="field-span-2">
                                <label for="origen">Origen de recursos</label>
                                <Textarea id="origen" v-model="profileForm.origen_recursos" rows="5" fluid autoResize />
                            </div>
                            <div class="form-actions field-span-2">
                                <Button type="submit" label="Guardar perfil" :loading="profileForm.processing" :disabled="!can.update">
                                    <template #icon><BadgeCheck :size="18" /></template>
                                </Button>
                            </div>
                        </form>
                    </section>

                    <section v-show="activeTab === 'documentos'" class="workspace-section">
                        <div class="section-heading">
                            <div><p class="section-kicker">Control documental</p><h2>Documentos del cliente</h2></div>
                            <div class="flex flex-wrap gap-2">
                                <Button v-if="can.generate_document" label="Generar documento" @click="openGeneration"><template #icon><FileText :size="18" /></template></Button>
                                <Button label="Documento adicional" outlined @click="openDocumentUpload()" :disabled="!can.update"><template #icon><Plus :size="18" /></template></Button>
                            </div>
                        </div>
                        <div class="document-list">
                            <article v-for="documento in currentDocuments" :key="documento.id" class="document-row">
                                <div class="document-icon" :class="documento.estado"><FileText :size="22" /></div>
                                <div class="document-main">
                                    <div class="document-title-line">
                                        <strong>{{ documento.nombre || optionLabel(opciones.documentos, documento.tipo) }}</strong>
                                        <Tag :value="optionLabel(opciones.estados_documento, documento.estado)" :severity="statusSeverity(documento.estado)" />
                                        <span v-if="documento.version > 1" class="version-label">v{{ documento.version }}</span>
                                    </div>
                                    <p v-if="documento.nombre_original">{{ documento.nombre_original }} · {{ formatDate(documento.recibido_en) }}</p>
                                    <p v-else>Sin archivo recibido</p>
                                    <p v-if="documento.motivo_rechazo" class="rejection-copy">{{ documento.motivo_rechazo }}</p>
                                </div>
                                <div class="row-actions">
                                    <Button v-if="documento.nombre_original" v-tooltip.top="'Ver de forma segura'" text rounded severity="secondary" :aria-label="`Ver ${documento.nombre_original}`" @click="openViewer(route('clientes.documentos.view', [cliente.id, documento.id]), route('clientes.documentos.download', [cliente.id, documento.id]), documento.nombre_original)">
                                        <template #icon><Eye :size="18" /></template>
                                    </Button>
                                    <Button v-if="documento.nombre_original" v-tooltip.top="'Descargar'" text rounded severity="secondary" @click="download(route('clientes.documentos.download', [cliente.id, documento.id]))">
                                        <template #icon><Download :size="18" /></template>
                                    </Button>
                                    <Button v-if="documento.estado === 'recibido' || documento.estado === 'validado'" v-tooltip.top="'Revisar'" text rounded severity="secondary" @click="openReview(documento)" :disabled="!can.update">
                                        <template #icon><FileCheck2 :size="18" /></template>
                                    </Button>
                                    <Button v-tooltip.top="documento.nombre_original ? 'Sustituir' : 'Cargar'" rounded @click="openDocumentUpload(documento)" :disabled="!can.update">
                                        <template #icon><Upload :size="18" /></template>
                                    </Button>
                                </div>
                            </article>
                        </div>
                        <details v-if="documentHistory.length" class="history-block">
                            <summary><FileClock :size="17" /> Historial de versiones ({{ documentHistory.length }})</summary>
                            <div v-for="documento in documentHistory" :key="documento.id" class="history-row">
                                <span>{{ optionLabel(opciones.documentos, documento.tipo) }} · v{{ documento.version }}</span>
                                <Tag :value="documento.estado" :severity="statusSeverity(documento.estado)" />
                                <div class="flex gap-1"><Button text size="small" label="Ver" @click="openViewer(route('clientes.documentos.view', [cliente.id, documento.id]), route('clientes.documentos.download', [cliente.id, documento.id]), documento.nombre_original)" /><Button text size="small" label="Descargar" @click="download(route('clientes.documentos.download', [cliente.id, documento.id]))" /></div>
                            </div>
                        </details>
                        <div class="mt-6 border-t border-surface-200 pt-5">
                            <div class="mb-3 flex items-center justify-between gap-3"><div><p class="section-kicker">Generados por Kronik</p><h3 class="text-lg font-semibold">Documentos finales</h3></div><Tag :value="`${cliente.documentos_generados?.length ?? 0} registros`" severity="secondary" /></div>
                            <div v-if="cliente.documentos_generados?.length" class="document-list">
                                <article v-for="generated in cliente.documentos_generados" :key="generated.id" class="document-row">
                                    <div class="document-icon" :class="generated.estado"><FileText :size="22" /></div>
                                    <div class="document-main"><div class="document-title-line"><strong>{{ generated.version?.plantilla?.nombre }}</strong><DocumentVersionStatus :status="generated.estado" /><span class="version-label">v{{ generated.version?.numero }}</span></div><p>{{ generated.nombre_archivo || 'Archivo en preparación' }} · {{ formatDate(generated.solicitado_en) }}</p><p v-if="generated.error_mensaje" class="rejection-copy">{{ generated.error_mensaje }}</p></div>
                                    <div v-if="generated.estado === 'generado'" class="row-actions"><Button v-if="can.read_documents" v-tooltip.top="'Ver documento final'" text rounded :aria-label="`Ver ${generated.nombre_archivo}`" @click="openViewer(route('documentos-generados.view', generated.id), can.download_documents ? route('documentos-generados.download', generated.id) : '', generated.nombre_archivo)"><template #icon><Eye :size="18" /></template></Button><Button v-if="can.download_documents" v-tooltip.top="'Descargar'" text rounded @click="download(route('documentos-generados.download', generated.id))"><template #icon><Download :size="18" /></template></Button></div>
                                </article>
                            </div>
                            <div v-else class="empty-state"><FileText :size="30" /><strong>Aún no hay documentos generados</strong><span class="text-sm text-surface-500">Los archivos cargados arriba se conservan sin cambios.</span></div>
                        </div>
                    </section>

                    <section v-show="activeTab === 'referencias'" class="workspace-section">
                        <div class="section-heading">
                            <div><p class="section-kicker">Contactos verificables</p><h2>Referencias</h2></div>
                            <Button label="Agregar referencia" @click="openReference()" :disabled="!can.update"><template #icon><Plus :size="18" /></template></Button>
                        </div>
                        <div v-if="cliente.referencias.length" class="records-table">
                            <div v-for="reference in cliente.referencias" :key="reference.id" class="record-row">
                                <div class="record-symbol"><UserRound :size="20" /></div>
                                <div><strong>{{ reference.nombre }}</strong><p>{{ optionLabel(opciones.referencias, reference.tipo) }} · {{ reference.relacion || reference.empresa }}</p></div>
                                <div class="record-contact"><span v-if="reference.telefono_codigo_pais">+{{ String(reference.telefono_codigo_pais).replace(/^\+/, "") }} </span><span>{{ reference.telefono }}</span><small>{{ reference.email }}</small></div>
                                <div class="row-actions">
                                    <Button v-if="can.update" text rounded @click="openReference(reference)"><template #icon><Pencil :size="17" /></template></Button>
                                    <Button v-if="can.update" text rounded severity="danger" @click="confirmDelete('Se eliminara esta referencia.', route('clientes.referencias.destroy', [cliente.id, reference.id]), 'Referencia eliminada')"><template #icon><Trash2 :size="17" /></template></Button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="empty-state"><UsersRound :size="30" /><strong>Sin referencias</strong></div>
                    </section>

                    <section v-show="activeTab === 'vinculados'" class="workspace-section">
                        <div class="section-heading">
                            <div><p class="section-kicker">Responsabilidad compartida</p><h2>Avales y obligados solidarios</h2></div>
                            <Button label="Vincular cliente" @click="linkDialog = true" :disabled="!can.update"><template #icon><Link2 :size="18" /></template></Button>
                        </div>
                        <div v-if="relaciones.length" class="records-table">
                            <div v-for="link in relaciones" :key="`${link.direccion}-${link.id}`" class="record-row">
                                <div class="record-symbol coral"><Link2 :size="20" /></div>
                                <div>
                                    <button type="button" class="related-client-link" @click="viewRelatedClient(link.cliente.id)">{{ [link.cliente.primer_nombre, link.cliente.segundo_nombre, link.cliente.apellido_paterno, link.cliente.apellido_materno].filter(Boolean).join(' ') }}</button>
                                    <p>{{ link.cliente.datos_fiscales?.rfc || 'RFC pendiente' }} · {{ link.direccion === 'saliente' ? 'Vinculado desde este expediente' : 'Este cliente está vinculado desde el expediente relacionado' }}</p>
                                </div>
                                <Tag :value="optionLabel(opciones.vinculos, link.rol)" :severity="link.direccion === 'saliente' ? 'contrast' : 'info'" />
                                <div class="row-actions">
                                    <Button text rounded v-tooltip.top="'Ver expediente'" @click="viewRelatedClient(link.cliente.id)"><template #icon><UserRound :size="17" /></template></Button>
                                    <Button v-if="can.update && link.puede_eliminar" text rounded severity="danger" @click="confirmDelete('Se retirara este vinculo del expediente.', route('clientes.vinculos.destroy', [cliente.id, link.id]), 'Vinculo eliminado')"><template #icon><Trash2 :size="17" /></template></Button>
                                </div>
                            </div>
                        </div>
                        <div v-else class="empty-state"><Link2 :size="30" /><strong>Sin personas vinculadas</strong></div>
                    </section>

                    <section v-show="activeTab === 'garantias'" class="workspace-section">
                        <div class="section-heading">
                            <div><p class="section-kicker">Respaldo patrimonial</p><h2>Garantías</h2></div>
                            <Button label="Registrar garantía" @click="openGuarantee()" :disabled="!can.update"><template #icon><Plus :size="18" /></template></Button>
                        </div>
                        <div v-if="cliente.garantias.length" class="guarantee-grid">
                            <article v-for="guarantee in cliente.garantias" :key="guarantee.id" class="guarantee-item">
                                <div class="guarantee-top"><Landmark :size="22" /><Tag :value="optionLabel(opciones.garantias, guarantee.tipo)" severity="warn" /></div>
                                <h3>{{ guarantee.descripcion }}</h3>
                                <strong class="guarantee-value">{{ formatCurrency(guarantee.valor_estimado, guarantee.moneda) }}</strong>
                                <p>Propietario: {{ guarantee.propietario ? [guarantee.propietario.primer_nombre, guarantee.propietario.apellido_paterno].filter(Boolean).join(' ') : nombreCompleto }}</p>
                                <div class="guarantee-actions">
                                    <Button v-if="can.update" text label="Editar" @click="openGuarantee(guarantee)"><template #icon><Pencil :size="16" /></template></Button>
                                    <Button v-if="can.update" text label="Eliminar" severity="danger" @click="confirmDelete('Se eliminara esta garantia.', route('clientes.garantias.destroy', [cliente.id, guarantee.id]), 'Garantia eliminada')"><template #icon><Trash2 :size="16" /></template></Button>
                                </div>
                            </article>
                        </div>
                        <div v-else class="empty-state"><Landmark :size="30" /><strong>Sin garantías registradas</strong></div>
                    </section>

                    <section v-show="activeTab === 'consentimientos'" class="workspace-section">
                        <div class="section-heading">
                            <div><p class="section-kicker">Trazabilidad de autorización</p><h2>Consentimientos SIC</h2></div>
                            <Button label="Registrar consentimiento" @click="consentDialog = true" :disabled="!can.update"><template #icon><ShieldCheck :size="18" /></template></Button>
                        </div>
                        <div v-if="cliente.consentimientos_sic.length" class="consent-timeline">
                            <article v-for="consent in cliente.consentimientos_sic" :key="consent.id" class="consent-row">
                                <div class="timeline-dot" :class="{ revoked: consent.revocado_en }"><ShieldCheck :size="18" /></div>
                                <div class="consent-copy"><strong>{{ consent.revocado_en ? 'Consentimiento revocado' : 'Consentimiento registrado' }}</strong><p>{{ optionLabel(opciones.medios_consentimiento, consent.medio) }} · {{ formatDate(consent.otorgado_en) }} · {{ consent.registrador.name }}</p><small v-if="consent.vence_en">Vigencia declarada hasta {{ formatDate(consent.vence_en) }}</small></div>
                                <div class="row-actions">
                                    <Button text rounded v-tooltip.top="'Ver evidencia segura'" @click="openViewer(route('clientes.consentimientos-sic.view', [cliente.id, consent.id]), route('clientes.consentimientos-sic.download', [cliente.id, consent.id]), consent.evidencia_nombre_original)"><template #icon><Eye :size="18" /></template></Button>
                                    <Button text rounded v-tooltip.top="'Descargar evidencia'" @click="download(route('clientes.consentimientos-sic.download', [cliente.id, consent.id]))"><template #icon><Download :size="18" /></template></Button>
                                    <Button v-if="can.update && !consent.revocado_en" text rounded severity="danger" v-tooltip.top="'Revocar'" @click="revokeConsent(consent)"><template #icon><Trash2 :size="18" /></template></Button>
                                </div>
                            </article>
                        </div>
                        <div v-else class="empty-state"><ShieldCheck :size="30" /><strong>Sin consentimiento SIC</strong></div>
                    </section>
                </main>
            </div>
        </div>

        <Dialog v-model:visible="documentDialog" modal :header="selectedDocument?.nombre_original ? 'Sustituir documento' : 'Recibir documento'" class="responsive-dialog">
            <form class="dialog-form" @submit.prevent="uploadDocument">
                <div><label>Tipo</label><Select v-model="documentForm.tipo" :options="opciones.documentos" optionLabel="label" optionValue="value" fluid :disabled="selectedDocument !== null" /></div>
                <div v-if="documentForm.tipo === 'adicional'"><label>Nombre del documento</label><InputText v-model="documentForm.nombre" fluid /></div>
                <div><label>Fecha de vencimiento</label><DatePicker v-model="documentForm.vence_en" dateFormat="dd-mm-yy" showIcon fluid /></div>
                <div><label>Archivo privado</label><FileUpload mode="basic" customUpload accept=".pdf,.jpg,.jpeg,.png" :maxFileSize="10485760" chooseLabel="Seleccionar PDF o imagen" @select="onDocumentSelected" /></div>
                <div><label>Notas</label><Textarea v-model="documentForm.notas" rows="3" fluid /></div>
                <Message v-for="error in documentForm.errors" :key="error" severity="error" size="small">{{ error }}</Message>
                <div class="dialog-actions"><Button type="button" label="Cancelar" text @click="documentDialog = false" /><Button type="submit" label="Guardar archivo" :loading="documentForm.processing"><template #icon><Upload :size="17" /></template></Button></div>
            </form>
        </Dialog>

        <Dialog v-model:visible="generationDialog" modal header="Generar documento final" class="responsive-dialog">
            <form class="dialog-form" @submit.prevent="generateDocument">
                <Message severity="info" :closable="false">El PDF usará la versión activa exacta y una copia cifrada de los valores utilizados. No representa una firma.</Message>
                <fieldset>
                    <legend class="mb-1 text-sm font-medium">Plantilla activa</legend>
                    <div class="max-h-48 space-y-2 overflow-auto rounded-xl border border-surface-200 p-2 dark:border-surface-700">
                        <label v-for="option in generationOptions" :key="option.value" :for="`generation-template-${option.value}`" class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition" :class="generationForm.version_id === option.value ? 'border-primary bg-primary-50 dark:bg-primary-950/30' : 'border-transparent hover:bg-surface-50 dark:hover:bg-surface-800'">
                            <RadioButton v-model="generationForm.version_id" :input-id="`generation-template-${option.value}`" name="generation-template" :value="option.value" />
                            <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ option.label }}</span>
                        </label>
                        <p v-if="!generationOptions.length" class="p-3 text-sm text-surface-500">No hay plantillas activas</p>
                    </div>
                    <Message v-if="generationForm.errors.version_id" severity="error" size="small">{{ generationForm.errors.version_id }}</Message>
                </fieldset>
                <fieldset v-if="selectedGeneration?.tipo === 'garantia'">
                    <legend class="mb-1 text-sm font-medium">Garantía</legend>
                    <div class="max-h-48 space-y-2 overflow-auto rounded-xl border border-surface-200 p-2 dark:border-surface-700">
                        <label v-for="guarantee in cliente.garantias" :key="guarantee.id" :for="`generation-guarantee-${guarantee.id}`" class="flex cursor-pointer items-center gap-3 rounded-lg border p-3 transition" :class="generationForm.garantia_id === guarantee.id ? 'border-primary bg-primary-50 dark:bg-primary-950/30' : 'border-transparent hover:bg-surface-50 dark:hover:bg-surface-800'">
                            <RadioButton v-model="generationForm.garantia_id" :input-id="`generation-guarantee-${guarantee.id}`" name="generation-guarantee" :value="guarantee.id" />
                            <span class="min-w-0 flex-1 truncate text-sm font-medium">{{ guarantee.descripcion }}</span>
                        </label>
                        <p v-if="!cliente.garantias.length" class="p-3 text-sm text-surface-500">No hay garantías disponibles</p>
                    </div>
                    <Message v-if="generationForm.errors.garantia_id" severity="error" size="small">{{ generationForm.errors.garantia_id }}</Message>
                </fieldset>
                <Message v-if="!generationOptions.length" severity="warn" :closable="false">No hay versiones activas disponibles. Activa una plantilla desde el Centro documental.</Message>
                <div class="dialog-actions"><Button type="button" label="Cancelar" text @click="generationDialog = false" /><Button type="submit" label="Generar PDF" icon="pi pi-file-pdf" :loading="generationForm.processing" :disabled="!generationOptions.length || !generationForm.version_id || (selectedGeneration?.tipo === 'garantia' && !generationForm.garantia_id)" /></div>
            </form>
        </Dialog>

        <PrivateDocumentViewer v-model:visible="viewerVisible" :url="viewer.url" :download-url="viewer.downloadUrl" :name="viewer.name" />

        <Dialog v-model:visible="reviewDialog" modal header="Revisión documental" class="responsive-dialog narrow-dialog">
            <form class="dialog-form" @submit.prevent="updateDocumentStatus">
                <SelectButton v-model="reviewForm.estado" :options="[{ label: 'Validar', value: 'validado' }, { label: 'Rechazar', value: 'rechazado' }, { label: 'Vencido', value: 'vencido' }]" optionLabel="label" optionValue="value" :allowEmpty="false" />
                <div v-if="reviewForm.estado === 'rechazado'"><label>Motivo de rechazo</label><Textarea v-model="reviewForm.motivo_rechazo" rows="4" fluid /></div>
                <Message v-for="error in reviewForm.errors" :key="error" severity="error" size="small">{{ error }}</Message>
                <div class="dialog-actions"><Button type="button" label="Cancelar" text @click="reviewDialog = false" /><Button type="submit" label="Aplicar estado" :loading="reviewForm.processing" /></div>
            </form>
        </Dialog>

        <Dialog v-model:visible="referenceDialog" modal :header="editingReference ? 'Editar referencia' : 'Nueva referencia'" class="responsive-dialog">
            <form class="dialog-form two-columns" @submit.prevent="saveReference">
                <div><label>Tipo</label><SelectButton v-model="referenceForm.tipo" :options="opciones.referencias" optionLabel="label" optionValue="value" :allowEmpty="false" /></div>
                <div><label>Nombre</label><InputText v-model="referenceForm.nombre" fluid /></div>
                <div v-if="referenceForm.tipo === 'personal'"><label>Relación</label><InputText v-model="referenceForm.relacion" fluid /></div>
                <template v-else><div><label>Empresa</label><InputText v-model="referenceForm.empresa" fluid /></div><div><label>Puesto</label><InputText v-model="referenceForm.puesto" fluid /></div></template>
                <div><label>Teléfono</label><InputText v-model="referenceForm.telefono" fluid /></div>
                <div><label>Email</label><InputText v-model="referenceForm.email" fluid /></div>
                <div class="full-field"><label>Notas</label><Textarea v-model="referenceForm.notas" rows="3" fluid /></div>
                <Message v-for="error in referenceForm.errors" :key="error" severity="error" size="small" class="full-field">{{ error }}</Message>
                <div class="dialog-actions full-field"><Button type="button" label="Cancelar" text @click="referenceDialog = false" /><Button type="submit" label="Guardar referencia" :loading="referenceForm.processing" /></div>
            </form>
        </Dialog>

        <Dialog v-model:visible="linkDialog" modal header="Vincular cliente" class="responsive-dialog narrow-dialog">
            <form class="dialog-form" @submit.prevent="saveLink">
                <div><label>Cliente</label><Select v-model="linkForm.cliente_vinculado_id" :options="candidateOptions" optionLabel="label" optionValue="id" filter fluid><template #option="{ option }"><div><strong>{{ option.label }}</strong><small class="block">{{ option.rfc }}</small></div></template></Select></div>
                <div><label>Responsabilidad</label><SelectButton v-model="linkForm.rol" :options="opciones.vinculos" optionLabel="label" optionValue="value" :allowEmpty="false" /></div>
                <div><label>Notas</label><Textarea v-model="linkForm.notas" rows="3" fluid /></div>
                <Message v-for="error in linkForm.errors" :key="error" severity="error" size="small">{{ error }}</Message>
                <div class="dialog-actions"><Button type="button" label="Cancelar" text @click="linkDialog = false" /><Button type="submit" label="Vincular" :loading="linkForm.processing" /></div>
            </form>
        </Dialog>

        <Dialog v-model:visible="guaranteeDialog" modal :header="editingGuarantee ? 'Editar garantía' : 'Registrar garantía'" class="responsive-dialog">
            <form class="dialog-form two-columns" @submit.prevent="saveGuarantee">
                <div><label>Tipo</label><Select v-model="guaranteeForm.tipo" :options="opciones.garantias" optionLabel="label" optionValue="value" fluid /></div>
                <div><label>Propietario</label><Select v-model="guaranteeForm.propietario_cliente_id" :options="relatedClientOptions" optionLabel="label" optionValue="id" showClear filter placeholder="El cliente titular" fluid /></div>
                <div class="full-field"><label>Descripción</label><InputText v-model="guaranteeForm.descripcion" fluid /></div>
                <div><label>Valor estimado</label><InputNumber v-model="guaranteeForm.valor_estimado" mode="currency" :currency="guaranteeForm.moneda" locale="es-MX" :min="0" fluid /></div>
                <div><label>Moneda</label><InputText v-model="guaranteeForm.moneda" maxlength="3" fluid /></div>
                <div class="full-field"><label>Notas</label><Textarea v-model="guaranteeForm.notas" rows="3" fluid /></div>
                <Message v-for="error in guaranteeForm.errors" :key="error" severity="error" size="small" class="full-field">{{ error }}</Message>
                <div class="dialog-actions full-field"><Button type="button" label="Cancelar" text @click="guaranteeDialog = false" /><Button type="submit" label="Guardar garantía" :loading="guaranteeForm.processing" /></div>
            </form>
        </Dialog>

        <Dialog v-model:visible="consentDialog" modal header="Registrar consentimiento SIC" class="responsive-dialog">
            <form class="dialog-form two-columns" @submit.prevent="saveConsent">
                <div><label>Medio</label><Select v-model="consentForm.medio" :options="opciones.medios_consentimiento" optionLabel="label" optionValue="value" fluid /></div>
                <div><label>Fecha y hora</label><DatePicker v-model="consentForm.otorgado_en" dateFormat="dd-mm-yy" showTime hourFormat="24" showIcon fluid /></div>
                <div><label>Vigencia declarada</label><DatePicker v-model="consentForm.vence_en" dateFormat="dd-mm-yy" showIcon fluid /></div>
                <div><label>Evidencia privada</label><FileUpload mode="basic" customUpload accept=".pdf,.jpg,.jpeg,.png" :maxFileSize="10485760" chooseLabel="Seleccionar evidencia" @select="onConsentSelected" /></div>
                <div class="full-field"><label>Notas</label><Textarea v-model="consentForm.notas" rows="3" fluid /></div>
                <Message v-for="error in consentForm.errors" :key="error" severity="error" size="small" class="full-field">{{ error }}</Message>
                <div class="dialog-actions full-field"><Button type="button" label="Cancelar" text @click="consentDialog = false" /><Button type="submit" label="Registrar consentimiento" :loading="consentForm.processing"><template #icon><ShieldCheck :size="17" /></template></Button></div>
            </form>
        </Dialog>
    </AppLayout>
</template>

<style scoped>
.dossier-shell { width: 100%; max-width: 100%; overflow: hidden; background: #f5f5f2; min-height: calc(100vh - 9rem); color: #20201e; }
.dossier-header { background: #191917; color: #fff; padding: 1.75rem clamp(1rem, 3vw, 2.75rem); display: flex; align-items: center; justify-content: space-between; gap: 1.5rem; border-bottom: 4px solid #ee6c4d; }
.identity-lockup { display: flex; align-items: center; gap: 1rem; min-width: 0; }
.identity-mark { width: 3.75rem; height: 3.75rem; flex: 0 0 3.75rem; display: grid; place-items: center; background: #f2cc8f; color: #191917; font-size: 1.15rem; font-weight: 800; border-radius: 6px; }
.eyebrow, .section-kicker { text-transform: uppercase; font-size: .72rem; font-weight: 800; letter-spacing: .08em; color: #ee6c4d; margin: 0 0 .3rem; }
.dossier-header h1 { margin: 0; font-size: clamp(1.45rem, 2vw, 2.15rem); line-height: 1.15; letter-spacing: 0; }
.identity-meta { display: flex; flex-wrap: wrap; gap: .35rem 1rem; color: #c8c8c2; font-size: .82rem; margin-top: .55rem; }
.header-actions { display: flex; gap: .65rem; flex-wrap: wrap; justify-content: flex-end; }
.dossier-header :deep(.p-button.p-button-outlined) { color: #fff; border-color: #71716b; }
.dossier-header :deep(.p-button.p-button-outlined:hover) { background: #30302d; border-color: #fff; }
.dossier-grid { display: grid; grid-template-columns: minmax(235px, 285px) minmax(0, 1fr); width: 100%; max-width: 1500px; margin: 0 auto; }
.dossier-rail { min-width: 0; padding: 1.5rem 1.25rem; border-right: 1px solid #d9d9d3; background: #ecece7; min-height: calc(100vh - 14rem); }
.rail-metric { padding: .9rem 0; border-bottom: 1px solid #d1d1ca; }
.metric-heading { display: flex; justify-content: space-between; gap: 1rem; align-items: baseline; margin-bottom: .55rem; font-size: .78rem; }
.metric-heading strong { font-size: 1rem; }
.rail-stat-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1px; background: #d1d1ca; margin: 1rem 0 1.4rem; border: 1px solid #d1d1ca; }
.rail-stat-grid > div { background: #f5f5f2; padding: .75rem; display: flex; flex-direction: column; }
.rail-stat-grid span { font-size: .68rem; color: #686862; }
.rail-stat-grid strong { font-size: 1.25rem; }
.rail-nav { display: grid; gap: .25rem; }
.rail-nav button { border: 0; background: transparent; color: #464641; display: flex; align-items: center; gap: .7rem; width: 100%; min-height: 2.75rem; padding: .65rem .75rem; border-left: 3px solid transparent; font-weight: 650; text-align: left; cursor: pointer; }
.rail-nav button:hover { background: #e2e2dc; }
.rail-nav button.active { background: #fff; color: #171715; border-left-color: #ee6c4d; }
.dossier-content { min-width: 0; background: #fff; }
.workspace-section { padding: clamp(1.25rem, 3vw, 2.75rem); min-height: 650px; }
.section-heading { display: flex; justify-content: space-between; align-items: flex-end; gap: 1rem; padding-bottom: 1.25rem; border-bottom: 2px solid #242421; margin-bottom: 1.6rem; }
.section-heading h2 { font-size: clamp(1.35rem, 2vw, 1.85rem); margin: 0; letter-spacing: 0; }
.flow-balance { text-align: right; padding-left: 1.25rem; border-left: 3px solid #3d9970; }
.flow-balance.negative { border-left-color: #d1495b; }
.flow-balance span { display: block; color: #6b6b65; font-size: .72rem; }
.flow-balance strong { font-size: 1.25rem; }
.editor-grid, .two-columns { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.25rem; }
.editor-grid label, .dialog-form label { display: block; font-size: .78rem; font-weight: 700; margin-bottom: .42rem; color: #494944; }
.field-span-2, .full-field { grid-column: 1 / -1; }
.form-actions, .dialog-actions { display: flex; justify-content: flex-end; gap: .6rem; padding-top: .5rem; }
.document-list, .records-table, .consent-timeline { border-top: 1px solid #deded8; }
.document-row, .record-row, .consent-row { display: grid; align-items: center; gap: 1rem; border-bottom: 1px solid #deded8; padding: 1rem .25rem; }
.document-row { grid-template-columns: 44px minmax(0, 1fr) auto; }
.document-icon, .record-symbol, .timeline-dot { width: 42px; height: 42px; display: grid; place-items: center; background: #e9e9e3; color: #60605a; border-radius: 5px; }
.document-icon.validado { background: #d9eee2; color: #26734d; }
.document-icon.rechazado, .timeline-dot.revoked { background: #f7dfe3; color: #a8273b; }
.document-icon.recibido { background: #dcebf3; color: #24627f; }
.document-icon.vencido { background: #f6ead5; color: #8a5d17; }
.document-title-line { display: flex; align-items: center; gap: .6rem; flex-wrap: wrap; }
.document-main p, .record-row p, .guarantee-item p, .consent-copy p { margin: .3rem 0 0; color: #6b6b65; font-size: .8rem; }
.rejection-copy { color: #a8273b !important; }
.version-label { font-size: .7rem; color: #777770; font-weight: 700; }
.row-actions { display: flex; align-items: center; justify-content: flex-end; gap: .15rem; }
.history-block { margin-top: 1.2rem; border: 1px solid #deded8; }
.history-block summary { padding: .85rem 1rem; display: flex; align-items: center; gap: .5rem; cursor: pointer; font-weight: 700; }
.history-row { display: grid; grid-template-columns: minmax(0, 1fr) auto auto; gap: .75rem; align-items: center; padding: .7rem 1rem; border-top: 1px solid #e8e8e3; font-size: .8rem; }
.record-row { grid-template-columns: 44px minmax(0, 1.2fr) minmax(150px, .8fr) auto; }
.record-symbol.coral { background: #fde4dd; color: #a53d25; }
.record-contact { display: flex; flex-direction: column; font-size: .8rem; }
.record-contact small { color: #74746e; }
.related-client-link { border: 0; padding: 0; background: transparent; color: #1f5f8b; font-weight: 750; text-align: left; cursor: pointer; }
.related-client-link:hover { text-decoration: underline; }
.guarantee-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 1px; background: #deded8; border: 1px solid #deded8; }
.guarantee-item { background: #fff; padding: 1.15rem; min-height: 205px; display: flex; flex-direction: column; }
.guarantee-top { display: flex; justify-content: space-between; align-items: center; color: #7a5420; }
.guarantee-item h3 { font-size: 1rem; margin: 1.15rem 0 .4rem; }
.guarantee-value { font-size: 1.45rem; }
.guarantee-actions { display: flex; justify-content: flex-end; margin-top: auto; }
.consent-row { grid-template-columns: 44px minmax(0, 1fr) auto; position: relative; }
.timeline-dot { background: #d9eee2; color: #26734d; }
.consent-copy small { color: #777770; }
.empty-state { min-height: 260px; display: grid; place-content: center; justify-items: center; gap: .75rem; color: #777770; border: 1px dashed #cecec6; }
.dialog-form { display: grid; gap: 1rem; padding-top: .4rem; }
:deep(.responsive-dialog) { width: min(680px, calc(100vw - 2rem)); }
:deep(.narrow-dialog) { width: min(520px, calc(100vw - 2rem)); }
:deep(.p-progressbar) { height: .42rem; border-radius: 0; background: #d7d7d0; }
:deep(.p-progressbar-value) { background: #3d9970; }

@media (max-width: 900px) {
    .dossier-header { align-items: flex-start; flex-direction: column; }
    .header-actions { width: 100%; justify-content: flex-start; }
    .dossier-grid { grid-template-columns: minmax(0, 1fr); }
    .dossier-rail { overflow: hidden; min-height: auto; border-right: 0; border-bottom: 1px solid #d9d9d3; padding-bottom: .7rem; }
    .rail-stat-grid { grid-template-columns: repeat(4, minmax(0, 1fr)); }
    .rail-nav { display: flex; max-width: 100%; overflow-x: auto; padding-bottom: .3rem; }
    .rail-nav button { min-width: max-content; border-left: 0; border-bottom: 3px solid transparent; }
    .rail-nav button.active { border-left: 0; border-bottom-color: #ee6c4d; }
    .workspace-section { min-height: 540px; }
}

@media (max-width: 640px) {
    .identity-mark { width: 3rem; height: 3rem; flex-basis: 3rem; }
    .identity-meta { flex-direction: column; gap: .2rem; }
    .header-actions :deep(.p-button) { flex: 1 1 auto; }
    .rail-stat-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
    .section-heading { align-items: flex-start; flex-direction: column; }
    .section-heading > :deep(.p-button) { width: 100%; }
    .flow-balance { text-align: left; }
    .editor-grid, .two-columns { grid-template-columns: 1fr; }
    .field-span-2, .full-field { grid-column: auto; }
    .document-row { grid-template-columns: 40px minmax(0, 1fr); }
    .document-row .row-actions { grid-column: 1 / -1; justify-content: flex-end; }
    .record-row { grid-template-columns: 40px minmax(0, 1fr) auto; }
    .record-contact { grid-column: 2; }
    .record-row .row-actions { grid-column: 3; grid-row: 1 / span 2; }
    .consent-row { grid-template-columns: 40px minmax(0, 1fr); }
    .consent-row .row-actions { grid-column: 2; justify-content: flex-start; }
}
</style>
