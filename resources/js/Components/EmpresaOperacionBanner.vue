<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import { computed } from "vue";

const page = usePage();
const empresa = computed(() => page.props.empresaOperacion);
const puedeConfigurar = computed(() => page.props.auth?.permissions?.["read-configuracion-empresa"]);
</script>

<template>
    <aside v-if="empresa?.motivo" role="status" class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-100">
        <p class="font-semibold">Empresa {{ empresa.estatus === "suspendida" ? "suspendida" : "en borrador" }}</p>
        <p class="mt-1">{{ empresa.motivo }}</p>
        <Link v-if="puedeConfigurar" :href="route('admin.configuracion-empresa.index')" class="mt-2 inline-block font-medium underline">Revisar configuración de empresa</Link>
    </aside>
</template>
