import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test("administra permisos con etiquetas legibles en ambos temas y tamaños", async ({ page }, testInfo) => {
    await login(page);
    const assertNoConsoleErrors = await failOnConsoleErrors(page);
    await page.goto("/admin/roles");
    await page.getByRole("button", { name: "Crear rol", exact: true }).click();
    const roleName = "Tesorería y administración de recursos del equipo";
    await page.getByRole("dialog").getByLabel("Nombre del Rol", { exact: true }).fill(roleName);
    await page.getByRole("dialog").getByRole("button", { name: "Crear", exact: true }).click();
    await expect(page.getByRole("dialog")).toBeHidden();
    await page.getByRole("option", { name: roleName, exact: false }).click();
    const clientPermission = page.getByRole("option", { name: /Consultar clientes/ });
    await clientPermission.getByRole("button", { name: "Ayuda: Consultar clientes", exact: true }).click();
    await expect(clientPermission).toHaveAttribute("aria-selected", "false");
    await expect(page.getByText("Consultar el listado y el expediente de clientes. El alcance de los datos lo determina la política de acceso de clientes.", { exact: true })).toBeVisible();
    await page.keyboard.press("Escape");
    await page.getByRole("option", { name: /Consultar clientes/ }).click();
    await page.getByRole("button", { name: "Actualizar", exact: true }).click();
    await expect(page.getByRole("button", { name: "Actualizar", exact: true })).toBeDisabled();
    await page.reload();
    await page.getByRole("option", { name: roleName, exact: false }).click();
    await expect(page.getByRole("option", { name: /Consultar clientes/ })).toHaveAttribute("aria-selected", "true");
    for (const width of [1280, 768, 390]) {
        await page.setViewportSize({ width, height: 900 });
        for (const dark of [false, true]) {
            await page.evaluate((dark) => document.documentElement.classList.toggle("app-dark", dark), dark);
            await page.evaluate(() => window.scrollTo(0, 0));
            expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth)).toBe(true);
            await page.screenshot({ path: testInfo.outputPath(`roles-${width}-${dark ? "dark" : "light"}.png`) });
        }
    }
    await page.getByRole("option", { name: /Consultar clientes/ }).click();
    await page.getByRole("button", { name: "Actualizar", exact: true }).click();
    await expect(page.getByRole("button", { name: "Actualizar", exact: true })).toBeDisabled();
    await page.reload();
    await page.getByRole("option", { name: roleName, exact: false }).click();
    await expect(page.getByRole("option", { name: /Consultar clientes/ })).toHaveAttribute("aria-selected", "false");
    assertNoConsoleErrors();
});
