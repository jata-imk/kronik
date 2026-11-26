import { expect } from "@playwright/test";
export async function createNotification(page, { key, channel = "sms", event = "payment_received", format = "text", body = "Hola {nombre}, registramos tu pago." }) {
    await page.goto("/comunicaciones/notificaciones");
    await page.getByRole("button", { name: "Nueva plantilla", exact: true }).click();
    await page.getByLabel("Nombre", { exact: true }).fill("QA " + key);
    await page.getByLabel("Clave", { exact: true }).fill(key);
    await page.getByLabel("Canal de la plantilla").selectOption(channel);
    await page.getByLabel("Evento de la plantilla").selectOption(event);
    if (channel === "email") {
        await page.getByLabel("Asunto", { exact: true }).fill("Aviso para {nombre}");
        await page.getByLabel("Formato", { exact: true }).selectOption(format);
    }
    if (format === "html") await page.locator(".ql-editor").fill(body);
    else await page.getByLabel("Contenido", { exact: true }).fill(body);
    await page.getByRole("button", { name: "Guardar borrador", exact: true }).click();
    await expect(page.getByRole("heading", { name: /Consultar versión 1/ })).toBeVisible();
    page.once("dialog", dialog => dialog.accept());
    await page.getByRole("button", { name: "Activar versión", exact: true }).click();
    await expect(page.getByRole("heading", { name: /Consultar versión 1 · Activa/ })).toBeVisible();
}
