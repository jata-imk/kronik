<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { computed } from "vue";
const props = defineProps({ metadata: Object, pageName: { type: String, default: "page" }, perPageName: { type: String, default: "por_pagina" } });
const page = usePage();
const pagination = computed(() => props.metadata === undefined ? page.props.listado : props.metadata);
function change(event) {
    const url = new URL(window.location.href);
    router.get(url.pathname, { ...Object.fromEntries(url.searchParams.entries()), [props.pageName]: event.page + 1, [props.perPageName]: event.rows }, { preserveState: true, preserveScroll: true });
}
</script>
<template>
    <div v-if="pagination" class="p-3">
        <p class="mb-2 text-sm text-surface-500">{{ pagination.total }} registros con estos filtros</p>
        <Paginator :first="(pagination.current_page - 1) * pagination.per_page" :rows="pagination.per_page" :total-records="pagination.total" :rows-per-page-options="[10, 25, 50]" @page="change" />
    </div>
</template>
