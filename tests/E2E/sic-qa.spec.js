import { test, expect } from "@playwright/test";
import { login, failOnConsoleErrors } from "./support/auth.js";

test("SIC displays all three services with provenance and report charts", async ({ page }) => {
    test.skip(process.env.E2E_SIC_FIXTURES !== "true", "Requires explicitly isolated synthetic SIC fixtures.");
    test.setTimeout(90000);
    const assertConsole = await failOnConsoleErrors(page);
    await login(page);
    page.setDefaultTimeout(15000);
    // Seeded Ana is the first client. This isolated fixture never uses installation data.
    for (const service of ["FICO Score v2", "Fintech Score", "Reporte de Crédito + FICO"]) {
        await page.goto("/consultas-sic/nueva/1", { waitUntil: "domcontentloaded" });
        await page.getByLabel("Servicio", { exact: false }).click();
        await page.getByRole("option", { name: service, exact: true }).click();
        if (service === "Fintech Score") {
            await expect(page.getByText("Fintech usa un escenario de simulación del proveedor.", { exact: false })).toBeVisible();
        }
        await page.getByLabel("Consentimiento", { exact: false }).click();
        await page.getByRole("option").first().click();
        await page.getByRole("checkbox").check();
        await page.getByRole("button", { name: "Consultar en sandbox" }).click();
        await expect(page).toHaveURL(/\/consultas-sic\/\d+$/);
        await expect(page.getByText("680", { exact: true })).toBeVisible();
        await expect(page.getByText("Resultado demostrativo.", { exact: false })).toBeVisible();
        if (service === "Reporte de Crédito + FICO") {
            await expect(page.getByRole("heading", { name: "Saldos reportados · MXN", exact: true })).toBeVisible();
            await expect(page.locator("canvas")).toHaveCount(5);
            await page.setViewportSize({ width: 390, height: 844 });
            await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        }
    }
    assertConsole();
});
