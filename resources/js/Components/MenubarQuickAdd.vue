<script setup>
import { computed, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import MenubarItemForm from "@/Pages/Admin/MenubarItems/Form.vue";
const page = usePage();
const visible = ref(false);
const canCreate = computed(() => page.props.menubarAdmin?.canCreate === true);
const canRead = computed(() => page.props.menubarAdmin?.canRead === true);
const currentScreen = computed(() => page.props.menubarAdmin?.currentRouteName ?? "");
watch(() => [page.url, page.props.contextoOperativo?.team_id, page.props.contextoOperativo?.sucursal_id, currentScreen.value, canCreate.value], (current, previous) => {
    if (current.some((value, index) => value !== previous[index])) visible.value = false;
});
function open() { if (canCreate.value) visible.value = true; }
defineExpose({ open });
</script>
<template>
    <MenubarItemForm v-if="visible && canCreate" :key="currentScreen" :initial-screen="currentScreen" :can-preview="canRead" @close="visible = false" />
</template>
