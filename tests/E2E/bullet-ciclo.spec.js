import { expect, test } from "@playwright/test";
import { readFileSync, writeFileSync } from "node:fs";
import { login, failOnConsoleErrors } from "./support/auth.js";

const clockFile = new URL("../../storage/framework/testing/bullet-e2e-clock.txt", import.meta.url);
const fixturesFile = new URL("../../storage/framework/testing/bullet-e2e-fixtures.json", import.meta.url);

// Each run consumes draft versions; a retry requires a fresh isolated database.
test.describe.configure({ retries: 0 });

async function fecha(page, day) {
    writeFileSync(clockFile, `${day}T12:00:00-06:00\n`);
    await page.clock.setFixedTime(new Date(`${day}T12:00:00-06:00`));
}

for (const policy of ["periodicos", "al_vencimiento"]) {
    test(`Bullet ${policy}: configura, origina, paga, anticipa, liquida y reabre`, async ({ page }, testInfo) => {
        test.setTimeout(300_000);
        page.setDefaultTimeout(15000);
        test.skip(process.env.E2E_BULLET_FIXTURES !== "true", "Requiere fixtures y reloj exclusivamente E2E aislados.");
        const fixture = JSON.parse(readFileSync(fixturesFile, "utf8"))[policy];
        await page.clock.install({ time: new Date("2027-01-01T12:00:00-06:00") });
        await fecha(page, "2027-01-01");
        const assertConsole = await failOnConsoleErrors(page);
        await login(page);
        await page.goto(`/productos-crediticios?producto_id=${fixture.producto}&version_id=${fixture.version}`);
        await page.getByRole("button", { name: "Editar versión 1", exact: true }).click();
        const editor = page.locator("#product-editor");
        await editor.getByRole("tab", { name: "Reglas", exact: true }).click();
        await editor.getByRole("combobox", { name: "Pago de intereses Bullet", exact: true }).click();
        await page.getByRole("option", { name: policy === "periodicos" ? "Intereses periódicos" : "Intereses al vencimiento", exact: true }).click();
        await expect(editor).toContainText("Conservar vencimiento");
        await editor.getByRole("button", { name: "Guardar borrador", exact: true }).click();
        await expect(editor).toHaveCount(0);
        await page.getByRole("button", { name: "Activar versión 1", exact: true }).click();
        await page.getByRole("button", { name: "Activar ahora", exact: true }).click();
        await expect(page.getByRole("button", { name: "Editar versión 1", exact: true })).toHaveCount(0);
        await page.getByRole("button", { name: "Simular versión 1", exact: true }).click();
        const simulator = page.locator(".p-drawer").last();
        const amount = simulator.locator(".p-inputnumber-input").first();
        await amount.press("ControlOrMeta+A");
        await amount.pressSequentially("12000");
        await amount.blur();
        await expect(amount).toHaveValue(/12,000\.00/);
        const simulated = page.waitForResponse(response => response.request().method() === "POST" && response.url().includes("/simular"));
        await page.getByRole("button", { name: "Calcular escenario", exact: true }).click();
        const simulation = await (await simulated).json();
        expect(simulation.politica_interes_bullet).toBe(policy);
        expect(simulation.tabla.slice(1).map(row => row.pago_total)).toEqual(policy === "periodicos" ? ["248.00", "12224.00"] : ["0.00", "12472.00"]);
        await page.keyboard.press("Escape");

        await page.goto(`/solicitudes/create?cliente_id=${fixture.cliente}`);
        await page.getByLabel("Producto y versión", { exact: true }).click();
        await page.getByRole("option", { name: new RegExp(`Bullet QA ${policy}`) }).click();
        await page.getByLabel("Monto solicitado (MXN)").pressSequentially("12000");
        await page.getByRole("combobox", { name: "Periodicidad", exact: true }).click();
        await page.getByRole("option", { name: "Mensual", exact: true }).click();
        await page.getByLabel("Número de periodos hasta vencer el capital").fill("2");
        await page.getByRole("combobox", { name: "Amortización", exact: true }).click();
        await page.getByRole("option", { name: "Bullet", exact: true }).click();
        await page.getByLabel("Fecha estimada de desembolso").fill("2027-01-01");
        await page.getByLabel("Destino del crédito").fill("Ciclo Bullet sintético aislado.");
        await page.getByRole("button", { name: "Guardar borrador", exact: true }).click();
        await expect(page).toHaveURL(/\/solicitudes\/\d+$/);
        await page.getByRole("button", { name: "Enviar a revisión", exact: true }).click();
        for (const type of ["evaluacion", "pld"]) {
            await page.getByLabel(type === "evaluacion" ? "Resultado de evaluación" : "Resultado PLD", { exact: true }).click();
            await page.getByRole("option", { name: type === "evaluacion" ? "Favorable" : "Sin observaciones en la revisión manual", exact: true }).click();
            if (type === "pld") {
                await page.getByLabel("Nivel de riesgo", { exact: true }).click();
                await page.getByRole("option", { name: "Bajo", exact: true }).click();
            }
            await page.getByLabel(`Metodología y versión utilizadas (${type})`, { exact: true }).fill("Manual sintético Bullet E2E versión 1");
            await page.getByLabel(`Fuentes y evidencias revisadas (${type})`, { exact: true }).fill("Expediente sintético aislado del recorrido Bullet.");
            await page.getByLabel(`Fundamento reservado (${type})`, { exact: true }).fill("Revisión favorable para el escenario de pruebas Bullet.");
            await page.getByRole("button", { name: type === "evaluacion" ? "Registrar evaluación" : "Registrar revisión PLD", exact: true }).click();
            await expect(page.getByRole("heading", { name: type === "evaluacion" ? "Favorable · Último de la revisión actual" : "Sin observaciones en la revisión manual · Último de la revisión actual", exact: true })).toBeVisible();
        }
        await page.getByLabel("Motivo de aprobación").fill("Aprobación sintética del recorrido completo Bullet.");
        await page.getByRole("button", { name: "Confirmar aprobación", exact: true }).click();
        await expect(page.getByRole("heading", { name: "Aprobada", exact: true })).toBeVisible();
        await page.getByRole("link", { name: "Contrato y tabla de pagos", exact: true }).click();
        await page.getByRole("button", { name: "Preparar contrato QA", exact: true }).click();
        await page.getByLabel("Plantilla de contrato").click();
        await page.getByRole("option").first().click();
        await page.getByRole("checkbox").check();
        await page.getByRole("button", { name: "Confirmar preparación QA", exact: true }).click();
        await expect(page.getByRole("button", { name: "Ver PDF QA", exact: true })).toBeVisible({ timeout: 60000 });
        await page.getByRole("button", { name: "Ver PDF QA", exact: true }).click();
        const download = page.getByRole("link", { name: "Descargar", exact: true });
        const pdf = await page.request.get(await download.getAttribute("href"));
        expect(pdf.ok()).toBe(true);
        const originalPdf = await pdf.body();
        expect(originalPdf.subarray(0, 5).toString()).toBe("%PDF-");
        await expect(page.locator('iframe[title^="PDF:"]')).toBeVisible();
        await page.keyboard.press("Escape");
        await page.getByRole("button", { name: "Recibir copia firmada", exact: true }).click();
        await page.getByLabel("Contrato firmado en PDF").setInputFiles({ name: "bullet-firma-qa.pdf", mimeType: "application/pdf", buffer: Buffer.concat([originalPdf, Buffer.from("\n% Firma sintetica Bullet")]) });
        await page.getByLabel("Fecha de firma", { exact: false }).fill("2027-01-01");
        await page.getByRole("checkbox").check();
        await page.getByRole("button", { name: "Enviar copia para revisión", exact: true }).click();
        await page.getByRole("button", { name: "Revisar firma", exact: true }).click();
        await page.getByRole("checkbox").nth(0).check();
        await page.getByRole("checkbox").nth(1).check();
        await page.getByRole("button", { name: "Aceptar firma y formalizar QA", exact: true }).click();
        await page.getByRole("link", { name: "Registrar desembolso QA", exact: true }).click();
        await page.getByRole("button", { name: "Revisar y registrar desembolso QA", exact: true }).click();
        await page.getByLabel("Referencia única de transferencia", { exact: false }).fill(`BULLET-DES-${policy}`);
        await page.getByRole("checkbox").check();
        await page.getByRole("button", { name: "Confirmar registro QA", exact: true }).click();
        await expect(page).toHaveURL(/\/creditos\/\d+$/);
        const creditUrl = page.url();

        await fecha(page, "2027-01-16");
        await page.reload();
        // The labelled container remains visible without relying on rounded motor output as an oracle.
        await expect(page.locator('[aria-label="Intereses Bullet"]')).toContainText("$120.00");
        await page.getByRole("link", { name: "Anticipos y liquidación", exact: true }).click();
        await expect(page.getByText(/El interés aún no exigible conserva importe y vencimiento/)).toBeVisible();
        await page.getByLabel("Total recibido (MXN)", { exact: false }).pressSequentially("3120");
        await page.getByLabel("Referencia única", { exact: false }).fill(`BULLET-ANT-${policy}`);
        await page.getByRole("button", { name: "Revisar efecto y distribución", exact: true }).click();
        await expect(page.getByRole("heading", { name: "Capital pendiente antes y después", exact: true })).toBeVisible();
        await page.getByRole("checkbox").check();
        await page.getByRole("button", { name: "Confirmar operación QA", exact: true }).click();
        await page.goto(creditUrl);
        await expect(page.getByText("$8,880.00", { exact: true }).first()).toBeVisible();
        await expect(page.locator('[aria-label="Intereses Bullet"]')).toContainText("$120.00");
        await page.screenshot({ path: testInfo.outputPath(`bullet-${policy}-desktop.png`), fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        await page.screenshot({ path: testInfo.outputPath(`bullet-${policy}-mobile.png`), fullPage: true });
        await page.setViewportSize({ width: 1440, height: 1000 });

        await fecha(page, "2027-01-20");
        await page.getByRole("link", { name: "Anticipos y liquidación", exact: true }).click();
        await page.getByLabel("Operación", { exact: false }).click();
        await page.getByRole("option", { name: "Liquidación total", exact: true }).click();
        await page.getByLabel("Referencia única", { exact: false }).fill(`BULLET-LIQ-${policy}`);
        const quoted = page.waitForResponse(response => response.request().method() === "POST" && response.url().includes("/preview"));
        await page.getByRole("button", { name: "Revisar efecto y distribución", exact: true }).click();
        expect((await (await quoted).json()).importe).toBe("9023.68");
        await page.getByRole("checkbox").check();
        await page.getByRole("button", { name: "Confirmar operación QA", exact: true }).click();
        await expect(page.getByText("Liquidación registrada · QA", { exact: true })).toBeVisible();
        await page.getByRole("button", { name: "Revertir último pago", exact: true }).click();
        await page.getByLabel("Motivo del reverso", { exact: false }).fill("Corrección sintética de liquidación Bullet E2E.");
        await page.getByRole("checkbox").check();
        await page.getByRole("button", { name: "Confirmar reverso QA", exact: true }).click();
        await page.goto(creditUrl);
        await expect(page.getByText("Activo · QA", { exact: true })).toBeVisible();

        await fecha(page, policy === "periodicos" ? "2027-02-01" : "2027-03-01");
        await page.reload();
        await page.getByRole("link", { name: "Registrar pago QA", exact: true }).click();
        await page.getByLabel("Importe recibido (MXN)", { exact: false }).pressSequentially("100");
        await page.getByLabel("Referencia única del pago", { exact: false }).fill(`BULLET-PAGO-${policy}`);
        await page.getByRole("button", { name: "Revisar distribución", exact: true }).click();
        await page.getByRole("checkbox").check();
        await page.getByRole("button", { name: "Confirmar pago QA", exact: true }).click();
        await expect(page.getByRole("heading", { name: /^Recibo interno/ })).toBeVisible();
        await fecha(page, policy === "periodicos" ? "2027-02-02" : "2027-03-02");
        await page.goto(creditUrl);
        await expect(page.getByText("1 días de atraso de obligaciones", { exact: true })).toBeVisible();
        // Al vencimiento con ambos: 380.48 - 100 + un día de 5.92 = 286.40.
        await expect(page.locator('[aria-label="Intereses Bullet"]')).toContainText(policy === "periodicos" ? "$114.72" : "$286.40");
        assertConsole();
    });
}
