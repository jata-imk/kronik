import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { login, users } from "./support/auth.js";

test("fallo anónimo aparece en ámbito global sin atribuirlo al titular del correo", async ({ page }, testInfo) => {
    await page.goto("/login");
    await page.locator("#email").fill(users.superAdmin.email);
    await page.locator("#password").fill("INCORRECTA-RESERVADA");
    await page.getByRole("button", { name: /entrar a kronik|log in/i }).click();
    await expect(page.locator("form")).toContainText(/credenciales|coinciden/i);
    await login(page);
    await page.goto("/admin/users/activity?event=login.failed");
    const row = page.getByRole("row").filter({ hasText: "Credenciales de acceso rechazadas" }).first();
    await expect(row).toContainText("Anónimo");
    await expect(row).not.toContainText(users.superAdmin.email);
    await expect(page.getByRole("table")).not.toContainText("INCORRECTA-RESERVADA");
    await row.getByRole("button", { name: /Ver detalle de actividad/ }).click();
    await expect(page.getByRole("dialog")).toContainText("Sin equipo");
    await expect(page.getByRole("dialog")).toContainText("Sin sucursal");
    await page.screenshot({ path: testInfo.outputPath("actividad-anonima.png"), fullPage: true });
});

test("alta de equipo y generación CSV quedan trazables sin incluir la exportación en sí misma", async ({ page }) => {
    test.setTimeout(90_000);
    await login(page);
    await page.goto("/admin/teams");
    await page.getByRole("button", { name: "Crear equipo", exact: true }).click();
    const dialog = page.getByRole("dialog");
    const name = `Auditoría E2E ${Date.now()}`;
    await dialog.getByRole("textbox").fill(name);
    await dialog.getByRole("button", { name: "Crear", exact: true }).click();
    await expect(dialog).toBeHidden({ timeout: 15_000 });
    const link = page.getByRole("link", { name: `Abrir configuración de ${name}`, exact: true });
    await expect(link).toBeVisible();
    const teamId = (await link.getAttribute("href")).split("/").at(-1);
    await page.goto(`/admin/users/activity?team_id=${teamId}`);
    await expect(page.getByRole("row").filter({ hasText: "Equipo creado" })).toHaveCount(1);
    const downloadPromise = page.waitForEvent("download");
    await page.getByRole("button", { name: "Exportar CSV", exact: true }).click();
    const download = await downloadPromise;
    const csv = await readFile(await download.path(), "utf8");
    expect(csv).toContain("team.created");
    expect(csv).not.toContain("activity_log.exported");
    await page.goto(`/admin/users/activity?team_id=${teamId}&event=activity_log.exported`);
    await expect(page.getByRole("row").filter({ hasText: "Archivo CSV de actividad generado" })).toHaveCount(1);
});
