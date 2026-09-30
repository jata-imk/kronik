import { expect, test } from "@playwright/test";
import { login } from "./support/auth.js";

test("configura fiscalidad explícita y conserva la consulta de la versión", async ({ page }) => {
    await login(page);
    await page.goto("/productos-crediticios");
    await page.getByRole("button", { name: "Nuevo producto", exact: true }).click();
    await page.getByRole("textbox", { name: "Clave *", exact: true }).fill("FISCAL-E2E");
    await page.getByLabel("Nombre comercial").fill("Producto fiscal QA");
    await page.getByRole("tab", { name: "Montos y tasas", exact: true }).click();
    await page.getByRole("combobox", { name: "Intereses durante la mora", exact: true }).click();
    await page.getByRole("option", { name: "Moratorio sustituye al ordinario", exact: true }).click();
    await expect(page.getByText(/Con A, el ordinario continúa durante la gracia/)).toBeVisible();
    await page.getByRole("tab", { name: "Fiscalidad", exact: true }).click();
    await expect(page.getByText("Cada concepto, su tratamiento")).toBeVisible();
    await page.getByRole("combobox", { name: "Tratamiento · Interés ordinario", exact: true }).click();
    await page.getByRole("option", { name: "Gravado", exact: true }).click();
    await page.getByRole("button", { name: "Guardar borrador" }).click();
    await expect(page.locator('[aria-invalid="true"]').first()).toBeVisible();
    await expect(page.getByText(/validation\./)).toHaveCount(0);
    await page.getByLabel("Tasa de impuesto (%) · Interés ordinario", { exact: true }).fill("16.12345678");
    await page.getByRole("combobox", { name: "Base de impuesto · Interés ordinario", exact: true }).click();
    await page.getByRole("option", { name: "Importe íntegro de este concepto" }).click();
    await page.getByRole("button", { name: "Guardar borrador" }).click();
    await expect(page.getByRole("dialog")).toHaveCount(0);
    await page.getByRole("button").filter({ hasText: "Producto fiscal QA" }).click();
    await page.getByRole("button", { name: "Ver política de atraso versión 1", exact: true }).click();
    await expect(page.getByText("A · Gracia efectiva (recomendada)", { exact: true })).toBeVisible();
    await expect(page.getByText("Moratorio sustituye al ordinario", { exact: true })).toBeVisible();
    await expect.poll(() => page.locator('.p-drawer').evaluate(el => {
        const rect = el.getBoundingClientRect();
        return rect.left >= 0 && rect.right <= innerWidth + 1;
    })).toBe(true);
    await page.screenshot({ path: test.info().outputPath("politica-atraso-desktop.png"), fullPage: true, animations: "disabled" });
    await page.keyboard.press("Escape");
    await page.getByRole("button", { name: "Ver fiscalidad versión 1", exact: true }).click();
    await expect(page.getByLabel("Tasa de impuesto (%) · Interés ordinario", { exact: true })).toHaveValue("16.12345678");
    await expect(page.getByLabel("Tasa de impuesto (%) · Interés ordinario", { exact: true })).toBeDisabled();
    await expect(page.getByText(/El simulador puede aplicar esta configuración/)).toBeVisible();
    await page.setViewportSize({ width: 390, height: 844 });
    await expect(page.getByText("Cada concepto, su tratamiento")).toBeVisible();
    expect(await page.locator('.p-drawer-content').evaluate(el => el.scrollWidth <= el.clientWidth + 1)).toBe(true);
    await page.keyboard.press('Escape');
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.getByRole("button", { name: "Simular", exact: true }).click();
    await page.getByLabel("Incluir impuestos proyectados", { exact: true }).check();
    await page.getByRole("button", { name: "Calcular escenario" }).click();
    await expect(page.getByRole("columnheader", { name: "Impuestos", exact: true })).toBeVisible();
    await expect(page.getByText("Configuración de prueba / QA", { exact: true })).toBeVisible();
    await page.getByText("Ver desglose fiscal por periodo y concepto", { exact: true }).click();
    await expect(page.getByText(/16.12345678 %/).first()).toBeVisible();
    await page.getByLabel("Incluir impuestos proyectados", { exact: true }).uncheck();
    await expect(page.getByRole("columnheader", { name: "Impuestos", exact: true })).toHaveCount(0);
    await page.getByRole("button", { name: "Calcular escenario" }).click();
    await expect(page.getByText(/Escenario anterior a impuestos. No significa exención/)).toBeVisible();
});

test("recorre catálogo, versiones y simulador de crédito simple", async ({ page }) => {
    await login(page);
    await page.goto("/productos-crediticios");

    await expect(page.getByRole("heading", { name: "Productos crediticios" })).toBeVisible();
    await expect(page.getByText("Crédito Simple Esencial", { exact: true }).first()).toBeVisible();
    await expect(page.getByText("Activa", { exact: true }).first()).toBeVisible();

    await page.getByRole("button", { name: "Simular", exact: true }).click();
    await expect(page.getByText("Simulador de crédito simple", { exact: true })).toBeVisible();
    await expect(page.getByText("Comisiones del escenario", { exact: true })).toBeVisible();
    await page.getByText("Asistencia opcional", { exact: true }).click();
    await page.getByRole("button", { name: "Calcular escenario" }).click();

    await expect(page.getByText("CAT base del producto", { exact: true })).toBeVisible();
    await expect(page.getByText(/La tabla incluye 1 comisión\(es\) opcional/)).toBeVisible();
    await expect(page.getByRole("columnheader", { name: "Saldo inicial" })).toBeVisible();
    await expect(page.getByRole("columnheader", { name: "Comisiones", exact: true })).toBeVisible();
    await expect(page.getByRole("columnheader", { name: "Pagado acum." })).toBeVisible();
    await expect(page.getByRole("columnheader", { name: "Disposición", exact: true })).toBeVisible();
    await expect(page.getByText("Disposición", { exact: true }).last()).toBeVisible();
    await expect(page.getByRole("listitem").filter({ hasText: /^Apertura · \$500\.00 · Pago separado al inicio$/ })).toBeVisible();
    await expect(page.getByRole("listitem").filter({ hasText: /^Administración · \$50\.00$/ }).first()).toBeVisible();
    await expect(page.getByRole("listitem").filter({ hasText: /^Asistencia opcional · \$250\.00 · Pago separado al inicio$/ })).toBeVisible();
    await page.getByRole("button", { name: "Ver fórmula y sustitución de desarrollo" }).click();
    await expect(page.getByText("Significado de los símbolos", { exact: true })).toBeVisible();
    await expect(page.getByText("Saldo insoluto al inicio del periodo k.", { exact: true })).toBeVisible();
    await page.getByLabel("Incluir impuestos proyectados", { exact: true }).check();
    await page.getByRole("button", { name: "Calcular escenario" }).click();
    await expect(page.getByText(/Define el uso de la configuración fiscal/).first()).toBeVisible();
    await expect(page.getByRole("columnheader", { name: "Pago total", exact: true })).toHaveCount(0);
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
