import { test, expect } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test("el operador configura reglas de comunicaciones sin activar tráfico externo", async ({ page }) => {
    const assertConsole = await failOnConsoleErrors(page);
    await login(page);
    await page.goto("/comunicaciones");
    await expect(page.getByRole("heading", { name: "Comunicaciones", exact: true })).toBeVisible();
    await expect(page.getByRole("status")).toContainText("Los envíos externos están desactivados");
    await expect(page.getByRole("heading", { name: "Correo" })).toBeVisible();
    await expect(page.getByRole("heading", { name: "SMS" })).toBeVisible();
    await expect(page.getByRole("heading", { name: "WhatsApp" })).toBeVisible();
    await page.screenshot({ path: "test-results/communications-desktop.png", fullPage: true });

    await page.getByRole("button", { name: "Reglas y plantillas" }).click();
    await expect(page.getByText("Eventos iniciales: pago recibido")).toBeVisible();
    await page.getByRole("textbox", { name: "Texto local" }).fill("Hola {nombre}, registramos tu pago.");
    await expect(page.getByText("Hola María, registramos tu pago.")).toBeVisible();
    await page.getByRole("button", { name: "Guardar regla" }).click();
    await expect(page.getByRole("status").filter({ hasText: "Regla y plantilla actualizadas" })).toBeVisible();
    await page.getByRole("button", { name: "Historial" }).click();
    await expect(page.getByText("Aún no hay envíos en este contexto.")).toBeVisible();
    assertConsole();
});

test("la pantalla de comunicaciones cabe en un móvil y conserva sus cuatro secciones", async ({ page }) => {
    const assertConsole = await failOnConsoleErrors(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);
    await page.goto("/comunicaciones");
    for (const name of ["Canales", "Reglas y plantillas", "Consentimientos", "Historial"]) {
        await page.getByRole("button", { name }).click();
        await expect(page.getByRole("heading", { name: "Comunicaciones", exact: true })).toBeVisible();
        await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    }
    await page.screenshot({ path: "test-results/communications-mobile.png", fullPage: true });
    assertConsole();
});
