<script setup>
import { computed, useSlots } from "vue";
import SectionTitle from "./SectionTitle.vue";

defineEmits(["submitted"]);

const hasActions = computed(() => !!useSlots().actions);
</script>

<template>
    <div class="md:grid md:grid-cols-3 md:gap-6">
        <SectionTitle>
            <template #title>
                <slot name="title" />
            </template>
            <template #description>
                <slot name="description" />
            </template>
        </SectionTitle>

        <Card class="mt-5 md:mt-0 md:col-span-2" :pt="{
            body: '!p-0',
        }">
            <template #content >
                <form @submit.prevent="$emit('submitted')">
                    <div class="p-card-body">
                        <div class="grid grid-cols-6 gap-6">
                            <slot name="form" />
                        </div>
                    </div>

                    <div v-if="hasActions" class="flex items-center justify-end px-4 py-3 bg-gray-50 dark:bg-gray-800 text-end sm:px-6 shadow sm:rounded-bl-md sm:rounded-br-md">
                        <slot name="actions" />
                    </div>
                </form>
            </template>
        </Card>
    </div>
</template>
