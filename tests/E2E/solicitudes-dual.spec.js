import { test, expect } from "@playwright/test";
import { login, failOnConsoleErrors } from "./support/auth.js";

test("dual bloquea capturista y permite aprobar a otra persona sin superadmin", async ({ page, browser }, testInfo) => {
    test.setTimeout(90_000);
    test.skip(process.env.E2E_ORIGINACION_DUAL !== "true", "Requiere escenario aislado con aprobación habilitada expresamente.");
    const assertConsole = await failOnConsoleErrors(page);
    await login(page, { email: "captura.dual@example.test", password: "password" });
    await page.goto("/solicitudes");
    await page.getByRole("link", { name: /^SOL-/ }).first().click();
    await expect(page).toHaveURL(/\/solicitudes\/\d+$/);
    const detalle = page.url();
    await expect(page.getByText("En modalidad dual debe aprobar una persona que no haya capturado ni enviado la solicitud.", { exact: true })).toBeVisible();
    await expect(page.getByRole("button", { name: "Confirmar aprobación" })).toBeDisabled();
    await page.screenshot({ path: testInfo.outputPath("dual-capturista.png"), fullPage: true });
    const context = await browser.newContext({ baseURL: new URL(detalle).origin, locale: "es-MX" });
    try {
        const aprobador = await context.newPage();
        const assertApproverConsole = await failOnConsoleErrors(aprobador);
        await login(aprobador, { email: "aprobacion.dual@example.test", password: "password" });
        await aprobador.goto(detalle);
        await expect(aprobador.getByRole("button", { name: "Confirmar aprobación" })).toBeEnabled();
        await expect(aprobador.getByRole("heading", { name: "Evaluación manual preliminar" })).toHaveCount(0);
        await expect(aprobador.getByRole("heading", { name: "Revisión PLD" })).toHaveCount(0);
        await aprobador.getByLabel("Motivo de aprobación").fill("Aprobación dual sintética verificada por una segunda persona.");
        await aprobador.getByRole("button", { name: "Confirmar aprobación" }).click();
        await expect(aprobador.getByRole("heading", { name: "Aprobada", exact: true })).toBeVisible();
        await aprobador.screenshot({ path: testInfo.outputPath("dual-aprobada.png"), fullPage: true });
        assertApproverConsole();
    } finally {
        await context.close();
    }
    await page.getByRole("button", { name: "Actualizar datos de la solicitud" }).click();
    await expect(page.getByRole("heading", { name: "Aprobada", exact: true })).toBeVisible();
    assertConsole();
});
