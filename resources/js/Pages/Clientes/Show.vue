<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import PageHeader from "@/Components/PageHeader.vue";
import { computed } from "vue";
import FormCliente from "./Partials/FormCliente.vue";

const page = usePage();
const nombreCompleto = computed(() =>
    [
        page.props.cliente.primer_nombre,
        page.props.cliente.segundo_nombre,
        page.props.cliente.apellido_paterno,
        page.props.cliente.apellido_materno,
    ]
        .filter(Boolean)
        .join(" "),
);
</script>

<template>
    <AppLayout title="Informacion del cliente">
        <template #card-header>
            <PageHeader eyebrow="Operación · Clientes" :title="nombreCompleto" description="Información y documentos del cliente." />
        </template>
        <template #card-content>
            <FormCliente />
            <section v-if="page.props.communicationConsents !== null" class="mt-6 rounded-lg border border-surface-200 p-4 dark:border-surface-700" aria-labelledby="comunicaciones-cliente">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h2 id="comunicaciones-cliente" class="text-lg font-semibold">Comunicaciones</h2>
                        <p class="text-sm text-surface-500">Autorizaciones registradas para este cliente y el equipo activo. Los datos de contacto se muestran arriba; su presencia no acredita consentimiento ni verificación.</p>
                    </div>
                    <Link v-if="page.props.canManageCommunications" :href="route('comunicaciones.index', { tab: 'consents', cliente_id: page.props.cliente.id })" class="rounded bg-primary-600 px-3 py-2 text-sm text-white">Gestionar consentimientos</Link>
                </div>
                <ul v-if="page.props.communicationConsents.length" class="mt-3 divide-y divide-surface-200 dark:divide-surface-700">
                    <li v-for="consent in page.props.communicationConsents" :key="consent.id" class="flex flex-wrap justify-between gap-2 py-2 text-sm">
                        <span>{{ { email: 'Correo', sms: 'SMS', whatsapp: 'WhatsApp' }[consent.channel] }} · {{ consent.purpose === 'operational' ? 'Operación' : 'Recordatorio preventivo' }}</span>
                        <span>{{ { revoked: 'Revocado', current: 'Vigente', contact_changed: 'Contacto cambiado; requiere nueva autorización' }[consent.effective_status] }} · {{ new Date(consent.granted_at).toLocaleDateString('es-MX') }}</span>
                    </li>
                </ul>
                <p v-else class="mt-3 text-sm">No hay consentimientos registrados para este equipo.</p>
            </section>
        </template>
    </AppLayout>
</template>
