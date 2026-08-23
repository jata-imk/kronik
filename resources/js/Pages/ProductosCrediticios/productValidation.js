export const productErrorTabs = {
    general: ["clave", "nombre", "descripcion", "version.cat_"],
    condiciones: [
        "version.monto_",
        "version.tasa_",
        "version.dias_",
        "version.periodicidades",
    ],
    reglas: ["version.reglas"],
    comisiones: ["version.comisiones"],
};

export const tabForProductError = (key) =>
    Object.keys(productErrorTabs).find((tab) =>
        productErrorTabs[tab].some((prefix) => key.startsWith(prefix)),
    ) ?? "general";

export const countProductTabErrors = (errors, tab) =>
    Object.keys(errors).filter((key) =>
        productErrorTabs[tab].some((prefix) => key.startsWith(prefix)),
    ).length;

export const formatMoneyWithCents = (value) =>
    new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
