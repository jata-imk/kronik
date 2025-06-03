<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionSection from "@/Components/ActionSection.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";

const props = defineProps({
    team: Object,
});

const confirmingTeamDeletion = ref(false);
const form = useForm({});

const confirmTeamDeletion = () => {
    confirmingTeamDeletion.value = true;
};

const deleteTeam = () => {
    form.delete(route("teams.destroy", props.team), {
        errorBag: "deleteTeam",
    });
};
</script>

<template>
    <ActionSection>
        <template #title>
            Eliminar equipo
        </template>

        <template #description>
            Eliminar este equipo de forma permanente.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-600 dark:text-gray-400">
                Al eliminar un equipo, todos sus recursos y datos se eliminarán permanentemente. Antes de eliminarlo, descargue cualquier dato o información que desee conservar.
            </div>

            <div class="mt-5">
                <Button severity="danger" class="uppercase font-semibold !text-xs" @click="confirmTeamDeletion">
                    Eliminar equipo
                </Button>
            </div>

            <!-- Delete Team Confirmation Modal -->
            <ConfirmationModal :show="confirmingTeamDeletion" @close="confirmingTeamDeletion = false">
                <template #title>
                    Eliminar equipo
                </template>

                <template #content>
                    ¿Seguro que quieres eliminar este equipo? Al eliminar un equipo, todos sus recursos y datos se eliminarán permanentemente.
                </template>

                <template #footer>
                    <Button severity="secondary" raised class="uppercase font-semibold !text-xs" @click="confirmingTeamDeletion = false">
                        Cancelar
                    </Button>

                    <Button
                        severity="danger" raised
                        class="ms-3 uppercase font-semibold !text-xs"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteTeam"
                    >
                        Eliminar equipo
                    </Button>
                </template>
            </ConfirmationModal>
        </template>
    </ActionSection>
</template>
