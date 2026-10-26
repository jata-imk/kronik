<script setup>
import ContextHelp from "@/Components/Products/FinancialFieldHelp.vue";
const props = defineProps({
    modules: Array,
    readonly: Boolean,
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
                <span class="text-sm text-gray-500 dark:text-gray-400 font-bold min-w-fit">{{ module.etiqueta }}</span>
                <ContextHelp :title="module.etiqueta" :description="module.descripcion" />
                <span class="w-full border-b border-gray-200 block dark:border-gray-700 mt-2"></span>
            </div>
            <div>
                <Listbox
                    v-model="model"
                    :options="permissions.filter((p) => p.module_id === module.id)"
                    optionLabel="etiqueta"
                    optionValue="id"
                    multiple
                    checkmark
                    fluid
                    :disabled="readonly"
                >
                    <template #option="{ option }">
                        <span>{{ option.etiqueta }}</span>
                        <span @click.stop @keydown.enter.stop @keydown.space.stop>
                            <ContextHelp :title="option.etiqueta" :description="option.descripcion" />
                        </span>
                    </template>
                </Listbox>
            </div>
        </div>
    </div>
</template>
