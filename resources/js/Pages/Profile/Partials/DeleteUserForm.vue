<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionSection from "@/Components/ActionSection.vue";
import DialogModal from "@/Components/DialogModal.vue";

const confirmingUserDeletion = ref(false);
const passwordInput = ref(null);

const form = useForm({
    password: "",
});

const confirmUserDeletion = () => {
    confirmingUserDeletion.value = true;

    setTimeout(() => passwordInput.value.focus(), 250);
};

const deleteUser = () => {
    form.delete(route("current-user.destroy"), {
        preserveScroll: true,
        onSuccess: () => closeModal(),
        onError: () => passwordInput.value.focus(),
        onFinish: () => form.reset(),
    });
};

const closeModal = () => {
    confirmingUserDeletion.value = false;

    form.reset();
};
</script>

<template>
    <ActionSection>
        <template #title>
            Eliminar cuenta
        </template>

        <template #description>
            Eliminar permanentemente su cuenta.
        </template>

        <template #content>
            <div class="max-w-xl text-sm text-gray-600">
                Una vez eliminada su cuenta, todos sus recursos y datos se eliminarán permanentemente. Antes de eliminar su cuenta, descargue cualquier dato o información que desee conservar.
            </div>

            <div class="mt-5">
                <Button severity="danger" class="uppercase font-semibold !text-xs" @click="confirmUserDeletion">
                    Eliminar cuenta
                </Button>
            </div>

            <!-- Delete Account Confirmation Modal -->
            <DialogModal :show="confirmingUserDeletion" @close="closeModal">
                <template #title>
                    Eliminar cuenta
                </template>

                <template #content>
                    ¿Seguro que desea eliminar su cuenta? Una vez eliminada, todos sus recursos y datos se eliminarán permanentemente. Ingrese su contraseña para confirmar que desea eliminar su cuenta permanentemente.

                    <div class="mt-4">
                        <InputText
                            ref="passwordInput"
                            v-model="form.password"
                            type="password"
                            class="mt-1 block w-3/4"
                            placeholder="Contraseña"
                            autocomplete="current-password"
                            @keyup.enter="deleteUser"
                        />

                        <Message v-if="form.errors.password" severity="error" size="small" class="mt-2" > {{ form.errors.password }} </Message>
                    </div>
                </template>

                <template #footer>
                    <Button severity="secondary" raised class="uppercase font-semibold !text-xs" @click="closeModal">
                        Cancelar
                    </Button>

                    <Button
                        severity="danger" raised
                        class="ms-3 uppercase font-semibold !text-xs"
                        :class="{ 'opacity-25': form.processing }"
                        :disabled="form.processing"
                        @click="deleteUser"
                    >
                        Eliminar cuenta
                    </Button>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
