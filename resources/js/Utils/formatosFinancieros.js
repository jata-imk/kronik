const enteros = new Intl.NumberFormat("es-MX", { maximumFractionDigits: 0 });

export function importeMXN(valor) {
    if (valor === null || valor === undefined) return "Sin dato";
    const texto = typeof valor === "number" ? valor.toFixed(2) : String(valor);
    const partes = /^(-?)(\d+)(?:\.(\d+))?$/.exec(texto);
    if (!partes) return "Sin dato";
    return `${partes[1]}$${enteros.format(BigInt(partes[2]))}.${(partes[3] ?? "").padEnd(2, "0").slice(0, 2)}`;
}

export function fechaCorta(valor) {
    if (!valor) return "Sin fecha";
    const [anio, mes, dia] = String(valor).slice(0, 10).split("-");
    return `${dia}/${mes}/${anio}`;
}
