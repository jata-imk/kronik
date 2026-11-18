import { expect, test } from "@playwright/test";
import { JSDOM } from "jsdom";
import { failOnConsoleErrors, login } from "./support/auth.js";

const openMobileMenu = async (page, mobile) => {
    if (mobile) await page.getByRole("button", { name: "Abrir menú", exact: true }).click();
};

for (const mobile of [false, true]) {
    for (const darkTheme of [false, true]) {
        test(`alterna sidebar en ${mobile ? "móvil" : "escritorio"} y tema ${darkTheme ? "oscuro" : "claro"}`, async ({ page }, testInfo) => {
            await page.setViewportSize(mobile ? { width: 390, height: 844 } : { width: 1440, height: 900 });
            await page.addInitScript((dark) => localStorage.setItem("layoutConfig", JSON.stringify({ darkTheme: dark, primary: dark ? "violet" : "emerald", surface: dark ? "slate" : "zinc" })), darkTheme);
            const assertConsole = await failOnConsoleErrors(page);
            await login(page);
            await page.goto("/clientes");
            await openMobileMenu(page, mobile);

            const sidebar = page.locator("#k-main-sidebar");
            const clientes = sidebar.getByRole("button", { name: "Clientes", exact: true });
            const originacion = sidebar.getByRole("button", { name: "Originación", exact: true });
            const listado = sidebar.getByRole("link", { name: "Listado", exact: true });
            await expect(clientes).toHaveAttribute("aria-expanded", "true");
            await expect(listado).toHaveAttribute("aria-current", "page");
            await clientes.click();
            await expect(clientes).toHaveAttribute("aria-expanded", "false");
            await expect(clientes).toHaveClass(/active-route/);
            await expect(clientes).toHaveAccessibleDescription("Página actual: Listado");
            await expect(listado).toBeHidden();
            await expect(page).toHaveURL(/\/clientes$/);
            await expect(clientes).toBeFocused();
            if (mobile) await expect(page.locator(".layout-wrapper")).toHaveClass(/layout-mobile-active/);
            await page.screenshot({ path: testInfo.outputPath("clientes-cerrado.png") });

            await clientes.press("Tab");
            await expect(originacion).toBeFocused();
            await clientes.focus();
            await clientes.press("Enter");
            await expect(clientes).toHaveAttribute("aria-expanded", "true");
            await clientes.press("Space");
            await expect(clientes).toHaveAttribute("aria-expanded", "false");
            await expect(clientes).toBeFocused();
            await expect(clientes).toHaveCSS("outline-style", "solid");
            await expect(clientes).toHaveCSS("outline-width", "2px");
            expect((await clientes.boundingBox()).height).toBeGreaterThanOrEqual(44);
            if (mobile) await expect(page.locator(".layout-wrapper")).toHaveClass(/layout-mobile-active/);

            // The configurable contextual menubar is outside this test's interaction surface.
            await originacion.click();
            await expect(originacion).toHaveAttribute("aria-expanded", "true");
            await expect(clientes).toHaveAttribute("aria-expanded", "false");
            await sidebar.getByRole("link", { name: "Todas las solicitudes", exact: true }).click();
            await expect(page).toHaveURL(/\/solicitudes$/);
            if (mobile) {
                await expect(page.locator(".layout-wrapper")).not.toHaveClass(/layout-mobile-active/);
                await openMobileMenu(page, mobile);
            }
            await expect(originacion).toHaveClass(/active-route/);
            await expect(clientes).not.toHaveClass(/active-route/);

            await page.goBack();
            await expect(page).toHaveURL(/\/clientes$/);
            await expect(clientes).toHaveAttribute("aria-expanded", "true");
            await clientes.click();
            await page.reload();
            await openMobileMenu(page, mobile);
            await expect(clientes).toHaveAttribute("aria-expanded", "true");
            await expect(page.locator("html")).toHaveClass(darkTheme ? /app-dark/ : /^(?!.*app-dark)/);
            await page.screenshot({ path: testInfo.outputPath("clientes-abierto.png") });
            assertConsole();
        });
    }
}

test("conserva cierre al filtrar y navegar parcialmente con Inertia", async ({ page }) => {
    await login(page);
    await page.goto("/clientes");
    const clientes = page.locator("#k-main-sidebar").getByRole("button", { name: "Clientes", exact: true });
    await clientes.click();
    const response = page.waitForResponse((response) => response.request().headers()["x-inertia"] === "true" && response.url().includes("/clientes?"));
    await page.getByPlaceholder("Buscar por nombre", { exact: true }).fill("Prueba");
    await response;
    await expect(page).toHaveURL(/\/clientes\?/);
    await expect(clientes).toHaveAttribute("aria-expanded", "false");
    await expect(clientes).toHaveClass(/active-route/);
});

test("alternar en modo overlay conserva el panel y la selección", async ({ page }) => {
    await page.addInitScript(() => localStorage.setItem("layoutConfig", JSON.stringify({ menuMode: "overlay", darkTheme: true })));
    await login(page);
    await page.goto("/clientes");
    await page.getByRole("button", { name: "Abrir menú", exact: true }).click();
    const clientes = page.locator("#k-main-sidebar").getByRole("button", { name: "Clientes", exact: true });
    await clientes.click();
    await expect(clientes).toHaveAttribute("aria-expanded", "false");
    await expect(page.locator(".layout-wrapper")).toHaveClass(/layout-overlay-active/);
    await clientes.press("Enter");
    await expect(clientes).toHaveAttribute("aria-expanded", "true");
    await expect(page.locator(".layout-wrapper")).toHaveClass(/layout-overlay-active/);
});

test("alterna grupos anidados con teclado sin perder selección ni desbordar en móvil", async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await login(page);
    // A synthetic extra level exercises the recursive component without changing
    // NavigationService or introducing production destinations/permissions.
    await page.route("**/clientes", async (intercepted) => {
        const response = await intercepted.fetch();
        const dom = new JSDOM(await response.text());
        const app = dom.window.document.querySelector("#app");
        const data = JSON.parse(app.dataset.page);
        const clientes = data.props.navigation.flatMap((section) => section.items ?? []).find((item) => item.label === "Clientes");
        clientes.items = [
            { label: "Detalle de clientes", items: clientes.items },
            { label: "Herramientas", items: [{ label: "Ir al tablero", to: "dashboard" }] },
        ];
        app.dataset.page = JSON.stringify(data);
        await intercepted.fulfill({ response, body: dom.serialize() });
        dom.window.close();
    });
    await page.goto("/clientes");
    await openMobileMenu(page, true);
    const sidebar = page.locator("#k-main-sidebar");
    const clientes = sidebar.getByRole("button", { name: "Clientes", exact: true });
    const detalle = sidebar.getByRole("button", { name: "Detalle de clientes", exact: true });
    const herramientas = sidebar.getByRole("button", { name: "Herramientas", exact: true });
    await expect(clientes).toHaveAttribute("aria-expanded", "true");
    await expect(detalle).toHaveAttribute("aria-expanded", "true");
    await detalle.press("Enter");
    await expect(detalle).toHaveAttribute("aria-expanded", "false");
    await expect(detalle).toHaveClass(/active-route/);
    await expect(clientes).toHaveAttribute("aria-expanded", "true");
    await detalle.press("Space");
    await expect(detalle).toHaveAttribute("aria-expanded", "true");
    await herramientas.click();
    await expect(detalle).toHaveAttribute("aria-expanded", "false");
    await expect(herramientas).toHaveAttribute("aria-expanded", "true");
    const bounds = await herramientas.boundingBox();
    const container = await herramientas.locator("..").boundingBox();
    expect(bounds.x + bounds.width).toBeLessThanOrEqual(container.x + container.width + 1);
    await expect(page.locator(".layout-wrapper")).toHaveClass(/layout-mobile-active/);
    await clientes.click();
    await clientes.click();
    await expect(detalle).toHaveAttribute("aria-expanded", "false");
    await expect(herramientas).toHaveAttribute("aria-expanded", "false");
});
