import { expect, test } from "@playwright/test";
import { login, users } from "./support/auth.js";

test("inicia y cierra sesión con una cuenta activa", async ({ page }) => {
    await login(page);
    await expect(page.getByText("Panel de superusuario", { exact: true })).toBeVisible();

    await page.getByRole("button", { name: "Test User", exact: true }).click();
    await page.getByRole("menuitem", { name: /Cerrar sesión/i }).click();
    await expect(page).toHaveURL(/\/$/);
    await expect(page.getByRole("button", { name: /iniciar sesión/i })).toBeVisible();
});

test("rechaza credenciales incorrectas con un mensaje visible", async ({ page }) => {
    await page.goto("/login");
    await page.locator("#email").fill(users.superAdmin.email);
    await page.locator("#password").fill("incorrecta");
    await page.getByRole("button", { name: /log in/i }).click();

    await expect(page).toHaveURL(/\/login$/);
    await expect(page.locator("form")).toContainText(/credenciales|coinciden|incorrect/i);
});

test("mantiene deshabilitado el autorregistro", async ({ page }) => {
    const response = await page.goto("/register");
    expect(response.status()).toBe(404);
});
