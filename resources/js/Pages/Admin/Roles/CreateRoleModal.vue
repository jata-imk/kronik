<script setup>
import { ref, watch } from "vue";
import { useToast } from "primevue/usetoast";
import { useGuardedForm as useForm } from "@/Composables/useGuardedForm";
import { confirmFormDiscard } from "@/Utils/unsavedChanges";

const toast = useToast();

const emit = defineEmits(["close"]);
const props = defineProps({
    visible: Boolean,
    teamId: Number,
});

const visible = ref(props.visible);

watch(
    () => props.visible,
    () => {
        visible.value = props.visible;
        if (!props.visible) { form.defaults({ name: "", add_all_permissions: false }); form.reset(); form.clearErrors(); }
    },
);

const form = useForm({
    name: "",
    add_all_permissions: false,
});

const submit = async () => {
    form.post(route("admin.roles.store", { team: props.teamId }), {
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
function requestClose() {
    if (!confirmFormDiscard(form)) return;
    form.defaults({ name: "", add_all_permissions: false }); form.reset(); form.clearErrors();
    emit("close");
}
</script>

<template>
  <Dialog :visible="visible" :modal="true" :style="{ width: '500px', maxWidth: '95vw' }" @update:visible="value => { if (!value) requestClose(); }" >
    <template #header>
      <h2>Crear Rol</h2>
    </template>

    <form @submit.prevent="submit">
      <Message v-for="(error, field) in form.errors" :key="field" severity="error">{{ error }}</Message>
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
        <Button label="Cancelar" icon="pi pi-times" class="p-button-secondary" @click.prevent="requestClose" />
        <Button label="Crear" icon="pi pi-check" type="submit" />
      </div>
    </form>
  </Dialog>
</template>
