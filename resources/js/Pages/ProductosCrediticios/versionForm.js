// Consultation never applies editing defaults or legacy commission conversions.
export function versionParaConsulta(version) {
    return JSON.parse(
        JSON.stringify({
            ...version,
            periodicidades: version.periodicidades ?? [],
            reglas: version.reglas ?? { metodos_amortizacion: [] },
            comisiones: version.comisiones ?? [],
        }),
    );
}

// Only called by an explicit editing action; never from selection/read-only watchers.
export function cambiarMetodos(reglas, seleccion, combinaciones) {
    const anterior = reglas.metodos_amortizacion ?? [];
    const entraBullet =
        seleccion.includes("bullet") && !anterior.includes("bullet");
    const saleBullet =
        anterior.includes("bullet") &&
        seleccion.some((value) => value !== "bullet");
    const metodos = entraBullet
        ? ["bullet"]
        : saleBullet
          ? seleccion.filter((value) => value !== "bullet")
          : seleccion;
    const bullet = metodos.includes("bullet");
    const combinacion = combinaciones[bullet ? "bullet" : "actuales"];
    const resultado = { ...reglas, metodos_amortizacion: metodos };
    resultado.base_ordinaria ??= "saldo_insoluto";
    if (!combinacion.bases_moratorias.includes(resultado.base_moratoria))
        resultado.base_moratoria = null;
    if (
        !combinacion.aplicaciones_prepago.includes(resultado.aplicacion_prepago)
    )
        resultado.aplicacion_prepago = combinacion.aplicaciones_prepago[0];
    if (!bullet) delete resultado.politica_interes_bullet;
    return resultado;
}
