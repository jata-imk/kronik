import { computed, ref, watch } from "vue";

const formatearFecha = (fecha, opciones) => new Intl.DateTimeFormat("es-MX", { ...opciones, timeZone: "UTC" }).format(fecha);
const fechaCivil = (fecha) => fecha.toISOString().slice(0, 10);

const estados = {
    habil: { texto: "Hábil", severidad: "secondary", icono: "pi-check" },
    descanso_semanal: { texto: "Descanso semanal", severidad: "secondary", icono: "pi-minus" },
    feriado: { texto: "Feriado", severidad: "warn", icono: "pi-flag" },
    apertura: { texto: "Apertura excepcional", severidad: "success", icono: "pi-lock-open" },
    cierre: { texto: "Cierre excepcional", severidad: "danger", icono: "pi-lock" },
    fuera: { texto: "Fuera de cobertura", severidad: "contrast", icono: "pi-question-circle" },
};

export function useCalendarioCrediticio(version, hoy) {
    const seleccionado = ref(hoy);
    const mes = ref(hoy.slice(0, 7));
    const configuracion = computed(() => version.value?.configuracion);

    watch(version, () => {
        const vigente = configuracion.value;
        seleccionado.value = vigente && (hoy < vigente.desde || hoy > vigente.hasta) ? vigente.desde : hoy;
        mes.value = seleccionado.value.slice(0, 7);
    }, { immediate: true });

    const inicioMes = computed(() => new Date(`${mes.value}-01T00:00:00Z`));
    const etiquetaMes = computed(() => formatearFecha(inicioMes.value, { month: "long", year: "numeric" }));

    function clasificar(civil, fecha) {
        const datos = configuracion.value;
        if (!datos || civil < datos.desde || civil > datos.hasta) return { tipo: "fuera", motivo: "La financiera no ha revisado esta fecha en esta versión." };
        const excepcion = datos.excepciones.find((dia) => dia.fecha === civil);
        if (excepcion) return { tipo: excepcion.habil ? "apertura" : "cierre", motivo: excepcion.nombre };
        const feriado = datos.feriados.find((dia) => dia.fecha === civil);
        if (feriado) return { tipo: "feriado", motivo: feriado.nombre };
        return datos.semana_habil.includes(fecha.getUTCDay() || 7)
            ? { tipo: "habil", motivo: "Día hábil según la semana configurada." }
            : { tipo: "descanso_semanal", motivo: "Día de descanso según la semana configurada." };
    }

    const celdas = computed(() => {
        const comienzo = inicioMes.value;
        const espacio = (comienzo.getUTCDay() + 6) % 7;
        const dias = new Date(Date.UTC(comienzo.getUTCFullYear(), comienzo.getUTCMonth() + 1, 0)).getUTCDate();
        return Array.from({ length: espacio + dias }, (_, indice) => {
            if (indice < espacio) return { clave: `espacio-${indice}` };
            const fecha = new Date(comienzo);
            fecha.setUTCDate(indice - espacio + 1);
            const civil = fechaCivil(fecha);
            const estado = clasificar(civil, fecha);
            return { clave: civil, fecha: civil, dia: fecha.getUTCDate(), ...estado, ...estados[estado.tipo] };
        });
    });

    const detalle = computed(() => {
        const fecha = new Date(`${seleccionado.value}T00:00:00Z`);
        const estado = clasificar(seleccionado.value, fecha);
        return { fecha: formatearFecha(fecha, { dateStyle: "full" }), ...estado, ...estados[estado.tipo] };
    });

    function moverMes(delta) {
        const fecha = new Date(inicioMes.value);
        fecha.setUTCMonth(fecha.getUTCMonth() + delta);
        mes.value = fechaCivil(fecha).slice(0, 7);
        seleccionado.value = fechaCivil(fecha);
    }

    return { seleccionado, etiquetaMes, celdas, detalle, moverMes, leyenda: Object.values(estados) };
}
