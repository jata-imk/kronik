import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test("estatus global restringe captura y conserva acceso a cartera", async ({ page }, testInfo) => {
    test.setTimeout(120_000);
    const assertConsole = await failOnConsoleErrors(page);
    await login(page);
    for (const estatus of ["Borrador", "Suspendida", "Activa"]) {
        await page.goto("/admin/configuracion-empresa");
        await page.locator("#estatus").click();
        await page.getByRole("option", { name: estatus, exact: true }).click();
        await page.getByRole("button", { name: "Guardar configuración" }).click();
        await expect(page.getByRole("alert").filter({ hasText: "Configuración actualizada" })).toBeVisible();
        await page.goto("/solicitudes/create");
        const guardar = page.getByRole("button", { name: "Guardar borrador", exact: true });
        if (estatus === "Suspendida") {
            await expect(guardar).toBeDisabled();
            await expect(guardar).toHaveAttribute("title", /empresa está suspendida/);
            await expect(page.getByRole("link", { name: "Revisar configuración de empresa" })).toBeVisible();
            await page.screenshot({ path: testInfo.outputPath("empresa-suspendida.png"), fullPage: true });
        } else {
            await expect(guardar).toBeEnabled();
        }
        await page.goto("/creditos");
        await expect(page.getByRole("heading", { name: "Créditos y pagos", exact: true })).toBeVisible();
    }
    assertConsole();
});
