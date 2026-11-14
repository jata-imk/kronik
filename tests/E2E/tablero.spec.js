import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login, users } from "./support/auth.js";

test("tablero conserva diseño, consulta y detalles en escritorio, móvil y oscuro", async ({ page }, testInfo) => {
    const verificarConsola = await failOnConsoleErrors(page);
    await login(page);
    await expect(page.getByRole("heading", { name: "Cartera y movimientos" })).toBeVisible();
    await expect(page.getByText("Capital pendiente de recuperar", { exact: true })).toBeVisible();
    if (process.env.E2E_TABLERO_FIXTURES === "true") {
        await expect(page.getByRole("button", { name: /Capital pendiente de recuperar/ })).toContainText("$20,880.00");
        await expect(page.getByRole("button", { name: /Cobros registrados.*Ver detalle/s })).toContainText("$3,120.00");
    }
    await page.getByRole("button", { name: /Cobros registrados.*Ver detalle/s }).click();
    await expect(page.getByRole("heading", { name: /Cobros registrados ·/ })).toBeVisible();
    await expect(page).toHaveURL(/detalle=cobros/);
    await page.reload();
    await expect(page.getByRole("heading", { name: /Cobros registrados ·/ })).toBeVisible();
    await page.getByRole("button", { name: "Cerrar detalle" }).click();
    await expect(page.getByRole("heading", { name: "Cartera y movimientos" })).toBeVisible();
    for (const width of [1440, 768, 390, 320]) {
        await page.setViewportSize({ width, height: 1000 });
        await expect(page.getByRole("button", { name: "Consultar", exact: true })).toBeVisible();
        await expect.poll(async () => page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)).toBeLessThanOrEqual(1);
        await page.screenshot({ path: testInfo.outputPath(`tablero-${width}.png`), fullPage: true });
    }
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.getByRole("button", { name: "Cambiar tema", exact: true }).click();
    await expect(page.locator("html")).toHaveClass(/app-dark/);
    await page.evaluate(async () => {
        await Promise.all(document.getAnimations().map((animation) => animation.finished.catch(() => {})));
    });
    await expect.poll(async () => page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)).toBeLessThanOrEqual(1);
    await page.screenshot({ path: testInfo.outputPath("tablero-oscuro.png") });
    await page.getByLabel("Movimientos desde", { exact: true }).focus();
    await expect(page.getByLabel("Movimientos desde", { exact: true })).toBeFocused();
    await page.getByRole("button", { name: "Personalizar apariencia", exact: true }).click();
    await page.getByRole("button", { name: "Color de énfasis indigo", exact: true }).click();
    await page.getByRole("button", { name: "Color de fondo zinc", exact: true }).click();
    await expect(page.getByRole("button", { name: "Color de énfasis indigo", exact: true })).toHaveAttribute("aria-pressed", "true");
    await page.getByRole("heading", { name: "Cartera y movimientos" }).click();
    await expect(page.locator(".config-panel")).toBeHidden();
    await page.evaluate(() => { document.documentElement.style.fontSize = "200%"; });
    await expect(page.getByRole("button", { name: "Consultar", exact: true })).toBeVisible();
    await expect.poll(async () => page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth)).toBeLessThanOrEqual(1);
    await page.screenshot({ path: testInfo.outputPath("tablero-zoom-tema.png"), fullPage: true });
    verificarConsola();
});

test("usuario sin lectura financiera no recibe indicadores ni controles de dinero", async ({ page }) => {
    await login(page, users.denied);
    await expect(page.getByRole("heading", { name: "Cartera y movimientos" })).toHaveCount(0);
    await expect(page.getByLabel("Corte de saldos")).toHaveCount(0);
});
