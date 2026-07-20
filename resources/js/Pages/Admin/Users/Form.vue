<script setup>
import { reactive, ref } from "vue";
import { useToast } from "primevue/usetoast";
import { useForm } from "@inertiajs/vue3";

const toast = useToast();
const props = defineProps({
    user: Object,
    roles: Array,
});
const emit = defineEmits(["close"]);

const user = reactive(props.user);
const roles = ref(props.roles);
const visible = ref(true);

const form = useForm({
    roles: user?.roles?.map((r) => r.id) || [],
});

const submit = () => {
    console.log(user.value);
    if (user?.id) {
        form.put(route("admin.users.update", user.id), {
            only: ["users"],
            onSuccess: () => {
                toast.add({
                    severity: "success",
                    summary: "Usuario actualizado exitosamente",
                    life: 3000,
                });

                emit("close");
            },
        });
    } else {
        form.post(route("admin.users.store"), {
            only: ["users"],
            onSuccess: () => {
                toast.add({
                    severity: "success",
                    summary: "Usuario creado exitosamente",
                    life: 3000,
                });

                emit("close");
            },
        });
    }
};
</script>

<template>
    <Dialog v-model:visible="visible" header="Configuración de usuario" :modal="true" :style="{ width: '500px' }" @hide="emit('close')">
        <form @submit.prevent="submit">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="roles">Roles</label>
                    <MultiSelect id="roles" name="roles" v-model="form.roles" :options="roles"
                        optionLabel="name" optionValue="id" fluid filter />
                </div>

                <div class="col-span-2 mt-4 flex justify-end gap-2">
                    <Button label="Cancelar" icon="pi pi-times" class="p-button-secondary"
                        @click.prevent="$emit('close')" />
                    <Button label="Guardar" icon="pi pi-check" type="submit" />
                </div>
            </div>
        </form>
    </Dialog>
</template>
