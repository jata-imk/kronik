// Consultation never applies editing defaults or legacy commission conversions.
export function versionParaConsulta(version) {
    return JSON.parse(JSON.stringify({
        ...version,
        periodicidades: version.periodicidades ?? [],
        reglas: version.reglas ?? { metodos_amortizacion: [] },
        comisiones: version.comisiones ?? [],
    }));
}

