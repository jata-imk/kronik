export const productErrorTabs = {
    general: ["clave", "nombre", "descripcion", "version.cat_"],
    condiciones: [
        "version.monto_",
        "version.tasa_",
        "version.dias_",
        "version.politica_mora",
        "version.periodicidades",
    ],
    reglas: ["version.reglas"],
    comisiones: ["version.comisiones"],
    fiscalidad: ["version.fiscalidad"],
};

export const tabForProductError = (key) =>
    /^(version\.fiscalidad|version\.comisiones\.\d+\.fiscalidad)(\.|$)/.test(
        key,
    )
        ? "fiscalidad"
        : (Object.keys(productErrorTabs).find((tab) =>
              productErrorTabs[tab].some((prefix) => key.startsWith(prefix)),
          ) ?? "general");

export const countProductTabErrors = (errors, tab) =>
    Object.keys(errors).filter((key) => tabForProductError(key) === tab).length;

export const formatMoneyWithCents = (value) =>
    new Intl.NumberFormat("es-MX", {
        style: "currency",
        currency: "MXN",
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(Number(value ?? 0));
