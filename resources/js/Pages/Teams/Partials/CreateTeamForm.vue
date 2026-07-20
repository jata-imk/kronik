<script setup>
import { useForm } from "@inertiajs/vue3";
import FormSection from "@/Components/FormSection.vue";
import InputLabel from "@/Components/InputLabel.vue";

const form = useForm({
    name: "",
});

const createTeam = () => {
    console.log(form);
    form.post(route("teams.store"), {
        errorBag: "createTeam",
        preserveScroll: true,
    });
};
</script>

<template>
    <FormSection @submitted="createTeam">
        <template #title>
            Detalles del equipo
        </template>

        <template #description>
            Crea un nuevo equipo para colaborar con otros en proyectos.
        </template>

        <template #form>
            <div class="col-span-6">
                <InputLabel value="Propietario del equipo" />

                <div class="flex items-center mt-2">
                    <img class="object-cover size-12 rounded-full" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">

                    <div class="ms-4 leading-tight">
                        <div class="text-gray-900 dark:text-gray-200">{{ $page.props.auth.user.name }}</div>
                        <div class="text-sm text-gray-700 dark:text-gray-400">
                            {{ $page.props.auth.user.email }}
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Nombre del equipo" />
                <InputText
                    id="name"
                    name="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full mt-1"
                    autofocus
                />
                <Message v-if="form.errors.name" severity="error" size="small" class="mt-2"> {{ form.errors.name }} </Message>
            </div>
        </template>

        <template #actions>
            <Button severity="contrast" class="uppercase font-semibold !text-xs" :class="{ 'opacity-25': form.processing }" :disabled="form.processing" type="submit">
                Crear
            </Button>
        </template>
    </FormSection>
</template>
