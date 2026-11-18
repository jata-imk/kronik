<script setup>
import { computed, ref, watch } from "vue";
import { usePage } from "@inertiajs/vue3";
import MenubarItemForm from "@/Pages/Admin/MenubarItems/Form.vue";
const page = usePage();
const visible = ref(false);
const canCreate = computed(() => page.props.auth?.is_super_admin || page.props.auth?.permissions?.["create-menubar-items"] === true);
const canRead = computed(() => page.props.auth?.is_super_admin || page.props.auth?.permissions?.["read-menubar-items"] === true);
const currentScreen = computed(() => page.props.menubarAdmin?.currentRouteName ?? "");
watch(() => [page.url, page.props.contextoOperativo, currentScreen.value, canCreate.value], () => { visible.value = false; }, { deep: true });
function open() { if (canCreate.value) visible.value = true; }
defineExpose({ open });
</script>
<template>
    <MenubarItemForm v-if="visible && canCreate" :key="currentScreen" :initial-screen="currentScreen" :can-preview="canRead" @close="visible = false" />
</template>
