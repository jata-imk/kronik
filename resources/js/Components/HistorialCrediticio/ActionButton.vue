<script setup>
import { defineProps } from "vue";

import { ArrowRight } from "lucide-vue-next";

const props = defineProps({
    label: {
        type: String,
        required: true,
    },
    href: {
        type: String,
        required: true,
    },
    severity: {
        type: String,
        default: "primary",
    },
});
</script>

<template>
    <Button :href="href" as="a" class="w-full" :severity="severity">
        {{ label }} <ArrowRight :size="16" class="mt-1" />
    </Button>
</template>