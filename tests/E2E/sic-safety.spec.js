import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test("navega a productos y a un historial SIC sin datos demostrativos", async ({ page }) => {
    await login(page);
    const assertNoConsoleErrors = await failOnConsoleErrors(page);
    await page.locator(".layout-menu").getByText("Productos crediticios", { exact: true }).click();
    await expect(page).toHaveURL(/productos-crediticios/);
    await page.goto("/clientes/historial-crediticio");
    await expect(page.getByRole("heading", { name: "Consultas SIC", exact: true })).toBeVisible();
    await expect(page.getByText(/Histórico heredado/)).toBeVisible();
    await expect(page.getByText(/CapitalOne|Capital One|Payment History/)).toHaveCount(0);
    await page.getByRole("link", { name: "Disponibilidad de consultas" }).click();
    await expect(page.getByText(/No se realizó ninguna consulta/)).toBeVisible();
    await expect(page.getByRole("button", { name: /consultar|enviar consulta/i })).toHaveCount(0);
    assertNoConsoleErrors();
});
