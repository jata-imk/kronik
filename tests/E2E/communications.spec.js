import { test, expect } from "@playwright/test";
import { createNotification } from "./support/notifications.js";
import { failOnConsoleErrors, login } from "./support/auth.js";

test("el operador configura reglas de comunicaciones sin activar tráfico externo", async ({ page }) => {
    const assertConsole = await failOnConsoleErrors(page);
    await login(page);
    const key = "regla-correo-" + Date.now();
    await createNotification(page, { key, channel: "email" });
    await page.goto("/comunicaciones");
    await expect(page.getByRole("heading", { name: "Comunicaciones", exact: true })).toBeVisible();
    await expect(page.getByRole("status")).toContainText("Los envíos externos están desactivados");
    await expect(page.getByRole("heading", { name: "Correo" })).toBeVisible();
    await expect(page.getByRole("heading", { name: "SMS" })).toBeVisible();
    await expect(page.getByRole("heading", { name: "WhatsApp" })).toBeVisible();
    await page.getByRole("combobox", { name: "Canal", exact: true }).selectOption("sms");
    await expect(page.getByTestId("sms-environment")).toContainText("se configuran en el entorno");
    await expect(page.locator("textarea")).toHaveCount(0);
    await expect(page.getByText("Los envíos SMS están desactivados en este entorno.", { exact: true })).toBeVisible();
    await expect(page.getByRole("button", { name: "Comprobar conexión técnica" })).toBeDisabled();
    await page.getByRole("combobox", { name: "Elegibilidad", exact: true }).selectOption("testing");
    await page.getByRole("button", { name: "Guardar canal" }).click();
    await expect(page.getByRole("status").filter({ hasText: "Canal actualizado." })).toBeVisible();
    await expect(page.getByTestId("sms-environment")).toBeVisible();
    await page.evaluate(() => window.scrollTo(0, 0));
    await page.screenshot({ path: "test-results/communications-desktop.png", fullPage: true });

    await page.getByRole("button", { name: "Reglas y plantillas" }).click();
    await expect(page.getByText("Eventos iniciales: pago recibido")).toBeVisible();
    await page.getByRole("combobox", { name: "Plantilla global" }).selectOption({ label: "QA " + key + " · V1" });
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
    await page.getByRole("button", { name: "Canales", exact: true }).click();
    await page.getByRole("combobox", { name: "Canal", exact: true }).selectOption("sms");
    await expect(page.getByTestId("sms-environment")).toBeVisible();
    await expect(page.locator("textarea")).toHaveCount(0);
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
    await page.screenshot({ path: "test-results/communications-mobile.png", fullPage: true });
    assertConsole();
});
