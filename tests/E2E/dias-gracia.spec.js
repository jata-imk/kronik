import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test("Empresa prellena nuevos productos sin alterar versiones guardadas", async ({ page }, testInfo) => {
    test.setTimeout(180_000);
    const assertConsole = await failOnConsoleErrors(page);
    await login(page);
    const companyUrl = "/admin/configuracion-empresa";
    await page.goto(companyUrl);
    const original = await page.locator("#dias_gracia").inputValue();
    async function setGrace(value) {
        await page.goto(companyUrl);
        await page.locator("#dias_gracia").press("ControlOrMeta+A");
        await page.locator("#dias_gracia").press("Backspace");
        if (value) await page.locator("#dias_gracia").pressSequentially(value);
        await page.locator("#dias_gracia").press("Tab");
        const saved = page.waitForResponse(r => r.request().method() === "PUT" && r.url().includes(companyUrl));
        await page.getByRole("button", { name: "Guardar configuración", exact: true }).click();
        expect((await saved).status()).toBeLessThan(400);
        await expect(page.getByRole("button", { name: "Guardar configuración", exact: true })).toBeEnabled();
        await expect(page.getByText(/validation\./)).toHaveCount(0);
    }
    try {
        await setGrace("0");
        await page.goto("/productos-crediticios");
        await page.getByRole("button", { name: "Nuevo producto", exact: true }).click();
        const dialog = page.locator("#product-editor");
        await dialog.getByRole("textbox", { name: "Clave *", exact: true }).fill("GRACIA-E2E");
        await dialog.getByLabel("Nombre comercial").fill("Gracia E2E");
        await dialog.getByRole("tab", { name: "Montos y tasas", exact: true }).click();
        await expect(dialog.locator("#grace-days input")).toHaveValue("0");
        await dialog.locator("#grace-days input").press("ControlOrMeta+A");
        await dialog.locator("#grace-days input").pressSequentially("7");
        await dialog.locator("#grace-days input").press("Tab");
        await dialog.getByRole("combobox", { name: "Intereses durante la mora", exact: true }).click();
        await page.getByRole("option", { name: "Moratorio sustituye al ordinario", exact: true }).click();
        await dialog.getByRole("button", { name: "Guardar borrador", exact: true }).click();
        await expect(dialog).toHaveCount(0);
        const productUrl = page.url();
        await setGrace("9");
        await page.goto(productUrl);
        await page.getByRole("button", { name: "Editar versión 1", exact: true }).click();
        await dialog.getByRole("tab", { name: "Montos y tasas", exact: true }).click();
        await expect(dialog.locator("#grace-days input")).toHaveValue("7");
        await page.screenshot({ path: testInfo.outputPath("gracia-borrador-conservado.png"), fullPage: true });
        await page.goto(productUrl);
        await page.getByRole("button", { name: "Nuevo producto", exact: true }).click();
        await dialog.getByRole("tab", { name: "Montos y tasas", exact: true }).click();
        await expect(dialog.locator("#grace-days input")).toHaveValue("9");
        await setGrace("");
        await page.goto("/productos-crediticios");
        await page.getByRole("button", { name: "Nuevo producto", exact: true }).click();
        await dialog.getByRole("tab", { name: "Montos y tasas", exact: true }).click();
        await expect(dialog.locator("#grace-days input")).toHaveValue("3");
        await page.goto(`${companyUrl}?modo=consulta`);
        await expect(page.locator("#dias_gracia")).toBeDisabled();
        const writes = [];
        page.on("request", r => { if (["POST", "PUT", "PATCH", "DELETE"].includes(r.method())) writes.push(r.url()); });
        await page.getByLabel("Razón social", { exact: true }).press("Enter");
        expect(writes).toEqual([]);
        await page.setViewportSize({ width: 390, height: 844 });
        await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        await page.screenshot({ path: testInfo.outputPath("gracia-empresa-consulta-movil.png"), fullPage: true });
        assertConsole();
    } finally {
        await setGrace(original);
    }
});
