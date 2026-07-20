<script setup>
import { ref } from "vue";
import { useForm } from "@inertiajs/vue3";
import ActionMessage from "@/Components/ActionMessage.vue";
import ActionSection from "@/Components/ActionSection.vue";
import ConfirmationModal from "@/Components/ConfirmationModal.vue";
import DialogModal from "@/Components/DialogModal.vue";
import FormSection from "@/Components/FormSection.vue";
import InputLabel from "@/Components/InputLabel.vue";
import SectionBorder from "@/Components/SectionBorder.vue";

const props = defineProps({
    tokens: Array,
    availablePermissions: Array,
    defaultPermissions: Array,
});

const createApiTokenForm = useForm({
    name: "",
    permissions: props.defaultPermissions,
});

const updateApiTokenForm = useForm({
    permissions: [],
});

const deleteApiTokenForm = useForm({});

const displayingToken = ref(false);
const managingPermissionsFor = ref(null);
const apiTokenBeingDeleted = ref(null);

const createApiToken = () => {
    createApiTokenForm.post(route("api-tokens.store"), {
        preserveScroll: true,
        onSuccess: () => {
            displayingToken.value = true;
            createApiTokenForm.reset();
        },
    });
};

const manageApiTokenPermissions = (token) => {
    updateApiTokenForm.permissions = token.abilities;
    managingPermissionsFor.value = token;
};

const updateApiToken = () => {
    updateApiTokenForm.put(
        route("api-tokens.update", managingPermissionsFor.value),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                managingPermissionsFor.value = null;
            },
        },
    );
};

const confirmApiTokenDeletion = (token) => {
    apiTokenBeingDeleted.value = token;
};

const deleteApiToken = () => {
    deleteApiTokenForm.delete(
        route("api-tokens.destroy", apiTokenBeingDeleted.value),
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                apiTokenBeingDeleted.value = null;
            },
        },
    );
};
</script>

<template>
    <div>
        <!-- Generate API Token -->
        <FormSection @submitted="createApiToken">
            <template #title>
                Crear Token API
            </template>

            <template #description>
                Los tokens API permiten que los servicios de terceros se autentiquen con nuestra aplicación en su nombre.
            </template>

            <template #form>
                <!-- Token Name -->
                <div class="col-span-6 sm:col-span-4">
                    <InputLabel for="name" value="Nombre" />
                    <InputText
                        id="name"
                        v-model="createApiTokenForm.name"
                        type="text"
                        class="mt-1 block w-full"
                        autofocus
                    />
                    <Message v-if="createApiTokenForm.errors.name" severity="error" size="small" class="mt-2" > {{ createApiTokenForm.errors.name }} </Message>
                </div>

                <!-- Token Permissions -->
                <div v-if="availablePermissions.length > 0" class="col-span-6">
                    <InputLabel for="permissions" value="Permisos" />

                    <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div v-for="permission in availablePermissions" :key="permission">
                            <label class="flex items-center">
                                <Checkbox v-model="createApiTokenForm.permissions" :value="permission" />
                                <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">{{ permission }}</span>
                            </label>
                        </div>
                    </div>
                </div>
            </template>

            <template #actions>
                <ActionMessage :on="createApiTokenForm.recentlySuccessful" class="me-3">
                    Creado.
                </ActionMessage>

                <Button severity="contrast" class="uppercase font-semibold !text-xs" :class="{ 'opacity-25': createApiTokenForm.processing }" :disabled="createApiTokenForm.processing">
                    Crear
                </Button>
            </template>
        </FormSection>

        <div v-if="tokens.length > 0">
            <SectionBorder />

            <!-- Manage API Tokens -->
            <div class="mt-10 sm:mt-0">
                <ActionSection>
                    <template #title>
                        Administrar Tokens API
                    </template>

                    <template #description>
                        Puedes eliminar cualquiera de tus tokens existentes si ya no los necesitas.
                    </template>

                    <!-- API Token List -->
                    <template #content>
                        <div class="space-y-6">
                            <div v-for="token in tokens" :key="token.id" class="flex items-center justify-between">
                                <div class="break-all">
                                    {{ token.name }}
                                </div>

                                <div class="flex items-center ms-2">
                                    <div v-if="token.last_used_ago" class="text-sm text-gray-400">
                                        Ultima vez utilizado {{ token.last_used_ago }}
                                    </div>

                                    <button
                                        v-if="availablePermissions.length > 0"
                                        class="cursor-pointer ms-6 text-sm text-gray-400 underline"
                                        @click="manageApiTokenPermissions(token)"
                                    >
                                        Permisos
                                    </button>

                                    <button class="cursor-pointer ms-6 text-sm text-red-500" @click="confirmApiTokenDeletion(token)">
                                        Borrar
                                    </button>
                                </div>
                            </div>
                        </div>
                    </template>
                </ActionSection>
            </div>
        </div>

        <!-- Token Value Modal -->
        <DialogModal :show="displayingToken" @close="displayingToken = false">
            <template #title>
                Token API
            </template>

            <template #content>
                <div>
                    Copia tu nuevo token de API. Por tu seguridad, no se volverá a mostrar.
                </div>

                <div v-if="$page.props.jetstream.flash.token" class="mt-4 bg-gray-100 px-4 py-2 rounded font-mono text-sm text-gray-500 break-all">
                    {{ $page.props.jetstream.flash.token }}
                </div>
            </template>

            <template #footer>
                <Button severity="secondary" raised class="uppercase font-semibold !text-xs" @click="displayingToken = false">
                    Cerrar
                </Button>
            </template>
        </DialogModal>

        <!-- API Token Permissions Modal -->
        <DialogModal :show="managingPermissionsFor != null" @close="managingPermissionsFor = null">
            <template #title>
                Permisos de Token de API
            </template>

            <template #content>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div v-for="permission in availablePermissions" :key="permission">
                        <label class="flex items-center">
                            <Checkbox v-model="updateApiTokenForm.permissions" :value="permission" />
                            <span class="ms-2 text-sm text-gray-600">{{ permission }}</span>
                        </label>
                    </div>
                </div>
            </template>

            <template #footer>
                <Button severity="secondary" raised class="uppercase font-semibold !text-xs" @click="managingPermissionsFor = null">
                    Cancelar
                </Button>

                <Button
                    severity="contrast" raised
                    class="ms-3 uppercase font-semibold !text-xs"
                    :class="{ 'opacity-25': updateApiTokenForm.processing }"
                    :disabled="updateApiTokenForm.processing"
                    @click="updateApiToken"
                >
                    Guardar
                </Button>
            </template>
        </DialogModal>

        <!-- Delete Token Confirmation Modal -->
        <ConfirmationModal :show="apiTokenBeingDeleted != null" @close="apiTokenBeingDeleted = null">
            <template #title>
                Eliminar token de API
            </template>

            <template #content>
                ¿Está seguro de que desea eliminar este token de API?
            </template>

            <template #footer>
                <Button severity="secondary" raised class="uppercase font-semibold !text-xs" @click="apiTokenBeingDeleted = null">
                    Cancelar
                </Button>

                <Button
                    severity="danger" raised
                    class="ms-3 uppercase font-semibold !text-xs"
                    :class="{ 'opacity-25': deleteApiTokenForm.processing }"
                    :disabled="deleteApiTokenForm.processing"
                    @click="deleteApiToken"
                >
                    Borrar
                </Button>
            </template>
        </ConfirmationModal>
    </div>
</template>
