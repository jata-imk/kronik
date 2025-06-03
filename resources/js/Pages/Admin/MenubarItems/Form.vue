<script setup>
import { reactive, ref, watch } from "vue";
import { useToast } from "primevue/usetoast";
import { useForm } from "@inertiajs/vue3";

const toast = useToast();
const props = defineProps({
    modules: Array,
    menubarItems: Array,
    item: Object,
});
const emit = defineEmits(["close"]);

const modules = ref(props.modules);
const menubarItemsWithChildren = ref([
    {
        label: "Base",
        children: props.menubarItems.filter((i) => i.children?.length > 0),
    },
    ...props.menubarItems
        .flatMap((i) => [i, ...i.children.flatMap((c) => [c, ...c.children])])
        .filter((i) => i.children?.length > 0),
]);
const item = reactive(props.item);

const visible = ref(true);

const actionTypes = [
    { label: "Listado", value: "index" },
    { label: "Crear", value: "create" },
    { label: "Ver", value: "show" },
    { label: "Editar", value: "edit" },
];
const menubarItemTypes = [
    { label: "Menu", value: "menu" },
    { label: "Ruta Laravel", value: "route:name" },
    { label: "URL Estática", value: "route:static" },
    { label: "Referer - Fallback", value: "route:referer_fallback" },
    { label: "Dinamica", value: "route:dynamic" },
];

const form = useForm({
    modules:
        item &&
        Object.fromEntries(
            item?.modules?.map((m) => [
                m.id,
                { routes: m.menubar_item_module.routes },
            ]),
        ),
    label: item?.label || "",
    icon: item?.icon || "",
    type: item?.type || "route:name",
    value: item?.value || "",
    params: (item?.params && Object.values(item?.params).join(",")) || "",
    parent_id: item?.parent_id || null,
});

const modulesSelected = ref(item ? Object.keys(form.modules).map(Number) : []);
const modulesSelectedMenubarItemModule = reactive(
    item
        ? Object.fromEntries(
              modulesSelected.value.map((id) => [
                  id,
                  form.modules[id].routes?.map((r) => r.split(".")[1]),
              ]),
          )
        : {},
);
const flagAnyActionModify = ref(false);

watch(
    () => [modulesSelected, modulesSelectedMenubarItemModule],
    () => {
        form.modules = Object.fromEntries(
            modulesSelected.value.map((id) => [
                id,
                {
                    routes: modulesSelectedMenubarItemModule[id]?.map(
                        (r) =>
                            `${modules.value.find((m) => m.id === id)?.route_name}.${r}`,
                    ),
                },
            ]),
        );
        flagAnyActionModify.value = false;
        for (const [moduleId, routes] of Object.entries(
            modulesSelectedMenubarItemModule,
        )) {
            if (routes.includes("edit") || routes.includes("show")) {
                flagAnyActionModify.value = true;
            }
        }

        if (flagAnyActionModify.value) {
            let params = "";

            for (const moduleId of modulesSelected.value) {
                const module = modules.value.find((m) => m.id === moduleId);
                const moduleName = module.name;

                let moduleNameSingular = moduleName;

                if (moduleName.endsWith("s")) {
                    moduleNameSingular = moduleName.slice(0, -1);
                }

                params += `"${moduleNameSingular}" : "{${moduleNameSingular}}",`;
            }

            form.params = `{${params.slice(0, -1)}}`;
        }
    },
    {
        deep: true,
        immediate: true,
    },
);

const submit = () => {
    if (item?.id) {
        form.put(route("admin.menubar-items.update", item.id), {
            only: ["menubarItems"],
            onSuccess: () => {
                toast.add({
                    severity: "success",
                    summary: "Menubar Item actualizado exitosamente",
                    life: 3000,
                });

                emit("close");
            },
        });
    } else {
        form.post(route("admin.menubar-items.store"), {
            only: ["menubarItems"],
            onSuccess: () => {
                toast.add({
                    severity: "success",
                    summary: "Menubar Item creado exitosamente",
                    life: 3000,
                });

                emit("close");
            },
        });
    }
};
</script>

<template>
    <Dialog v-model:visible="visible" header="Formulario de item de menú" :modal="true" :style="{ width: '500px' }" @hide="emit('close')">
        <form @submit.prevent="submit">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium mb-1" for="label">Titulo</label>
                    <InputText id="label" fluid v-model="form.label" />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="module_id">Modulo(s)</label>
                    <MultiSelect id="module_id" name="module_id" v-model="modulesSelected" :options="modules"
                        optionLabel="name" optionValue="id" fluid filter />
                </div>

                <div v-if="modulesSelected.length" v-for="module in modulesSelected" :key="module" class="col-span-2">
                    <label class="block text-sm font-medium mb-1" :for="`module-${module}-routes`">Rutas para el modulo {{ modules.find((m) => m.id === module)?.name }}</label>
                    <MultiSelect :id="`module-${module}-routes`" :name="`module-${module}-routes`" v-model="modulesSelectedMenubarItemModule[module]" :options="actionTypes"
                        optionLabel="label" optionValue="value" fluid filter />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="icon">Icono (Clases CSS)</label>
                    <InputText id="icon" fluid v-model="form.icon" />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="type">Tipo de item</label>
                    <Select id="type" v-model="form.type" fluid :options="menubarItemTypes" optionLabel="label"
                        optionValue="value" />
                </div>

                <div v-if="form.type !== 'route:dynamic'">
                    <label class="block text-sm font-medium mb-1" for="value">URL / Route name</label>
                    <InputText id="value" fluid v-model="form.value" />
                </div>

                <div v-else>
                    <label class="block text-sm font-medium mb-1" for="value">JSON Condiciones</label>
                    <Textarea id="value" rows="7" fluid style="resize: none" v-model="form.value"
                        :placeholder="JSON.stringify({condition_type: 'value', condition_value: 'value', route_name: 'value', params: 'value'})" />
                </div>

                <div>
                    <label class="block text-sm font-medium mb-1" for="parent_id">Padre</label>
                    <Select
                        id="parent_id"
                        v-model="form.parent_id"
                        fluid
                        :options="menubarItemsWithChildren"
                        optionLabel="label"
                        optionValue="id"
                        optionGroupLabel="label"
                        optionGroupChildren="children" >
                        <template #optiongroup="slotProps">
                            <div class="flex items-center">
                                <span :class="`pi ${slotProps.option.icon} mr-2`" ></span>
                                <div>{{ slotProps.option.label.split("-").map((word) => word.charAt(0).toUpperCase() + word.slice(1)).join(" ") }}</div>
                            </div>
                        </template>

                        <template #option="slotProps">
                            <div class="flex items-center">
                                <span class="pi pi-arrow-right !text-[0.5rem] ml-4 mr-2"></span>
                                <div>{{  slotProps.option.label }}</div>
                            </div>
                        </template>
                    </Select>
                </div>

                <div v-if="flagAnyActionModify">
                    <label class="block text-sm font-medium mb-1" for="params">Parámetros</label>
                    <InputText id="params" fluid v-model="form.params" />
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
