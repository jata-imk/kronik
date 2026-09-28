export const emptyTaxConcept = () => ({
    tratamiento: "no_definido",
    tasa: null,
    base: null,
});
export const normalizeTaxPolicy = (value) => ({
    uso: value?.uso ?? "prueba",
    referencia: value?.referencia ?? "",
    ordinario: { ...emptyTaxConcept(), ...value?.ordinario },
    moratorio: { ...emptyTaxConcept(), ...value?.moratorio },
});
export const taxConceptLabel = (value) =>
    ({
        no_definido: "Sin definir",
        gravado: "Gravado",
        exento: "Exento",
        no_causa: "No causa impuesto",
    })[value?.tratamiento] ?? "Sin definir";
