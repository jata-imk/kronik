<script setup>
import { computed } from "vue";

defineOptions({ inheritAttrs: false });

const props = defineProps({
    modelValue: { type: [Number, String], default: null },
    options: { type: Array, default: () => [] },
    optionValue: { type: String, default: "id" },
    disabled: { type: Boolean, default: false },
    invalid: { type: Boolean, default: false },
});

const emit = defineEmits(["update:modelValue", "change"]);

const normalize = (value) =>
    String(value ?? "")
        .normalize("NFD")
        .replace(/\p{Diacritic}/gu, "")
        .toLowerCase();

const paises = computed(() =>
    props.options.map((pais) => ({
        ...pais,
        busqueda: [
            pais.nombre_es,
            normalize(pais.nombre_es),
            pais.nombre_us,
            normalize(pais.nombre_us),
            pais.codigo_iso,
            pais.codigo_iso3,
        ]
            .filter(Boolean)
            .join(" ")
            .toLowerCase(),
    })),
);

const selected = computed(() =>
    paises.value.find((pais) => pais[props.optionValue] === props.modelValue),
);
</script>

<template>
    <Select
        :model-value="modelValue"
        :options="paises"
        :option-value="optionValue"
        option-label="nombre_es"
        :filter-fields="['busqueda']"
        filter
        reset-filter-on-hide
        :disabled="disabled"
        :invalid="invalid"
        fluid
        v-bind="$attrs"
        @update:model-value="emit('update:modelValue', $event)"
        @change="emit('change', $event)"
    >
        <template #option="{ option }">
            <div class="flex items-center gap-2">
                <span v-if="option.emoji" v-twemoji>{{ option.emoji }}</span>
                <span v-else class="country-code">{{ option.codigo_iso }}</span>
                <span>{{ option.nombre_es }}</span>
                <small class="text-surface-500">{{ option.codigo_iso }}</small>
            </div>
        </template>
        <template #value="{ placeholder }">
            <div v-if="selected" class="flex items-center gap-2">
                <span v-if="selected.emoji" v-twemoji>{{ selected.emoji }}</span>
                <span v-else class="country-code">{{ selected.codigo_iso }}</span>
                <span>{{ selected.nombre_es }}</span>
            </div>
            <span v-else>{{ placeholder ?? "Seleccione un país" }}</span>
        </template>
    </Select>
</template>

<style scoped>
.country-code {
    min-width: 2rem;
    font-size: 0.7rem;
    font-weight: 700;
    text-align: center;
    border: 1px solid var(--p-content-border-color);
    border-radius: 0.25rem;
    padding: 0.15rem 0.25rem;
}
</style>
