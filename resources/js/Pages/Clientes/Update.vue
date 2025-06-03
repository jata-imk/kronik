<script setup>
import { ref } from "vue";
import { usePage } from "@inertiajs/vue3";

import AppLayout from "@sakai-vue/layout/AppLayout.vue";
import FormCliente from "./Partials/FormCliente.vue";

const page = usePage();
const menubarItems = ref(page.props.menubarItems);
// const menubarItems = ref([
//     {
//         label: "Regresar al listado",
//         icon: "pi pi-arrow-left",
//         iconPos: "left",
//         url: route("clientes.index"),
//     },
//     {
//         label: "Clientes",
//         items: [
//             {
//                 label: "Nuevo",
//                 icon: "pi pi-fw pi-user-plus",
//             },
//             {
//                 label: "Eliminar",
//                 icon: "pi pi-fw pi-user-minus",
//             },
//             {
//                 label: "Buscar",
//                 icon: "pi pi-fw pi-users",
//             },
//         ],
//     },
//     {
//         label: "Historial Crediticio",
//         items: [
//             {
//                 label: "Listado de consultas",
//                 icon: "pi pi-fw pi-list",
//                 url: route("historial-crediticio.index"),
//             },
//             {
//                 label: "Nueva consulta",
//                 icon: "pi pi-fw pi-plus",
//             },
//         ],
//     },
// ]);
</script>

<template>
    <AppLayout title="Información del cliente">
            <template #card-header>
                <Menubar :model="menubarItems">
                    <template #end>
                        <i class="pi pi-search px-2" />
                        <i class="pi pi-bars px-2" />
                    </template>
                </Menubar>

                <div class="flex justify-between items-center pl-8 pt-4">
                    <h2 class="text-2xl font-bold mb-4">Editar información del cliente</h2>
                </div>
            </template>
        
            <template #card-content>
                <FormCliente />
            </template>
    </AppLayout>
</template>
