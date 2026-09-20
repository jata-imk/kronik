import { expect, test } from "@playwright/test";
import { failOnConsoleErrors, login } from "./support/auth.js";

test("revisión y carga empiezan limpias entre documentos", async ({ page }) => {
    test.setTimeout(90_000);
    const assertConsole = await failOnConsoleErrors(page);
    await login(page);
    await page.goto("/clientes/1/expediente");
    await page.getByRole("button", { name: "Documentos", exact: true }).click();
    const rows = page.locator(".document-row");
    const upload = async (row, name) => {
        await row.getByRole("button", { name: /Cargar documento|Sustituir documento/ }).click();
        const dialog = page.getByRole("dialog");
        await dialog.locator('input[type="file"]').setInputFiles({
            name, mimeType: "application/pdf", buffer: Buffer.from("%PDF-1.4\n% Documento de prueba QA\n%%EOF"),
        });
        await dialog.getByRole("button", { name: "Guardar archivo" }).click();
        await expect(dialog).not.toBeVisible();
        await expect(row).toContainText(name);
    };
    await upload(rows.nth(0), "qa-primero.pdf");
    await upload(rows.nth(1), "qa-segundo.pdf");
    await rows.nth(0).getByRole("button", {name:"Revisar documento"}).click();
    await page.getByRole("button", {name:"Rechazar", exact:true}).click();
    await page.getByRole("dialog").locator("textarea").fill("La imagen recibida resulta ilegible.");
    await page.getByRole("button", {name:"Aplicar estado"}).click();
    await expect(page.getByRole("dialog")).not.toBeVisible();
    await rows.nth(1).getByRole("button", {name:"Revisar documento"}).click();
    await page.getByRole("button", {name:"Rechazar", exact:true}).click();
    await expect(page.getByRole("dialog").locator("textarea")).toHaveValue("");
    await page.getByRole("dialog").getByRole("button", {name:"Cancelar", exact:true}).click();

    await rows.nth(0).getByRole("button", {name:"Sustituir documento"}).click();
    const dialog = page.getByRole("dialog");
    await expect(dialog.getByText("Sin archivo seleccionado.", {exact:false})).toBeVisible();
    await dialog.locator('input[type="file"]').setInputFiles({name:"no-permitido.svg",mimeType:"image/svg+xml",buffer:Buffer.from("<svg/>")});
    await expect(dialog.getByText("Selecciona un archivo PDF, JPG o PNG.", {exact:true})).toBeVisible();
    await dialog.locator('input[type="file"]').setInputFiles({name:"corregido.pdf",mimeType:"application/pdf",buffer:Buffer.from("%PDF-1.4\n%%EOF")});
    await expect(dialog.getByText("Selecciona un archivo PDF, JPG o PNG.", {exact:true})).not.toBeVisible();
    await dialog.getByRole("button", {name:"Quitar selección"}).click();
    await expect(dialog.getByText("Sin archivo seleccionado.", {exact:false})).toBeVisible();
    await dialog.getByRole("button", {name:"Cancelar", exact:true}).click();
    await upload(rows.nth(0), "qa-reemplazo.pdf");
    const history = rows.nth(0).locator(".document-history");
    await history.locator("summary").click();
    await expect(history).toContainText("qa-primero.pdf");
    await expect(history).toContainText("Rechazado");
    await expect(history).toContainText("La imagen recibida resulta ilegible.");
    await expect(history).not.toContainText("qa-segundo.pdf");
    assertConsole();
});
