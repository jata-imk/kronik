<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import FormSection from "@/Components/FormSection.vue";
import InputLabel from "@/Components/InputLabel.vue";

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const form = useForm({
    current_password: "",
    password: "",
    password_confirmation: "",
});

const updatePassword = () => {
    form.put(route("user-password.update"), {
        errorBag: "updatePassword",
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset("password", "password_confirmation");
                passwordInput.value.focus();
            }

            if (form.errors.current_password) {
                form.reset("current_password");
                currentPasswordInput.value.focus();
            }
        },
    });
};
</script>

<template>
    <FormSection @submitted="updatePassword">
        <template #title>
            Actualizar contraseña
        </template>

        <template #description>
            Asegúrese de que su cuenta utilice una contraseña larga y aleatoria para mantener su seguridad.
        </template>

        <template #form>
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="current_password" value="Contraseña actual" />
                <InputText
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="current-password"
                />
                <Message v-if="form.errors.current_password" severity="error" size="small" class="mt-2" > {{ form.errors.current_password }} </Message>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="password" value="Nueva contraseña" />
                <InputText
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
                <Message v-if="form.errors.password" severity="error" size="small" class="mt-2" > {{ form.errors.password }} </Message>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="password_confirmation" value="Confirme la nueva contraseña" />
                <InputText
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    autocomplete="new-password"
                />
                <Message v-if="form.errors.password_confirmation" severity="error" size="small" class="mt-2" > {{ form.errors.password_confirmation }} </Message>
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Guardado.
            </ActionMessage>

            <Button severity="contrast" class="uppercase font-semibold !text-xs" :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Guardar
            </Button>
        </template>
    </FormSection>
</template>
