<script setup>
const props = defineProps({
    modules: Array,
    permissions: {
        type: Array,
        default: () => [],
    },
});
const model = defineModel("selected-permissions");
</script>

<template>
    <div>
        <div v-for="module in modules" :key="module.id" class="flex flex-col gap-4 p-4">
            <div class="flex items-center gap-4">
                <span class="text-sm text-gray-500 dark:text-gray-400 font-bold min-w-fit">{{ module.name }}</span>
                <span class="w-full border-b border-gray-200 block dark:border-gray-700 mt-2"></span>
            </div>
            <div>
                <Listbox
                    v-model="model"
                    :options="permissions.filter((p) => p.module_id === module.id)"
                    optionLabel="name"
                    optionValue="id"
                    multiple
                    checkmark
                    filter
                    filter-placeholder="Buscar permiso"
                    fluid
                />
            </div>
        </div>
    </div>
</template>
