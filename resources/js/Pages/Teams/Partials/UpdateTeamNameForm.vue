<script setup>
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputLabel from "@/Components/InputLabel.vue";

const props = defineProps({
    team: Object,
    permissions: Object,
});

const form = useForm({
    name: props.team.name,
});

const updateTeamName = () => {
    form.put(route("teams.update", props.team), {
        errorBag: "updateTeamName",
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="updateTeamName">
        <template #title>
            Nombre del equipo
        </template>

        <template #description>
            El nombre del equipo y la información del propietario.
        </template>

        <template #form>
            <!-- Team Owner Information -->
            <div class="col-span-6">
                <InputLabel value="Propietario del equipo" />

                <div class="flex items-center mt-2">
                    <img class="size-12 rounded-full object-cover" :src="team.owner.profile_photo_url" :alt="team.owner.name">

                    <div class="ms-4 leading-tight">
                        <div class="text-gray-900 dark:text-gray-200">{{ team.owner.name }}</div>
                        <div class="text-gray-700 dark:text-gray-400 text-sm">
                            {{ team.owner.email }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Team Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Nombre del equipo" />

                <InputText
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="! permissions.canUpdateTeam"
                />

                <Message v-if="form.errors.name" severity="error" size="small" class="mt-2">{{ form.errors.name }}</Message>
            </div>
        </template>

        <template v-if="permissions.canUpdateTeam" #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Guardado.
            </ActionMessage>

            <Button severity="contrast" class="uppercase font-semibold !text-xs" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Guardar
            </Button>
        </template>
    </FormSection>
</template>
