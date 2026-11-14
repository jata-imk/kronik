import { onUnmounted, ref, watch, nextTick } from "vue";
import { useLayout } from "@sakai-vue/layout/composables/layout";

export function usePaletaFinanciera() {
    const { layoutConfig } = useLayout();
    const paleta = ref({});
    let activa = true;
    onUnmounted(() => {
        activa = false;
    });
    watch(
        () => layoutConfig,
        async () => {
            await nextTick();
            await new Promise((resolve) => requestAnimationFrame(resolve));
            if (!activa) return;
            const css = getComputedStyle(document.documentElement);
            const token = (name, fallback) =>
                css.getPropertyValue(name).trim() || fallback;
            paleta.value = {
                principal: token("--p-primary-color", "#10b981"),
                secundario: token("--k-chart-secundario", "#8b5cf6"),
                advertencia: token("--k-chart-advertencia", "#f59e0b"),
                peligro: token("--k-chart-peligro", "#ef4444"),
                info: token("--k-chart-info", "#0ea5e9"),
                texto: token("--p-text-color", "#0f172a"),
                tenue: token("--p-text-muted-color", "#64748b"),
                borde: token("--p-content-border-color", "#e2e8f0"),
            };
        },
        { deep: true, immediate: true },
    );
    return paleta;
}
