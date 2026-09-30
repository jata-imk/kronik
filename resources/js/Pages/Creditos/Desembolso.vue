<script setup>
import { Link, useForm } from "@inertiajs/vue3";
import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import { computed, ref } from "vue";
import { formatMoneyWithCents as money } from "@/Pages/ProductosCrediticios/productValidation";
const props = defineProps({ solicitud: Object, preparacion: Object, puedeRegistrar: Boolean });
const drawer = ref(false);
const pendientes = computed(() => props.preparacion.requisitos.filter(r => !r.cumplido));
const resumen = computed(() => props.preparacion.resumen);
const form = useForm({ fecha_desembolso: resumen.value?.fecha ?? "", importe: resumen.value?.importe ?? "", referencia: "", confirmacion_qa: false, idempotency_key: crypto.randomUUID(), lock_version: props.solicitud.lock_version });
function registrar() {
    form.post(route("solicitudes.desembolso.store", props.solicitud.id), { preserveScroll: true });
}
</script>

<template>
    <AppLayout title="Registrar desembolso QA">
        <template #card-content>
            <div class="space-y-6 p-4 md:p-6">
                <nav class="flex flex-wrap gap-4 text-sm" aria-label="Contexto del desembolso">
                    <Link :href="route('solicitudes.show', solicitud.id)" class="text-primary hover:underline"><i class="pi pi-arrow-left mr-2" />Solicitud SOL-{{ solicitud.id }}</Link>
                    <Link :href="route('solicitudes.paquete.show', solicitud.id)" class="text-primary hover:underline"><i class="pi pi-file-pdf mr-2" />Contrato y tabla de pagos</Link>
                </nav>
                <header><p class="text-xs font-semibold uppercase tracking-widest text-primary">Operación crediticia</p><h1 class="mt-2 text-2xl font-bold">Registrar desembolso</h1><p class="mt-2 text-surface-500">{{ solicitud.cliente.primer_nombre }} {{ solicitud.cliente.apellido_paterno }} · {{ solicitud.sucursal.nombre }}</p></header>
                <section v-if="resumen" class="rounded-2xl bg-gradient-to-br from-slate-950 to-violet-700 p-6 text-white" aria-label="Importes firmados">
                    <Tag value="Transferencia manual · solo QA" severity="warn" />
                    <div class="mt-5 grid gap-4 sm:grid-cols-3">
                        <div><p class="text-white/80">Efectivo a entregar</p><p class="mt-2 text-3xl font-bold">{{ money(resumen.importe) }}</p><p class="mt-2 text-sm text-white/80">Importe exacto de la transferencia</p></div>
                        <div class="rounded-xl bg-white/10 p-4"><p>Capital financiado inicial</p><p class="mt-2 text-xl font-semibold">{{ money(resumen.capital) }}</p><p class="mt-2 text-sm">Incluye cargos financiados: {{ money(resumen.financiado) }}</p></div>
                        <div class="rounded-xl bg-white/10 p-4"><p>Fecha firmada</p><p class="mt-2 text-xl font-semibold">{{ resumen.fecha }}</p><p class="mt-2 text-sm">Debe coincidir con hoy en la zona de la institución.</p></div>
                    </div>
                </section>
                <Message severity="warn" :closable="false">Esta pantalla solo registra una transferencia sintética para QA. No envía dinero al banco. No utilices comprobantes ni cuentas reales.</Message>
                <div class="grid gap-5 lg:grid-cols-3">
                    <section class="space-y-4 rounded-2xl border border-surface-200 p-5 dark:border-surface-700 lg:col-span-2">
                        <h2 class="text-lg font-semibold"><i class="pi pi-shield mr-2 text-primary" />Antes de registrar</h2>
                        <ul v-if="pendientes.length" class="space-y-3"><li v-for="item in pendientes" :key="item.clave" class="rounded-xl bg-amber-50 p-3 text-amber-900 dark:bg-amber-950 dark:text-amber-100"><i class="pi pi-exclamation-circle mr-2" />{{ item.mensaje }}</li></ul>
                        <Message v-else severity="success" :closable="false">Formalización y evidencia vigentes. Se volverán a comprobar al confirmar.</Message>
                        <Button v-if="puedeRegistrar" label="Revisar y registrar desembolso QA" icon="pi pi-send" :disabled="!preparacion.permitido" @click="drawer = true" />
                        <p v-else class="text-sm text-surface-500">Solicita el permiso de desembolso al administrador y selecciona la sucursal responsable.</p>
                    </section>
                    <aside class="space-y-3 rounded-2xl border border-surface-200 bg-surface-50 p-5 dark:border-surface-700 dark:bg-surface-900">
                        <h2 class="font-semibold"><i class="pi pi-info-circle mr-2 text-primary" />Cómo se compone</h2>
                        <template v-if="resumen"><p>Monto solicitado: {{ money(resumen.monto) }}</p><p>Retenciones: {{ money(resumen.retenido) }}</p><p>Cargos iniciales separados: {{ money(resumen.separado) }}</p></template>
                        <p class="text-sm text-surface-500">Los importes incluyen los impuestos proyectados según su modalidad. Los cargos separados no se descuentan de la transferencia ni se marcan pagados aquí.</p>
                        <p class="text-sm text-surface-500">Al confirmar se crea un solo crédito, su calendario versión 1 y el movimiento de capital inicial. No podrás cancelar la solicitud ni editar el registro. Revisa antes de confirmar.</p>
                    </aside>
                </div>
                <Drawer v-model:visible="drawer" header="Confirmar desembolso QA" position="right" class="!w-full md:!w-[36rem]">
                    <form class="space-y-5" @submit.prevent="registrar">
                        <Message severity="info" :closable="false">Comprueba que los datos coinciden con la transferencia sintética. Si fecha o importe cambiaron, no registres: renueva las condiciones y la firma.</Message>
                        <div><label for="desembolso-importe" class="mb-2 block font-semibold">Importe transferido (MXN)</label><InputText id="desembolso-importe" :modelValue="money(form.importe)" readonly class="w-full" /><p class="mt-2 text-sm text-surface-500">Congelado por el contrato; no se recaptura ni modifica.</p></div>
                        <div><label for="desembolso-fecha" class="mb-2 block font-semibold">Fecha del desembolso</label><InputText id="desembolso-fecha" :modelValue="form.fecha_desembolso" readonly class="w-full" /></div>
                        <div><label for="desembolso-referencia" class="mb-2 block font-semibold">Referencia única de transferencia *</label><InputText id="desembolso-referencia" v-model="form.referencia" required minlength="5" maxlength="120" class="w-full" /><p class="mt-2 text-sm text-surface-500">Identificador completo del comprobante sintético (por ejemplo QA-TRANSFERENCIA-001). No lo reutilices en otro crédito ni captures datos bancarios personales.</p></div>
                        <label class="flex items-start gap-3"><Checkbox v-model="form.confirmacion_qa" binary /><span>Verifiqué fecha, cliente e importe. Confirmo el registro exclusivamente QA, sin dinero real.</span></label>
                        <Message v-for="(error, key) in form.errors" :key="key" severity="error" :closable="false">{{ error }}</Message>
                        <Button type="submit" label="Confirmar registro QA" icon="pi pi-check" :loading="form.processing" :disabled="!preparacion.permitido" class="w-full" />
                    </form>
                </Drawer>
            </div>
        </template>
    </AppLayout>
</template>
