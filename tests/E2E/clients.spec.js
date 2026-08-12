import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test.beforeEach(async ({ page }) => login(page));

test("recorre clientes, expediente e historial crediticio sin errores de consola", async ({ page }) => {
    const assertNoConsoleErrors = await failOnConsoleErrors(page);
    await page.goto("/clientes");
    await expect(page.getByText("Ana", { exact: false }).first()).toBeVisible();

    await page.getByRole("button", { name: /Ver Ana Lucia Garcia Lopez/i }).click();
    await expect(page).toHaveURL(/\/clientes\/\d+/);

    const clientId = page.url().match(/\/clientes\/(\d+)/)?.[1];
    await page.goto(`/clientes/${clientId}/expediente`);
    await expect(page.getByText(/Expediente|Perfil KYC/i).first()).toBeVisible();

    await page.goto("/clientes/historial-crediticio");
    await expect(page.getByText(/Historial/i).first()).toBeVisible();
    assertNoConsoleErrors();
});

test("explica el rechazo del RFC genérico en el formulario", async ({ page }) => {
    await page.goto("/clientes/create");
    await expect(page.getByText(/No se admiten RFC genéricos/i)).toBeVisible();
});

test("abre perfil, seguridad y tokens API", async ({ page }) => {
    for (const url of ["/user/profile", "/user/api-tokens"]) {
        await page.goto(url);
        await expect(page.locator("body")).not.toContainText(/Server Error|Exception/i);
        await expect(page.locator("main, .layout-main, body").first()).toBeVisible();
    }
});
