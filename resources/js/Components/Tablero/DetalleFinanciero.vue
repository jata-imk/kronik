<script setup>
import { Link } from "@inertiajs/vue3";
import { importeMXN, fechaCorta } from "@/Utils/formatosFinancieros";
defineProps({ listado: Object, titulo: String });
defineEmits(["cerrar"]);
</script>

<template>
    <section
        v-if="listado"
        class="k-surface k-panel space-y-4"
        aria-labelledby="detalle-financiero"
    >
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="k-eyebrow">Registros que integran la cifra</p>
                <h2 id="detalle-financiero" class="text-lg font-semibold">
                    {{ titulo }} · {{ listado.total }} registros
                </h2>
            </div>
            <Button
                label="Cerrar detalle"
                severity="secondary"
                outlined
                @click="$emit('cerrar')"
            />
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr>
                        <th class="p-3 text-left">Crédito</th>
                        <th class="p-3 text-left">Fecha</th>
                        <th class="p-3 text-right">Importe · MXN</th>
                    </tr>
                </thead>
                <tbody>
                    <tr
                        v-for="(fila, i) in listado.data"
                        :key="`${fila.credito_id}-${i}`"
                        class="border-t border-surface"
                    >
                        <td class="p-3">
                            <Link
                                class="font-semibold text-primary"
                                :href="route('creditos.show', fila.credito_id)"
                                >{{
                                    fila.folio ?? `Crédito #${fila.credito_id}`
                                }}</Link
                            >
                        </td>
                        <td class="p-3">{{ fechaCorta(fila.fecha) }}</td>
                        <td class="k-financial p-3 text-right">
                            {{ importeMXN(fila.importe) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <p v-if="!listado.data.length" class="text-muted-color">
            No hay registros para este indicador y alcance.
        </p>
        <nav class="flex flex-wrap gap-2" aria-label="Paginación del detalle">
            <Link
                v-for="(link, i) in listado.links"
                :key="i"
                :href="link.url ?? '#'"
                :aria-disabled="!link.url"
                :aria-current="link.active ? 'page' : undefined"
                class="k-action-link"
                :class="{ 'pointer-events-none opacity-50': !link.url }"
                ><span v-html="link.label"
            /></Link>
        </nav>
    </section>
</template>
