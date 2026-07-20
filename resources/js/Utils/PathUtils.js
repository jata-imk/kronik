export function isRouteMatching(pattern) {
    const path = window.location.pathname;
    return pattern.test(path);
}

export function laravelPatternToRegex(pattern) {
    // Escapa los `/` y reemplaza cada {param} por un patrón numérico (\d+) o genérico ([^/]+)
    const regexString = pattern
        .replace(/[-\/\\^$+?.()|[\]{}]/g, "\\$&") // Escape de caracteres especiales de RegExp
        .replace(/\\{[^}]+\\}/g, "[^/]+"); // Convierte {param} en patrón genérico

    return new RegExp(`/${regexString}$`);
}
