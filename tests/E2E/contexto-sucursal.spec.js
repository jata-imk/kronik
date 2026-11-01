import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test.use({ screenshot: "on" });

for (const viewport of [{ width: 1280, height: 900 }, { width: 390, height: 844 }]) {
    test.describe(`Sucursal ${viewport.width}px`, () => {
        test.use({ viewport });
        test("cambiar sucursal limpia el alcance anterior y la bandeja institucional es explícita", async ({ page }) => {
            await login(page);
            const assertNoConsoleErrors = await failOnConsoleErrors(page);
            async function switchBranch(name) {
                if (viewport.width >= 1024) {
                    await page.locator("button:has(.pi-map-marker):visible").first().click();
                } else {
                    await page.getByRole("button", { name: "Opciones de usuario", exact: true }).filter({ visible: true }).click();
                }
                const changed = page.waitForResponse(r => r.url().endsWith("/current-sucursal") && r.request().method() === "PUT");
                await page.getByText(name, { exact: true }).filter({ visible: true }).last().click();
                await changed;
            }
            await switchBranch("Matriz");
            await page.goto("/solicitudes/create");
            await page.getByLabel("Cliente (obligatorio)").fill("Ma");
            await page.getByRole("option").first().click();
            await page.getByRole("button", { name: "Guardar borrador", exact: true }).click();
            await expect(page).toHaveURL(/\/solicitudes\/\d+$/);
            const detail = new URL(page.url()).pathname;
            await page.goto("/solicitudes?scope=all&page=7");
            await switchBranch("Sucursal Norte");
            await expect(page).toHaveURL(/\/solicitudes$/);
            await expect(page.locator(`a[href$="${detail}"]`)).toHaveCount(0);
            await page.getByLabel("Alcance", { exact: true }).click();
            await page.getByRole("option", { name: "Consulta institucional", exact: true }).click();
            await page.getByRole("button", { name: "Buscar", exact: true }).click();
            await expect(page.locator(`a[href$="${detail}"]:visible`).first()).toBeVisible();
            assertNoConsoleErrors();
        });

    });
}


test("otra pestaña y navegación histórica abandonan formularios y alcance del contexto anterior", async ({ page, context }) => {
    await login(page);
    const assertNoConsoleErrors = await failOnConsoleErrors(page);
    async function switchBranch(name) {
        await page.locator("button:has(.pi-map-marker):visible").first().click();
        await page.getByText(name, { exact: true }).filter({ visible: true }).last().click();
        await expect(page.locator("button:has(.pi-map-marker):visible").first()).toContainText(name);
    }
    await switchBranch("Matriz");
    const other = await context.newPage();
    const assertOtherConsole = await failOnConsoleErrors(other);
    await other.goto("/solicitudes/create");
    await expect(other.getByRole("button", { name: "Guardar borrador", exact: true })).toBeVisible();
    await page.bringToFront();
    await page.goto("/solicitudes?scope=all&page=7");
    await switchBranch("Sucursal Norte");
    await other.bringToFront();
    await expect(other).toHaveURL(/\/solicitudes$/);
    await expect(other.getByRole("button", { name: "Guardar borrador", exact: true })).toHaveCount(0);
    await other.goto("/solicitudes?scope=all&page=4");
    await other.goto("/dashboard");
    await page.bringToFront();
    await switchBranch("Matriz");
    await other.bringToFront();
    await expect(other.locator("button:has(.pi-map-marker):visible").first()).toContainText("Matriz");
    await other.goBack();
    await expect(other).toHaveURL(/\/solicitudes$/);
    await expect(other.getByLabel("Alcance", { exact: true })).toContainText("Sucursal activa");
    assertNoConsoleErrors(); assertOtherConsole();
    await other.close();
});
