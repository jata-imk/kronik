<script>
import PrimeChart from "primevue/chart";
import ChartJS from "chart.js/auto";

// PrimeVue 4 inicia su importación asíncrona antes de comprobar si el canvas
// sigue montado. La visita Inertia puede retirarlo mientras llega ese módulo.
export default {
    name: "GraficaFinanciera",
    extends: PrimeChart,
    methods: {
        initChart() {
            const canvas = this.$refs.canvas;
            if (!canvas?.isConnected) return;
            this.chart?.destroy();
            this.chart = new ChartJS(canvas, {
                type: this.type,
                data: this.data,
                options: this.options,
                plugins: this.plugins,
            });
            this.$emit("loaded", this.chart);
        },
    },
};
</script>
