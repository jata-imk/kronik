import { expect, test } from "@playwright/test";
import { login, users } from "./support/auth.js";

test("oculta clientes sin permiso y conserva el 403 directo", async ({ page }) => {
    await login(page, users.denied);
    await expect(page.getByText("Clientes", { exact: true })).toHaveCount(0);

    const response = await page.goto("/clientes");
    expect(response.status()).toBe(403);
    await expect(page.getByText(/403|prohibido|forbidden/i).first()).toBeVisible();
});

test("el lector consulta clientes pero no obtiene acciones de escritura", async ({ page }) => {
    await login(page, users.reader);
    await page.goto("/clientes");
    await expect(page.getByText("Clientes", { exact: true }).first()).toBeVisible();
    await expect(page.getByRole("link", { name: /crear cliente/i })).toHaveCount(0);
});

test("un usuario operativo no entra al panel administrativo", async ({ page }) => {
    await login(page, users.editor);
    const response = await page.goto("/admin");
    expect(response.status()).toBe(403);
});
