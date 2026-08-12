import { execFileSync } from "node:child_process";
import { closeSync, existsSync, openSync, rmSync } from "node:fs";
import { resolve } from "node:path";
import { fileURLToPath } from "node:url";

export function e2eEnvironment() {
    const localDatabase = fileURLToPath(
        new URL("../../storage/framework/testing/kronik-e2e.sqlite", import.meta.url),
    );

    return {
        APP_ENV: "e2e",
        APP_URL: process.env.E2E_BASE_URL ?? "http://127.0.0.1:8015",
        APP_DEBUG: "false",
        DEBUGBAR_ENABLED: "false",
        APP_KEY: "base64:VdUXfE4mVbQd4D4QnS2SGdlx1DsFJsPhlfLCMyn5LIk=",
        E2E_DATABASE: "true",
        GEOCODING_ENABLED: "false",
        DB_CONNECTION: process.env.E2E_DB_CONNECTION ?? "sqlite",
        DB_DATABASE: process.env.E2E_DB_DATABASE ?? localDatabase,
        DB_HOST: process.env.E2E_DB_HOST ?? "127.0.0.1",
        DB_PORT: process.env.E2E_DB_PORT ?? "3306",
        DB_USERNAME: process.env.E2E_DB_USERNAME ?? "",
        DB_PASSWORD: process.env.E2E_DB_PASSWORD ?? "",
        CACHE_STORE: "array",
        MAIL_MAILER: "array",
        QUEUE_CONNECTION: "sync",
        SESSION_DRIVER: "file",
        BCRYPT_ROUNDS: "4",
    };
}

export function prepareE2eDatabase(environment = e2eEnvironment()) {
    const root = fileURLToPath(new URL("../..", import.meta.url));
    const hotFile = fileURLToPath(new URL("../../public/hot", import.meta.url));
    const safeLocalDatabase = fileURLToPath(
        new URL("../../storage/framework/testing/kronik-e2e.sqlite", import.meta.url),
    );

    if (environment.E2E_DATABASE !== "true" || environment.APP_ENV !== "e2e") {
        throw new Error("La preparación E2E requiere APP_ENV=e2e y E2E_DATABASE=true.");
    }

    // Force Laravel to use the production build. A stale Vite hot file would
    // point browser tests at a developer server that may not be running.
    if (existsSync(hotFile)) rmSync(hotFile);

    if (environment.DB_CONNECTION === "sqlite") {
        const database = environment.DB_DATABASE;
        if (resolve(database) !== resolve(safeLocalDatabase)) {
            throw new Error(`Ruta SQLite E2E insegura: ${database}`);
        }
        if (existsSync(database)) rmSync(database);
        closeSync(openSync(database, "w"));
    } else if (
        environment.DB_CONNECTION !== "mysql" ||
        environment.DB_DATABASE !== "kronik_e2e"
    ) {
        throw new Error("MySQL E2E solo puede usar la base kronik_e2e.");
    }

    execFileSync(
        "php",
        ["artisan", "migrate:fresh", "--seed", "--seeder=E2eSeeder", "--force"],
        {
            cwd: root,
            env: { ...process.env, ...environment },
            stdio: "inherit",
        },
    );

    return environment;
}
