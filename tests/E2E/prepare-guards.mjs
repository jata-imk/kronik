import assert from "node:assert/strict";
import { randomUUID } from "node:crypto";
import { existsSync, readFileSync, unlinkSync, writeFileSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { test } from "node:test";
import { e2eEnvironment, prepareE2eDatabase } from "./prepare.js";

test("la preparación rechaza un entorno operativo antes de escribir", () => {
    assert.throws(() => prepareE2eDatabase({ APP_ENV: "production", E2E_DATABASE: "true" }), /APP_ENV=e2e/);
    assert.throws(() => prepareE2eDatabase({ APP_ENV: "e2e", E2E_DATABASE: "false" }), /E2E_DATABASE=true/);
});

test("la captura histórica no puede combinarse con reloj Bullet", () => {
    const original = { ...process.env };
    try {
        process.env.E2E_HISTORICO_FIXTURES = "true";
        process.env.E2E_BULLET_FIXTURES = "true";
        assert.throws(e2eEnvironment, /reloj real/);
    } finally {
        process.env = original;
    }
});

test("una base nueva rechaza rutas externas y conexiones reutilizables", () => {
    const base = { APP_ENV: "e2e", E2E_DATABASE: "true", E2E_FRESH_DATABASE: "true" };
    const external = fileURLToPath(new URL(`../../storage/kronik-e2e-${randomUUID()}.sqlite`, import.meta.url));
    assert.throws(() => prepareE2eDatabase({ ...base, DB_CONNECTION: "sqlite", DB_DATABASE: external }), /Ruta SQLite E2E insegura/);
    assert.equal(existsSync(external), false);
    assert.throws(() => prepareE2eDatabase({ ...base, DB_CONNECTION: "mysql", DB_DATABASE: "kronik_e2e" }), /SQLite dedicada/);
});

test("la apertura exclusiva conserva intacta una base ya existente", () => {
    const database = fileURLToPath(new URL(`../../storage/framework/testing/kronik-e2e-${randomUUID()}.sqlite`, import.meta.url));
    writeFileSync(database, "Evidencia sintética que debe conservarse", { flag: "wx" });
    try {
        assert.throws(() => prepareE2eDatabase({
            APP_ENV: "e2e", E2E_DATABASE: "true", E2E_FRESH_DATABASE: "true",
            DB_CONNECTION: "sqlite", DB_DATABASE: database,
        }), { code: "EEXIST" });
        assert.equal(readFileSync(database, "utf8"), "Evidencia sintética que debe conservarse");
    } finally {
        // Only the unique file created by this test, never a directory or an operational DB.
        unlinkSync(database);
    }
});
