import { expect, test } from "@playwright/test";
import { readFileSync } from "node:fs";
import { login, failOnConsoleErrors } from "./support/auth.js";

const fixturesFile = new URL("../../storage/framework/testing/historico-e2e-fixtures.json", import.meta.url);
// Independent integer-cent oracle: no imports from production financial code.
const days = (a, b) => Math.round((Date.parse(b + "T00:00:00Z") - Date.parse(a + "T00:00:00Z")) / 86400000);
const addDays = (date, n) => new Date(Date.parse(date + "T00:00:00Z") + n * 86400000).toISOString().slice(0, 10);
const month = (date, n) => { const d = new Date(date + "T00:00:00Z"); d.setUTCMonth(d.getUTCMonth() + n); return d.toISOString().slice(0, 10); };
const cash = (cents) => (Number(cents) / 100).toFixed(2);
const rounded = (numerador) => (BigInt(numerador) + 18000n) / 36000n;
function oracle(fixture, policy, reversado) {
    const [v1, v2] = [month(fixture.fecha, 1), month(fixture.fecha, 2)];
    if (policy === "periodicos") {
        const remanente = 800 * days(fixture.fecha, v1) - (reversado ? 10000 : 15000);
        const segundo = 800 * days(v1, v2);
        const mora1 = rounded(BigInt(remanente) * 36n * BigInt(Math.max(0, days(v1, fixture.hoy) - 3)));
        const mora2 = rounded(BigInt(segundo) * 36n * BigInt(Math.max(0, days(v2, fixture.hoy) - 3)));
        return { capital_insoluto: "12000.00", capital_vencido: "0.00", interes_vencido: cash(remanente + segundo),
            interes_no_exigible: cash(800 * days(v2, fixture.hoy)), exigible: cash(BigInt(remanente + segundo) + mora1 + mora2) };
    }
    const numerador = 1200000n * 24n * 15n + (reversado
        ? 888000n * 24n * BigInt(days(addDays(fixture.fecha, 15), fixture.hoy))
        : 888000n * 24n + 788000n * 24n * BigInt(days(addDays(fixture.fecha, 16), fixture.hoy)));
    return { capital_insoluto: reversado ? "8880.00" : "7880.00", capital_vencido: "0.00", interes_vencido: "0.00",
        interes_no_exigible: cash(rounded(numerador)), exigible: "0.00", dias_atraso: 0 };
}

test.describe.configure({ retries: 0 });

for (const policy of ["periodicos", "al_vencimiento"]) {
    test(`Histórico ${policy}: UI real, pagos cronológicos, saldo y reverso`, async ({ page }, testInfo) => {
        test.setTimeout(300_000);
        page.setDefaultTimeout(15000);
        page.setDefaultNavigationTimeout(30_000);
        test.skip(process.env.E2E_HISTORICO_FIXTURES !== "true", "Requiere base nueva y fixtures históricos aislados; conserva el reloj real.");
        const fixture = JSON.parse(readFileSync(fixturesFile, "utf8"))[policy];
        const vencimientos = [1, 2, 3].map((n) => month(fixture.fecha, n));
        const assertConsole = await failOnConsoleErrors(page);
        await login(page, { email: "historico.e2e@example.test", password: "password" });
        await page.goto(`/productos-crediticios?producto_id=${fixture.producto}&version_id=${fixture.version}`, { waitUntil: "domcontentloaded" });
        await page.getByRole("button", { name: "Editar versión 1", exact: true }).click();
        const editor = page.locator("#product-editor");
        await editor.getByRole("tab", { name: "Reglas", exact: true }).click();
        await editor.getByRole("combobox", { name: "Pago de intereses Bullet", exact: true }).click();
        await page.getByRole("option", { name: policy === "periodicos" ? "Intereses periódicos" : "Intereses al vencimiento", exact: true }).click();
        await expect(editor).toContainText("Conservar vencimiento");
        await editor.getByRole("button", { name: "Guardar borrador", exact: true }).click();
        await expect(editor).toHaveCount(0);
        await page.getByRole("button", { name: "Activar versión 1", exact: true }).click();
        await page.locator("#activation-date").fill(fixture.fecha);
        await page.locator("#activation-date").press("Tab");
        await page.getByLabel("Confirmo la vigencia pasada exclusivamente para QA.", { exact: true }).check();
        await page.getByRole("button", { name: "Activar ahora", exact: true }).click();
        await expect(page.getByRole("button", { name: "Editar versión 1", exact: true })).toHaveCount(0);
        await page.getByRole("button", { name: "Simular versión 1", exact: true }).click();
        const simulator = page.locator(".p-drawer").last();
        const amount = simulator.locator(".p-inputnumber-input").first();
        await amount.press("ControlOrMeta+A");
        await amount.pressSequentially("12000");
        await amount.blur();
        await expect(amount).toHaveValue(/12,000\.00/);
        await simulator.locator(".p-datepicker-input").fill(fixture.fecha);
        await simulator.locator(".p-datepicker-input").press("Tab");
        const periods = simulator.locator(".p-inputnumber-input").nth(1);
        await periods.press("ControlOrMeta+A");
        await periods.pressSequentially("3");
        await periods.blur();
        const simulated = page.waitForResponse(response => response.request().method() === "POST" && response.url().includes("/simular"));
        await page.getByRole("button", { name: "Calcular escenario", exact: true }).click();
        const simulation = await (await simulated).json();
        expect(simulation.politica_interes_bullet).toBe(policy);
        const intereses = vencimientos.map((v, i) => 800 * days(i === 0 ? fixture.fecha : vencimientos[i - 1], v));
        expect(simulation.tabla.slice(1).map(row => row.pago_total)).toEqual(policy === "periodicos" ? [cash(intereses[0]), cash(intereses[1]), cash(1200000 + intereses[2])] : ["0.00", "0.00", cash(1200000 + intereses.reduce((a, b) => a + b, 0))]);
        await page.keyboard.press("Escape");

        await page.goto(`/solicitudes/create?cliente_id=${fixture.cliente}`, { waitUntil: "domcontentloaded" });
        await page.getByLabel("Producto y versión", { exact: true }).click();
        await page.getByRole("option", { name: new RegExp(`Bullet QA ${policy}`) }).click();
        await page.getByLabel("Monto solicitado (MXN)").pressSequentially("12000");
        await page.getByRole("combobox", { name: "Periodicidad", exact: true }).click();
        await page.getByRole("option", { name: "Mensual", exact: true }).click();
        await page.getByLabel("Número de periodos hasta vencer el capital").fill("3");
        await page.getByRole("combobox", { name: "Amortización", exact: true }).click();
        await page.getByRole("option", { name: "Bullet", exact: true }).click();
        await page.getByLabel("Fecha estimada de desembolso").fill(fixture.fecha);
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
        const originalPdfUrl = await download.getAttribute("href");
        expect(originalPdf.subarray(0, 5).toString()).toBe("%PDF-");
        await expect(page.locator('iframe[title^="PDF:"]')).toBeVisible();
        await page.keyboard.press("Escape");
        await page.getByRole("button", { name: "Recibir copia firmada", exact: true }).click();
        await page.getByLabel("Contrato firmado en PDF").setInputFiles({ name: "bullet-firma-qa.pdf", mimeType: "application/pdf", buffer: Buffer.concat([originalPdf, Buffer.from("\n% Firma sintetica Bullet")]) });
        await page.getByLabel("Fecha de firma", { exact: false }).fill(fixture.hoy);
        await page.getByRole("checkbox").check();
        await page.getByRole("button", { name: "Enviar copia para revisión", exact: true }).click();
        await page.getByRole("button", { name: "Revisar firma", exact: true }).click();
        await page.getByRole("checkbox").nth(0).check();
        await page.getByRole("checkbox").nth(1).check();
        await page.getByRole("button", { name: "Aceptar firma y formalizar QA", exact: true }).click();
        await page.getByRole("link", { name: "Registrar desembolso QA", exact: true }).click();
        await page.getByRole("button", { name: "Revisar y registrar desembolso QA", exact: true }).click();
        await page.getByLabel("Referencia única de transferencia", { exact: false }).fill(`BULLET-DES-${policy}`);
        await page.getByRole("checkbox").first().check();
        await page.getByLabel("Confirmo que registro una operación histórica sintética ya ocurrida; no solicito una transferencia nueva.", { exact: true }).check();
        await page.getByRole("button", { name: "Confirmar registro QA", exact: true }).click();
        await expect(page).toHaveURL(/\/creditos\/\d+$/);
        const creditUrl = page.url();
        await expect(page.getByRole("heading", { name: "Captura histórica pendiente", exact: true })).toBeVisible();
        async function registrar(fechaPago, importe, n) {
            await page.goto(creditUrl, { waitUntil: "domcontentloaded" });
            await page.getByRole("link", { name: policy === "periodicos" ? "Registrar pago QA" : "Anticipos y liquidación", exact: true }).click();
            await page.getByLabel("Fecha efectiva", { exact: false }).fill(fechaPago);
            await page.getByLabel(policy === "periodicos" ? "Importe recibido (MXN)" : "Total recibido (MXN)", { exact: false }).pressSequentially(importe);
            await page.getByLabel(policy === "periodicos" ? "Referencia única del pago" : "Referencia única", { exact: false }).fill("HIST-" + policy + "-" + n);
            await page.getByRole("button", { name: policy === "periodicos" ? "Revisar distribución" : "Revisar efecto y distribución", exact: true }).click();
            await page.getByRole("checkbox").check();
            await page.getByRole("button", { name: policy === "periodicos" ? "Confirmar pago QA" : "Confirmar operación QA", exact: true }).click();
            await expect(page.getByRole("heading", { name: /^Recibo interno/ })).toBeVisible();
        }
        if (policy === "periodicos") {
            await registrar(vencimientos[0], "100", 1);
            await registrar(addDays(vencimientos[0], 2), "50", 2);
        } else {
            await registrar(addDays(fixture.fecha, 15), "3120", 1);
            await registrar(addDays(fixture.fecha, 16), "1000", 2);
        }
        async function verificarSaldo(reversado) {
            const version = JSON.parse(await page.locator("#app").getAttribute("data-page")).version;
            const respuesta = await page.request.get(creditUrl, { headers: { "X-Inertia": "true", "X-Inertia-Version": version, "X-Requested-With": "XMLHttpRequest" } });
            expect(respuesta.ok()).toBe(true);
            const props = (await respuesta.json()).props;
            const esperado = oracle(fixture, policy, reversado);
            for (const [campo, valor] of Object.entries(esperado)) expect(props.situacion.resumen[campo], campo).toBe(valor);
            expect(props.desembolso.fecha_efectiva.slice(0, 10)).toBe(fixture.fecha);
            expect(props.desembolso.created_at.slice(0, 10)).not.toBe(fixture.fecha);
            expect(props.capturaHistorica.estado).toBe("pendiente");
        }
        await verificarSaldo(false);
        await page.getByRole("button", { name: "Revertir último pago", exact: true }).click();
        await page.getByLabel("Motivo del reverso", { exact: false }).fill("Corrección sintética del último movimiento histórico.");
        await page.getByRole("checkbox").check();
        await page.getByRole("button", { name: "Confirmar reverso QA", exact: true }).click();
        await expect(page.getByRole("heading", { name: /^Reverso/ })).toBeVisible();
        await verificarSaldo(true);
        await page.goto(creditUrl, { waitUntil: "domcontentloaded" });
        await page.screenshot({ path: testInfo.outputPath("historico-" + policy + "-desktop.png"), fullPage: true });
        await page.setViewportSize({ width: 390, height: 844 });
        await expect.poll(() => page.evaluate(() => document.documentElement.scrollWidth <= innerWidth)).toBe(true);
        await page.screenshot({ path: testInfo.outputPath("historico-" + policy + "-mobile.png"), fullPage: true });
        const panel = page.locator('[aria-label="Captura histórica"]');
        await panel.getByRole("checkbox").nth(0).check();
        await panel.getByRole("checkbox").nth(1).check();
        await panel.getByRole("button", { name: "Completar captura histórica", exact: true }).click();
        await expect(page.getByText("Captura histórica completada el", { exact: false })).toBeVisible();
        await expect(page.getByRole("heading", { name: "Captura histórica pendiente", exact: true })).toHaveCount(0);
        const conservado = await page.request.get(originalPdfUrl);
        expect(await conservado.body()).toEqual(originalPdf);
        assertConsole();
    });
}
