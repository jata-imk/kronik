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
    await expect(page.getByRole("columnheader", { name: "Saldo inicial" })).toBeVisible();
    await expect(page.getByRole("columnheader", { name: "Comisiones", exact: true })).toBeVisible();
    await expect(page.getByRole("columnheader", { name: "Pagado acum." })).toBeVisible();
    await expect(page.getByRole("columnheader", { name: "Disposición", exact: true })).toBeVisible();
    await expect(page.getByText("Disposición", { exact: true }).last()).toBeVisible();
    await expect(page.getByRole("listitem").filter({ hasText: /^Apertura · \$500\.00 · Pago separado al inicio$/ })).toBeVisible();
    await expect(page.getByRole("listitem").filter({ hasText: /^Administración · \$50\.00$/ }).first()).toBeVisible();
    await expect(page.getByRole("button", { name: "Ver fórmula y sustitución de desarrollo" })).toBeVisible();
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
