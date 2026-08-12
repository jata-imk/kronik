import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test.beforeEach(async ({ page }) => login(page));

test("recorre las superficies administrativas actuales", async ({ page }) => {
    const assertNoConsoleErrors = await failOnConsoleErrors(page);
    const pages = [
        ["/admin", /Configuraciones del Super Admin/i],
        ["/admin/users", /Gestión de usuarios/i],
        ["/admin/teams", /Equipos y departamentos/i],
        ["/admin/sucursales", /^Sucursales$/i],
        ["/admin/configuracion-empresa", /^Configuración de empresa$/i],
        ["/admin/roles", /Roles/i],
        ["/admin/menubar-items", /Menubar|menú/i],
        ["/admin/users/activity", /Logs de Actividades|Actividad/i],
    ];

    for (const [url, text] of pages) {
        await page.goto(url);
        await expect(page.getByRole("heading", { name: text }).first()).toBeVisible();
    }
    assertNoConsoleErrors();
});

test("muestra el centro completo del equipo y prellena una invitación", async ({ page }) => {
    await page.goto("/admin/teams");
    await page.getByRole("link", { name: /abrir configuración de test user's team/i }).click();

    await expect(page.getByText("Miembros", { exact: true })).toBeVisible();
    await expect(page.getByRole("table").getByText("Test User", { exact: true })).toBeVisible();
    await page.getByRole("link", { name: /invitar usuario/i }).click();

    await expect(page.getByRole("dialog")).toBeVisible();
    await expect(page.getByRole("dialog")).toContainText(/Equipos y roles/i);
    await expect(page.getByRole("dialog")).toContainText(/Test User's Team/i);
});

test("usa confirmación PrimeVue para desactivar una sucursal", async ({ page }) => {
    await page.goto("/admin/sucursales");
    await page.getByRole("button", { name: /desactivar matriz/i }).click();

    await expect(page.getByRole("alertdialog")).toContainText(/Desactivar sucursal/i);
    await page.getByRole("button", { name: /cancelar/i }).click();
    await expect(page.getByRole("alertdialog")).toBeHidden();
});

test("muestra equipo y sucursal en actividad y permite exportar CSV", async ({ page }) => {
    await page.goto("/admin/users/activity");
    await expect(page.getByText("Test User's Team").first()).toBeVisible();
    await expect(page.getByText(/MATRIZ.*Matriz|Matriz/i).first()).toBeVisible();

    const downloadPromise = page.waitForEvent("download");
    await page.getByRole("button", { name: /exportar/i }).click();
    const download = await downloadPromise;
    expect(download.suggestedFilename()).toMatch(/actividad.*\.csv$/);
});
