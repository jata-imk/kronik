import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test("administra, versiona y previsualiza una plantilla documental", async ({ page }) => {
    test.setTimeout(90_000);
    const assertNoConsoleErrors = await failOnConsoleErrors(page);
    await login(page);
    await page.goto("/plantillas-documentos");

    await expect(page.getByRole("heading", { name: "Documentos y plantillas" })).toBeVisible();
    await expect(page.getByRole("heading", { name: "Consentimiento SIC de muestra" })).toBeVisible();

    await page.getByRole("button", { name: "Nueva plantilla" }).click();
    await page.getByLabel("Nombre *").fill("Aviso documental E2E");
    await page.getByLabel("Clave *").fill("aviso-documental-e2e");
    await page.locator(".ql-editor").fill("Documento sintético para {{cliente.nombre_completo}}.");
    await expect(page.getByText("Variables permitidas")).toBeVisible();
    await page.getByRole("button", { name: "Guardar borrador" }).click();
    await expect(page.getByText("Plantilla creada", { exact: true })).toBeVisible();

    await page.getByLabel("Buscar plantillas").fill("Aviso documental E2E");
    await page.getByRole("button", { name: /Aviso documental E2E/ }).click();
    await expect(page.getByText("Versión histórica protegida")).not.toBeVisible();
    await page.getByRole("button", { name: "Activar versión" }).click();
    await page.getByRole("button", { name: "Activar", exact: true }).click();
    await expect(page.getByText("Versión activada", { exact: true })).toBeVisible();

    await page.getByRole("button", { name: "Previsualizar" }).click();
    await expect(page.getByText("Vista previa con datos sintéticos.")).toBeVisible();
    await expect(page.getByLabel("Previsualización segura de la plantilla")).toContainText("Documento sintético");

    await page.keyboard.press("Escape");

    await page.goto("/clientes/1/expediente");
    await page.getByRole("button", { name: "Documentos" }).click();
    await page.getByRole("button", { name: "Generar documento" }).click();
    await page.getByRole("radio", { name: "Aviso documental E2E · v1" }).check();
    await page.getByRole("button", { name: "Generar PDF" }).click();
    await expect(page.getByText("Generación solicitada", { exact: true })).toBeVisible({ timeout: 60_000 });
    await expect(page.getByText("Aviso documental E2E", { exact: true })).toBeVisible();
    await page.getByRole("button", { name: /Ver aviso-documental-e2e-v1-/ }).click();
    await expect(page.getByText("Visor seguro", { exact: true })).toBeVisible();
    await expect(page.getByTitle(/PDF: aviso-documental-e2e-v1-/)).toBeVisible({ timeout: 20_000 });
    await page.keyboard.press("Escape");

    const mobile = await page.context().newPage();
    const assertMobileConsole = await failOnConsoleErrors(mobile);
    await mobile.setViewportSize({ width: 390, height: 844 });
    await mobile.goto("/plantillas-documentos");
    await expect(mobile.getByRole("heading", { name: "Documentos y plantillas" })).toBeVisible();
    await expect(mobile.getByRole("button", { name: "Nueva plantilla" })).toBeVisible();
    assertMobileConsole();
    await mobile.close();
    assertNoConsoleErrors();
});
