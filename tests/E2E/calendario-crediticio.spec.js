import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test("publica la plantilla y compara vencimientos con teclado y en móvil", async ({ page }, testInfo) => {
    const revisarConsola = await failOnConsoleErrors(page);
    await login(page);
    await page.goto("/productos-crediticios/calendario");
    await expect(page.getByRole("heading", { name: "Calendario crediticio", exact: true })).toBeVisible();
    await page.getByRole("button", { name: "Nuevo borrador" }).click();
    await page.getByRole("button", { name: "Cargar plantilla CNBV 2026" }).click();
    await page.getByLabel("Cobertura hasta").fill("2027-01-01");
    await page.getByLabel("Cobertura hasta").press("Tab");
    await page.getByRole("button", { name: "Guardar borrador" }).click();
    await expect(page).toHaveURL(/calendario\/\d+$/);
    await page.getByRole("button", { name: "Publicar", exact: true }).click();
    await page.getByRole("button", { name: "Publicar versión" }).click();
    await expect(page.getByText("Vigente", { exact: true }).first()).toBeVisible();
    await expect(page.getByText(/Cobertura revisada.*2027-01-01/)).toBeVisible();
    await page.getByRole("tab", { name: "Ejemplos" }).focus();
    await page.keyboard.press("Enter");
    await page.getByRole("button", { name: "Calcular ejemplo" }).click();
    await expect(page.getByRole("button", { name: /2026-11-14 → 2026-11-17/ })).toBeVisible();
    await expect(page.getByText("2026-11-21", { exact: true })).toBeVisible();
    await page.screenshot({ path: testInfo.outputPath("calendario-ejemplo-claro.png"), fullPage: true });

    await page.setViewportSize({ width: 390, height: 844 });
    await page.getByRole("tab", { name: "Calendario", exact: true }).click();
    await page.getByRole("button", { name: "Cambiar tema", exact: true }).click();
    await expect(page.locator("html")).toHaveClass(/app-dark/);
    await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth - innerWidth)).toBeLessThanOrEqual(1);
    await page.screenshot({ path: testInfo.outputPath("calendario-movil-oscuro.png"), fullPage: true });
    revisarConsola();
});
