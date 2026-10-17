import { test, expect } from "@playwright/test";
import { failOnConsoleErrors } from "./support/auth.js";

test("public landing explains modules and opens the SIC gallery without signing in", async ({
    page,
}) => {
    const assertConsole = await failOnConsoleErrors(page);
    await page.setViewportSize({ width: 1440, height: 1000 });
    await page.goto("/");
    await expect(page.getByRole("link", { name: "Iniciar sesión", exact: false })).toBeVisible();
    await expect(page.locator("#sic")).toContainText("Disponible en simulación");
    await expect(page.locator("#accesos")).toContainText("Roles y permisos");

    // Load each public capture, including images initially outside the viewport.
    for (const image of await page.locator("main img").all()) {
        await image.scrollIntoViewIfNeeded();
        await expect
            .poll(() => image.evaluate((el) => el.complete && el.naturalWidth > 0))
            .toBe(true);
    }

    const trigger = page.getByRole("button", {
        name: "Ampliar captura: Del score al contexto",
        exact: true,
    });
    await trigger.click();
    const dialog = page.getByRole("dialog");
    await expect(dialog).toHaveAccessibleName("Del score al contexto");
    await page.getByRole("button", { name: "Captura siguiente", exact: true }).click();
    await expect(dialog).toHaveAccessibleName("Lo que cuentan las cuentas");
    await page.keyboard.press("ArrowRight");
    await expect(dialog).toHaveAccessibleName("Cada consulta tiene historia");
    await page.keyboard.press("ArrowRight");
    await expect(dialog).toHaveAccessibleName("Del score al contexto");
    await page.keyboard.press("Escape");
    await expect(dialog).toBeHidden();
    await expect(trigger).toBeFocused();

    const howTo = page
        .locator(".k-module-card")
        .filter({ has: page.getByRole("heading", { name: "Usuarios y equipos", exact: true }) });
    await howTo.locator("summary").click();
    await expect(howTo.locator("ol")).toBeVisible();
    await expect(howTo.locator("ol")).toContainText("verificación en dos pasos");
    await expect
        .poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth))
        .toBe(true);
    assertConsole();
});

test("mobile landing keeps the report readable with zoom and no page overflow", async ({
    page,
}) => {
    const assertConsole = await failOnConsoleErrors(page);
    await page.setViewportSize({ width: 390, height: 844 });
    await page.emulateMedia({ reducedMotion: "reduce" });
    await page.goto("/#sic");
    await expect
        .poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth))
        .toBe(true);
    await page
        .getByRole("button", { name: "Ampliar captura: Del score al contexto", exact: true })
        .click();
    const dialog = page.getByRole("dialog");
    await expect(dialog).toBeVisible();
    await dialog.getByRole("button", { name: "Acercar captura", exact: true }).click();
    const stage = dialog.locator(".k-image-viewer-stage");
    await expect.poll(() => stage.evaluate((el) => el.scrollWidth > el.clientWidth)).toBe(true);
    await expect
        .poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth))
        .toBe(true);
    await stage.focus();
    await page.keyboard.press("ArrowRight");
    await expect.poll(() => stage.evaluate((el) => el.scrollLeft > 0)).toBe(true);
    await expect(dialog).toHaveAccessibleName("Del score al contexto");
    await dialog.getByRole("button", { name: "Captura siguiente", exact: true }).click();
    await expect(
        dialog.getByRole("button", { name: "Acercar captura", exact: true }),
    ).toHaveAttribute("aria-pressed", "false");
    await dialog.getByRole("button", { name: "Cerrar captura", exact: true }).click();
    await expect(dialog).toBeHidden();
    await page
        .getByRole("button", { name: "Ampliar captura: Roles y permisos", exact: true })
        .click();
    await expect(page.getByRole("dialog", { name: "Roles y permisos", exact: true })).toBeVisible();
    await page.keyboard.press("Escape");
    await expect
        .poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth))
        .toBe(true);
    assertConsole();
});
