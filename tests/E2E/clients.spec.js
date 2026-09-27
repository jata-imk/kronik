import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test.beforeEach(async ({ page }) => login(page));

test("recorre clientes, expediente e historial crediticio sin errores de consola", async ({ page }) => {
    const assertNoConsoleErrors = await failOnConsoleErrors(page);
    await page.goto("/clientes");
    await expect(page.getByText("Ana", { exact: false }).first()).toBeVisible();
    await expect(page.getByRole("button", { name: "Sucursal actual", exact: true })).toHaveAttribute("aria-pressed", "true");

    const viewButton = page.getByRole("button", { name: /Ver Ana Lucia Garcia Lopez/i });
    await viewButton.hover();
    await expect(page.getByText("Ver cliente", { exact: true })).toBeVisible();
    await viewButton.click();
    await expect(page).toHaveURL(/\/clientes\/\d+/);

    const clientId = page.url().match(/\/clientes\/(\d+)/)?.[1];
    for (const url of [`/clientes/${clientId}`, `/clientes/${clientId}/edit`]) {
        await page.goto(url);
        await expect(page.getByRole("button", { name: "Abrir expediente", exact: true })).toHaveCount(0);
        await expect(page.getByRole("button", { name: "Nueva solicitud", exact: true })).toHaveCount(0);
        const solicitud = page.getByRole("menuitem", { name: "Nueva solicitud", exact: true });
        await expect(solicitud).toBeVisible();
        await solicitud.click();
        await expect(page).toHaveURL(new RegExp(`/solicitudes/create\\?cliente_id=${clientId}$`));
        await expect(page.getByText(/Ana.*Garcia.*Lopez/).first()).toBeVisible();
    }
    await page.goto(`/clientes/${clientId}`);
    await page.getByRole("menuitem", { name: /Expediente KYC|Abrir expediente/ }).click();
    await expect(page).toHaveURL(new RegExp(`/clientes/${clientId}/expediente$`));
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
