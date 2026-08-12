import { expect, test } from "@playwright/test";
import { login } from "./support/auth.js";

for (const viewport of [
    { name: "escritorio", width: 1440, height: 900 },
    { name: "móvil", width: 390, height: 844 },
]) {
    test(`mantiene navegables las tablas clave en ${viewport.name}`, async ({ page }) => {
        await page.setViewportSize(viewport);
        await login(page);

        for (const url of ["/admin/users", "/admin/teams", "/admin/sucursales"]) {
            await page.goto(url);
            await expect(page.locator("table")).toBeVisible();
            await expect(page.locator("body")).not.toContainText(/Server Error|Exception/i);
        }
    });
}
