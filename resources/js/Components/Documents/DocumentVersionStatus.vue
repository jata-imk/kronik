<script setup>
import { computed } from "vue";

const props = defineProps({ status: { type: String, required: true } });
const meta = computed(
    () =>
        ({
            borrador: {
                label: "Borrador",
                severity: "secondary",
                icon: "pi pi-pencil",
            },
            activa: {
                label: "Activa",
                severity: "success",
                icon: "pi pi-check-circle",
            },
            retirada: {
                label: "Retirada",
                severity: "contrast",
                icon: "pi pi-lock",
            },
            pendiente: {
                label: "Pendiente",
                severity: "warn",
                icon: "pi pi-clock",
            },
            procesando: {
                label: "Procesando",
                severity: "info",
                icon: "pi pi-spin pi-spinner",
            },
            generado: {
                label: "Generado",
                severity: "success",
                icon: "pi pi-file-pdf",
            },
            fallido: {
                label: "Fallido",
                severity: "danger",
                icon: "pi pi-exclamation-triangle",
            },
        })[props.status] ?? {
            label: props.status,
            severity: "secondary",
            icon: "pi pi-circle",
        },
);
</script>

<template>
    <Tag :severity="meta.severity" rounded>
        <span :class="meta.icon" aria-hidden="true" />
        <span>{{ meta.label }}</span>
    </Tag>
</template>
