<template>
  <div
    :class="props.type == 'credit-score' ? 'col-span-3' : ''"
    class="flex flex-col items-center justify-center min-h-[400px] p-8 bg-gray-200 dark:bg-gray-800 rounded-xl shadow-sm">
    <div class="text-center max-w-md">
      <div class="mx-auto w-16 h-16 flex items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-900 mb-6">
        <component :is="iconComponent" :size="48" class="text-indigo-600 dark:text-indigo-400" />
      </div>

      <h2 class="text-2xl font-semibold text-gray-900 dark:text-gray-200 mb-3">{{ content.title }}</h2>
      <p class="text-gray-600 dark:text-gray-400 mb-8">{{ content.description }}</p>

      <div class="space-y-3">
        
        <Button v-if="!!content.primaryAction.url" :href="content.primaryAction.url" as="a" class="w-full" severity="primary">
            {{ content.primaryAction.label }} <ArrowRight :size="16" class="mt-1" />
        </Button>
        <Button v-else :href="content.primaryAction.url" as="a" class="w-full" severity="primary">
            {{ content.primaryAction.label }} <ArrowRight :size="16" class="mt-1" />
        </Button>

        <Button v-if="content.secondaryAction" class="w-full" severity="secondary">{{ content.secondaryAction }}</Button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from "vue";
import { FileSearch, ArrowRight, ShieldCheck } from "lucide-vue-next"; // Asegúrate de instalar `lucide-vue-next` o adaptar al paquete que uses

const props = defineProps({
    type: {
        type: String,
        required: true,
        validator: (val) =>
            ["credit-score", "history", "accounts", "alerts"].includes(val),
    },
});

const contentMap = {
    "credit-score": {
        icon: "ShieldCheck",
        title: "No hay puntaje crediticio disponible",
        description:
            "Aún no hemos recibido los datos de tu puntaje crediticio. Esto podría deberse a que eres nuevo en el monitoreo de crédito o a que aún estamos procesando tu información.",
        primaryAction: {
            label: "Realizar consulta",
            url: route("circulo-credito.create"),
        },
        secondaryAction: "Más información sobre puntajes crediticios",
    },
    history: {
        icon: "FileSearch",
        title: "No se encontró historial crediticio",
        description:
            "Tu historial crediticio aparecerá aquí una vez que conectes tus cuentas o después de que se registre tu primera actividad crediticia.",
        primaryAction: {
            label: "Conectar cuentas",
            url: route("circulo-credito.create"),
        },
        secondaryAction: "Ver guía de crédito",
    },
    accounts: {
        icon: "FileSearch",
        title: "No hay cuentas de crédito activas",
        description:
            "Aún no has agregado ninguna cuenta de crédito. Conecta tus cuentas para comenzar a monitorear tu actividad crediticia.",
        primaryAction: {
            label: "Conectar cuentas",
            url: route("circulo-credito.create"),
        },
        secondaryAction: "Explorar opciones de crédito",
    },
    alertas: {
        ícono: "ShieldCheck",
        título: "Alertas sin crédito",
        descripción:
            "¡Ya está todo al día! No hay alertas ni notificaciones activas en este momento.",
        primaryAction: {
            label: "Configurar alertas",
        },
        secondaryAction: "Configurar notificaciones",
    },
};

const content = computed(() => contentMap[props.type]);
const iconComponent = computed(() => {
    const icons = { FileSearch, ShieldCheck };
    return icons[content.value.icon];
});
</script>
