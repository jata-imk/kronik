import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login, users } from "./support/auth.js";

const editor = page => page.getByRole("dialog", { name: "Configurar elemento de menú", exact: true });
test.setTimeout(180_000);
async function choose(page, control, option) {
    await control.click();
    await page.getByRole("option", { name: option, exact: true }).click();
}
async function createGroup(page, label) {
    await page.getByRole("button", { name: "Agregar acción o grupo", exact: true }).click();
    const dialog = editor(page);
    await choose(page, dialog.locator("#menubar-type"), "Grupo de menú");
    await dialog.locator("#menubar-label").fill(label);
    await dialog.getByRole("button", { name: "Guardar", exact: true }).click();
    await expect(dialog).toBeHidden({ timeout: 45_000 });
}
for (const mobile of [false, true]) {
    for (const dark of [false, true]) {
        test(`configura y previsualiza menú en ${mobile ? "móvil" : "escritorio"} ${dark ? "oscuro" : "claro"}`, async ({ page }, testInfo) => {
            const group = `Cliente QA ${mobile ? "móvil" : "escritorio"} ${dark ? "oscuro" : "claro"}`;
            const label = `Listado QA ${mobile ? "móvil" : "escritorio"} ${dark ? "oscuro" : "claro"}`;
            await page.setViewportSize(mobile ? { width: 390, height: 844 } : { width: 1440, height: 1000 });
            await page.addInitScript(darkTheme => localStorage.setItem("layoutConfig", JSON.stringify({ darkTheme, primary: "emerald", surface: "zinc" })), dark);
            const assertConsole = await failOnConsoleErrors(page);
            await login(page);
            await page.goto("/admin/menubar-items");
            await createGroup(page, group);
            await page.getByRole("button", { name: "Agregar acción o grupo", exact: true }).click();
            const dialog = editor(page);
            await choose(page, dialog.locator("#menubar-action"), /^Listado de clientes/);
            await dialog.locator("#menubar-label").fill(label);
            await choose(page, dialog.locator("#menubar-parent"), group);
            await dialog.locator("#menubar-order").fill("7");
            await dialog.locator(".p-multiselect").click();
            await page.getByRole("option", { name: "Clientes · Editar cliente", exact: true }).click();
            await expect(dialog.locator(".p-multiselect")).toContainText("Clientes · Editar cliente");
            await page.keyboard.press("Escape");
            await expect(page.getByRole("dialog", { name: "¿Descartar los cambios?", exact: true })).toHaveCount(0);
            await expect(dialog).toBeVisible();
            await choose(page, dialog.locator("#menubar-preview-screen"), "Clientes · Editar cliente");
            await dialog.getByRole("button", { name: "Ver vista previa", exact: true }).click();
            const previewRegion = dialog.locator('[aria-label="Vista previa"]');
            const previewToggle = previewRegion.getByRole("button", { name: "Abrir acciones de esta pantalla", exact: true });
            if (await previewToggle.isVisible()) await previewToggle.click();
            const preview = previewRegion.getByRole("menubar", { name: "Vista previa del menú" });
            await preview.getByRole("menuitem", { name: group, exact: true }).click();
            const before = page.url();
            await preview.getByRole("menuitem", { name: label, exact: true }).click();
            expect(page.url()).toBe(before);
            await expect(preview.locator("a[href]")).toHaveCount(0);
            await dialog.locator('[aria-label="Vista previa"]').screenshot({ path: testInfo.outputPath("vista-previa.png") });
            await dialog.getByRole("button", { name: "Guardar", exact: true }).click();
            await expect(dialog).toBeHidden({ timeout: 45_000 });
            await page.goto("/clientes/1/edit");
            const menu = page.locator(".p-menubar");
            if (await menu.locator(".p-menubar-button").isVisible()) await menu.locator(".p-menubar-button").click();
            await menu.getByRole("menuitem", { name: group, exact: true }).click();
            await expect(menu.getByRole("menuitem", { name: label, exact: true })).toBeVisible();
            await page.reload();
            await expect(page.locator(".p-menubar")).toContainText(group);
            await page.screenshot({ path: testInfo.outputPath("menu-persistido.png") });
            assertConsole();
        });
    }
}
test("consulta por ID y teclado no guarda ni modifica configuración", async ({ page }, testInfo) => {
    await login(page);
    await page.goto("/admin/menubar-items");
    await page.getByRole("button", { name: /^Ver / }).first().click();
    const dialog = page.getByRole("dialog", { name: "Consultar elemento de menú", exact: true });
    await expect(dialog.getByRole("button", { name: "Guardar", exact: true })).toHaveCount(0);
    await expect(dialog.locator("#menubar-label")).toBeDisabled();
    const writes = [];
    page.on("request", request => { if (["POST", "PUT", "PATCH", "DELETE"].includes(request.method())) writes.push(request.url()); });
    await dialog.locator("#menubar-value").press("Enter");
    await dialog.getByRole("button", { name: "Cerrar consulta", exact: true }).click();
    await expect(dialog).toBeHidden();
    await page.reload();
    await expect(page.getByRole("dialog")).toHaveCount(0);
    expect(writes).toEqual([]);
    await page.screenshot({ path: testInfo.outputPath("consulta-cerrada.png") });
});
test("validación de menú muestra errores españoles y lector sin gestión no abre configuración", async ({ page }) => {
    await login(page);
    await page.goto("/admin/menubar-items");
    await page.getByRole("button", { name: "Agregar acción o grupo", exact: true }).click();
    const dialog = editor(page);
    await choose(page, dialog.locator("#menubar-type"), "Grupo de menú");
    await dialog.getByRole("button", { name: "Guardar", exact: true }).click();
    await expect(dialog).toContainText(/etiqueta.*obligatorio/i);
    await expect(dialog).not.toContainText("validation.");
    await page.goto("/clientes/1/edit");
    await page.context().clearCookies();
    await login(page, users.reader);
    await page.goto("/clientes");
    await expect(page.getByRole("button", { name: "Configurar", exact: true })).toHaveCount(0);
    expect((await page.request.get("/admin/menubar-items/catalogo")).status()).toBe(403);
});
