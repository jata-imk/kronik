import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test("muestra vigencia y activa una invitación una sola vez", async ({ page }, testInfo) => {
    await login(page);
    const assertNoConsoleErrors = await failOnConsoleErrors(page);
    await page.goto("/admin/users");
    const invited = page.getByRole("row").filter({ hasText: "invitada.e2e@example.test" });
    await expect(invited).toContainText("Enviada:");
    await expect(invited).toContainText("Vence:");
    await page.context().clearCookies();
    const invitation = "/invitaciones/E2E-INVITATION-ONLY?email=invitada.e2e%40example.test";
    await page.goto(invitation);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.getByLabel("Contraseña", { exact: true }).fill("nueva-clave-segura");
    await page.getByLabel("Confirmar contraseña", { exact: true }).fill("nueva-clave-segura");
    await page.screenshot({ path: testInfo.outputPath("invitacion-mobile.png") });
    await page.getByRole("button", { name: "Crear mi contraseña", exact: true }).click();
    await expect(page).toHaveURL(/\/login$/);
    await expect(page.getByRole("status")).toContainText("Tu cuenta está activa");
    await page.goto(invitation);
    await page.getByLabel("Contraseña", { exact: true }).fill("otra-clave-segura");
    await page.getByLabel("Confirmar contraseña", { exact: true }).fill("otra-clave-segura");
    await page.getByRole("button", { name: "Crear mi contraseña", exact: true }).click();
    await expect(page.getByText("La invitación no es válida o ya venció. Solicita que te envíen una nueva.", { exact: true })).toBeVisible();
    await login(page, { email: "invitada.e2e@example.test", password: "nueva-clave-segura" });
    assertNoConsoleErrors();
});
