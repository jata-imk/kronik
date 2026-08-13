import { expect, test } from "@playwright/test";
import { login } from "./support/auth.js";

test("recorre catálogo, versiones y simulador de crédito simple", async ({ page }) => {
    await login(page);
    await page.goto("/productos-crediticios");

    await expect(page.getByRole("heading", { name: "Productos crediticios" })).toBeVisible();
    await expect(page.getByText("Crédito Simple Esencial", { exact: true }).first()).toBeVisible();
    await expect(page.getByText("Activa", { exact: true }).first()).toBeVisible();

    await page.getByRole("button", { name: "Simular", exact: true }).click();
    await expect(page.getByText("Simulador de crédito simple", { exact: true })).toBeVisible();
    await page.getByRole("button", { name: "Calcular escenario" }).click();

    await expect(page.getByText("CAT informativo", { exact: true })).toBeVisible();
    await expect(page.getByText(/Para fines informativos y de comparación/)).toBeVisible();
    await expect(page.getByRole("columnheader", { name: "Capital" })).toBeVisible();
});

test("catálogo es utilizable en una pantalla móvil", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);
    await page.goto("/productos-crediticios");

    await expect(page.getByRole("heading", { name: "Productos crediticios" })).toBeVisible();
    await expect(page.getByText("Versiones y vigencia")).toBeVisible();
    await page.getByRole("button", { name: "Simular", exact: true }).click();
    await expect(page.getByText("Simulador de crédito simple", { exact: true })).toBeVisible();
});
