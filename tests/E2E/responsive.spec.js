import { expect, test } from "@playwright/test";
import { login } from "./support/auth.js";

for (const viewport of [
    { name: "escritorio", width: 1440, height: 900 },
    { name: "móvil", width: 390, height: 844 },
]) {
    test(`mantiene navegables las tablas clave en ${viewport.name}`, async ({ page }) => {
        await page.setViewportSize(viewport);
        await login(page);

        for (const { url, filters } of [
            { url: "/admin/users", filters: ["Nombre o correo", "Equipo", "Sucursal"] },
            { url: "/admin/teams", filters: ["Nombre", "Responsable", "Resumen"] },
            { url: "/admin/sucursales", filters: ["Nombre, teléfono o correo", "Domicilio", "Horario", "Resumen"] },
            { url: "/clientes", filters: ["Buscar por nombre", "Buscar por email", "Buscar por teléfono", "Buscar por sucursal"] },
        ]) {
            await page.goto(url);
            await expect(page.locator("table")).toBeVisible();
            for (const placeholder of filters) {
                await expect(page.getByPlaceholder(placeholder, { exact: true })).toBeVisible();
            }
            if (url === "/admin/teams") {
                await expect(page.getByLabel("Filtrar por estado")).toBeVisible();
            }
            if (url === "/admin/sucursales") {
                await expect(page.getByLabel("Filtrar sucursales por estado")).toBeVisible();
            }
            await expect(page.locator("body")).not.toContainText(/Server Error|Exception/i);
        }
    });
}
