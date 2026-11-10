import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

for (const [tipo, generic, own] of [["Fisica", "XAXX010101000", "GALA910418AB8"], ["Moral", "XEXX010101000", "KDE260101AB1"]]) {
    test(`${tipo}: alta repetida, corrección y consulta del RFC genérico`, async ({ page }, testInfo) => {
        test.setTimeout(240_000);
        const assertConsole = await failOnConsoleErrors(page);
        await page.route("https://tile.openstreetmap.org/**", route => route.fulfill({ contentType: "image/png", body: Buffer.from(
            "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=", "base64",
        ) }));
        await login(page);
        async function createClient(number) {
            await page.goto("/clientes/create");
            await page.locator("#primer_nombre").fill(`Generico ${tipo} ${number}`);
            await page.locator("#apellido_paterno").fill("Prueba");
            await page.locator("#fecha_nacimiento input").fill("18-04-1991");
            await page.locator("#fecha_nacimiento input").press("Tab");
            await page.locator("#pais_nacimiento_id").click();
            await page.getByRole("option", { name: /México/ }).click();
            await page.locator("#sexo").click();
            await page.getByRole("option", { name: "Femenino", exact: true }).click();
            await page.locator("#email").fill(`generico-${tipo.toLowerCase()}-${number}@example.test`);
            await page.locator("input[type=tel]").fill("9991234567");
            await page.locator("input[type=tel]").press("Tab");
            await page.locator("#tipo_persona").click();
            await page.getByRole("option", { name: tipo, exact: true }).click();
            await page.locator("#curp").fill("GALA910418MDFRPN01");
            await page.locator("#rfc").fill(generic.toLowerCase());
            await expect(page.locator("#rfc")).toHaveValue(generic);
            await expect(page.getByText("RFC genérico: identidad fiscal propia pendiente.", { exact: true })).toBeVisible();
            if (tipo === "Moral") await page.locator("#razon_social").fill("Empresa de prueba E2E");
            await page.locator("#linea_uno").fill("Calle de prueba 123");
            await page.locator("#codigo_postal").fill("06000");
            await page.getByRole("option", { name: "06000", exact: true }).click();
            await page.locator("#division_admin_tres_id").click();
            await page.getByRole("option").first().click();
            await page.getByRole("button", { name: "Guardar Cliente", exact: true }).click();
            await expect(page).toHaveURL(/\/clientes\/\d+\/expediente$/, { timeout: 30_000 });
            return page.url().match(/\/clientes\/(\d+)\//)[1];
        }
        const first = await createClient(1);
        const second = await createClient(2);
        expect(second).not.toBe(first);
        await page.goto(`/clientes/${second}/edit`);
        await expect(page.locator("#rfc")).toHaveValue(generic);
        await page.locator("#rfc").fill(own);
        await expect(page.getByText("RFC genérico: identidad fiscal propia pendiente.", { exact: true })).toHaveCount(0);
        await page.getByRole("button", { name: "Guardar Cliente", exact: true }).click();
        await expect(page.getByText("Cliente actualizado exitosamente", { exact: true })).toBeVisible();
        await page.reload();
        await expect(page.locator("#rfc")).toHaveValue(own);
        await page.goto(`/clientes/${first}`);
        await expect(page.locator("#rfc")).toHaveValue(generic);
        await expect(page.locator("#rfc")).toBeDisabled();
        await expect(page.getByRole("button", { name: "Guardar Cliente", exact: true })).toHaveCount(0);
        const writes = [];
        page.on("request", r => { if (["POST", "PUT", "PATCH", "DELETE"].includes(r.method())) writes.push(r.url()); });
        await page.locator("form").filter({ has: page.locator("#rfc") }).dispatchEvent("submit");
        expect(writes).toEqual([]);
        await page.setViewportSize({ width: 390, height: 844 });
        await page.locator("#rfc").scrollIntoViewIfNeeded();
        await expect(page.getByText("RFC genérico: identidad fiscal propia pendiente.", { exact: true })).toBeVisible();
        await page.screenshot({ path: testInfo.outputPath(`rfc-${tipo.toLowerCase()}-consulta-movil.png`), fullPage: true });
        assertConsole();
    });
}
