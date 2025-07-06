<script setup>
import { ref, watch } from "vue";
import { useToast } from "primevue/usetoast";
import { useForm } from "@inertiajs/vue3";

const toast = useToast();

const emit = defineEmits(["close"]);
const props = defineProps({
    visible: Boolean,
});

const visible = ref(props.visible);

watch(
    () => props.visible,
    () => {
        visible.value = props.visible;
    },
);

const form = useForm({
    name: "",
    add_all_permissions: false,
});

const submit = async () => {
    form.post(route("admin.roles.store"), {
        only: ["roles"],
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Rol creado exitosamente",
                life: 3000,
            });

            emit("close");
        },
    });
};
</script>

<template>
  <Dialog v-model:visible="visible" :modal="true" :style="{ width: '500px' }" @hide="emit('close')" >
    <template #header>
      <h2>Crear Rol</h2>
    </template>

    <form @submit.prevent="submit">
      <div class="grid grid-cols-1 gap-4">
        <div>
          <label class="block text-sm font-medium mb-1" for="name">Nombre del Rol</label>
          <InputText id="name" v-model="form.name" type="text" fluid />
        </div>

        <div class="flex items-center gap-2">
            <Checkbox input-id="add_all_permissions" :value="true" v-model="form.add_all_permissions" :binary="true" />
            <label class="block text-sm font-medium mb-1" for="add_all_permissions">Agregar todos los permisos</label>
        </div>
      </div>

      <div class="col-span-2 mt-4 flex justify-end gap-2">
        <Button label="Cancelar" icon="pi pi-times" class="p-button-secondary" @click.prevent="$emit('close')" />
        <Button label="Crear" icon="pi pi-check" type="submit" />
      </div>
    </form>
  </Dialog>
</template>