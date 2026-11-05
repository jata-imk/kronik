<script setup>
import { Link } from "@inertiajs/vue3";
defineProps({ info: Object, consulta: Boolean });
</script>
<template>
    <Message v-if="info" :severity="info.existe ? 'info' : 'warn'" :closable="false">
        <p class="font-semibold">{{ info.mensaje }}</p>
        <p>{{ info.ubicacion }}</p>
        <p v-if="!info.existe">{{ info.siguiente }}</p>
        <Link v-if="info.accion" :href="route(info.accion.ruta, consulta ? {...info.accion.parametros, modo:'consulta'} : info.accion.parametros)" class="mt-2 inline-block underline">{{ consulta ? 'Consultar política' : info.accion.etiqueta }}</Link>
    </Message>
</template>
