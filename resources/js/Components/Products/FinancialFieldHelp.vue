<script setup>
import FieldHelp from "../FieldHelp.vue";
defineOptions({ inheritAttrs: false });
</script>

<template>
    <FieldHelp v-bind="$attrs" />
</template>
