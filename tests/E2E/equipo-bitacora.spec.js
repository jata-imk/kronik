import { expect, test } from "@playwright/test";
import { readFile } from "node:fs/promises";
import { failOnConsoleErrors, login } from "./support/auth.js";

test("equipo muestra tarjetas cuadradas, acciones horizontales y bitácora filtrada", async ({ page }, testInfo) => {
    test.setTimeout(90_000);
    await login(page);
    const assertNoConsoleErrors = await failOnConsoleErrors(page);
    await page.goto("/admin/teams");
    await page.getByRole("link", { name: "Abrir configuración de Operaciones E2E", exact: true }).click();
    const teamUrl = page.url();
    const teamId = teamUrl.split("/").at(-1);
    const roles = page.getByRole("list", { name: "Roles del equipo" });
    await expect(roles.locator("li")).toHaveCount(4);
    await expect(roles.locator("li").first().getByRole("link", { name: "Administrar roles", exact: true })).toBeVisible();
    await expect(roles.getByText("Rol vacío E2E", { exact: true })).toHaveCount(0);
    await expect(page.getByText("Actividad reciente", { exact: true })).toHaveCount(0);
    const checkSquares = async () => {
        for (const card of await roles.locator("li").all()) {
            const box = await card.boundingBox();
            expect(Math.abs(box.width - box.height)).toBeLessThanOrEqual(2);
        }
    };
    await checkSquares();
    for (const width of [320, 768, 1024, 1440]) {
        await page.setViewportSize({ width, height: 900 });
        await checkSquares();
    }
    await page.setViewportSize({ width: 1280, height: 900 });
    const actions = page.getByRole("row").filter({ hasText: "integrante.acciones@example.test" }).getByRole("button");
    const boxes = await Promise.all((await actions.all()).map((button) => button.boundingBox()));
    expect(boxes.length).toBeGreaterThan(1);
    expect(Math.max(...boxes.map((box) => box.y)) - Math.min(...boxes.map((box) => box.y))).toBeLessThanOrEqual(2);
    await page.screenshot({ path: testInfo.outputPath("equipo-tarjetas-desktop.png"), animations: "disabled", fullPage: true });
    await page.evaluate(() => document.documentElement.classList.add("app-dark"));
    await page.screenshot({ path: testInfo.outputPath("equipo-tarjetas-dark.png"), animations: "disabled", fullPage: true });
    await page.evaluate(() => document.documentElement.classList.remove("app-dark"));
    await page.getByRole("link", { name: "Ver bitácora del equipo", exact: true }).click();
    await expect(page).toHaveURL(new RegExp(`team_id=${teamId}`));
    await page.locator("#activity-branch").click();
    await page.getByRole("option", { name: "NORTE · Sucursal Norte", exact: true }).click();
    await expect(page).toHaveURL(/sucursal_id=\d+/);
    await expect(page.getByText("Actividad del equipo Operaciones E2E", { exact: true })).toBeVisible();
    await expect(page.getByRole("row").filter({ hasText: "Actividad de equipo E2E" })).toHaveCount(1);
    const downloadPromise = page.waitForEvent("download");
    await page.getByRole("button", { name: "Exportar CSV", exact: true }).click();
    const download = await downloadPromise;
    const csv = await readFile(await download.path(), "utf8");
    expect(csv).toContain("Operaciones E2E");
    expect(csv).toContain("Sucursal Norte");
    expect(csv).not.toContain("Actividad determinista E2E");
    await page.screenshot({ path: testInfo.outputPath("bitacora-contextos-desktop.png"), animations: "disabled", fullPage: true });
    await page.getByRole("button", { name: "Limpiar", exact: true }).click();
    await expect(page).not.toHaveURL(/sucursal_id=\d+/);
    await expect(page).toHaveURL(new RegExp(`team_id=${teamId}`));
    await page.locator("#activity-team").click();
    const otherTeam = (await page.getByRole("option").allTextContents()).find((name) => name.trim() !== "Operaciones E2E").trim();
    await page.getByRole("option", { name: otherTeam, exact: true }).click();
    await expect(page.getByText(`Actividad del equipo ${otherTeam}`, { exact: true })).toBeVisible();
    await page.getByRole("link", { name: "Volver", exact: true }).click();
    await expect(page).toHaveURL(/\/teams\/\d+$/);
    expect(page.url()).not.toBe(teamUrl);
    await page.goto(teamUrl);
    await page.setViewportSize({ width: 390, height: 844 });
    await checkSquares();
    await page.screenshot({ path: testInfo.outputPath("equipo-tarjetas-mobile.png"), animations: "disabled", fullPage: true });
    expect(await page.evaluate(() => document.documentElement.scrollWidth <= window.innerWidth + 1)).toBe(true);
    assertNoConsoleErrors();
});
