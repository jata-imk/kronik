import { execFileSync } from "node:child_process";
import { closeSync, existsSync, openSync, rmSync, writeFileSync } from "node:fs";
import { resolve, dirname, basename } from "node:path";
import { randomUUID } from "node:crypto";
import { fileURLToPath } from "node:url";

export function e2eEnvironment() {
    const historical = process.env.E2E_HISTORICO_FIXTURES === "true";
    if (historical && process.env.E2E_BULLET_FIXTURES === "true") {
        throw new Error("El recorrido histórico usa reloj real: desactiva E2E_BULLET_FIXTURES antes de iniciarlo.");
    }
    const fresh = historical || process.env.E2E_FRESH_DATABASE === "true";
    const cycle = historical || process.env.E2E_ORIGINACION_DUAL === "true" || process.env.E2E_BULLET_FIXTURES === "true";
    const localDatabase = fileURLToPath(
        new URL(fresh ? `../../storage/framework/testing/kronik-e2e-${randomUUID()}.sqlite` : "../../storage/framework/testing/kronik-e2e.sqlite", import.meta.url),
    );

    return {
        APP_ENV: "e2e",
        APP_URL: process.env.E2E_BASE_URL ?? "http://127.0.0.1:8015",
        APP_DEBUG: "false",
        DEBUGBAR_ENABLED: "false",
        APP_KEY: "base64:VdUXfE4mVbQd4D4QnS2SGdlx1DsFJsPhlfLCMyn5LIk=",
        E2E_DATABASE: "true",
        E2E_TABLERO_FIXTURES: process.env.E2E_TABLERO_FIXTURES === "true" ? "true" : "false",
        E2E_HISTORICO_FIXTURES: historical ? "true" : "false",
        E2E_FRESH_DATABASE: fresh ? "true" : "false",
        E2E_BULLET_FIXTURES: process.env.E2E_BULLET_FIXTURES === "true" ? "true" : "false",
        E2E_ORIGINACION_DUAL: process.env.E2E_ORIGINACION_DUAL === "true" ? "true" : "false",
        ORIGINACION_APROBACIONES_HABILITADAS: cycle ? "true" : "false",
        ORIGINACION_PAQUETES_QA_HABILITADOS: cycle ? "true" : "false",
        ORIGINACION_FIRMAS_QA_HABILITADAS: cycle ? "true" : "false",
        ORIGINACION_DESEMBOLSOS_QA_HABILITADOS: cycle ? "true" : "false",
        ORIGINACION_PAGOS_QA_HABILITADOS: cycle ? "true" : "false",
        ORIGINACION_ANTICIPOS_QA_HABILITADOS: cycle ? "true" : "false",
        E2E_SIC_FIXTURES: process.env.E2E_SIC_FIXTURES === "true" ? "true" : "false",
        SIC_QA_ESCENARIOS_HABILITADOS: process.env.E2E_SIC_FIXTURES === "true" ? "true" : "false",
        SIC_QA_HABILITADO: process.env.E2E_SIC_FIXTURES === "true" ? "true" : "false",
        SIC_CA_BUNDLE: "",
        CIRCULO_CREDITO_HOST: "https://services.circulodecredito.com.mx",
        CIRCULO_CREDITO_SANDBOX: "true",
        CIRCULO_CREDITO_API_KEY: "E2E-NO-PROVIDER-CREDENTIAL",
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
        // File mtime follows the OS clock; dated scenarios need session timestamps from Laravel.
        SESSION_DRIVER: process.env.E2E_BULLET_FIXTURES === "true" ? "database" : "file",
        SESSION_LIFETIME: process.env.E2E_BULLET_FIXTURES === "true" ? "525600" : "120",
        BCRYPT_ROUNDS: "4",
        DOCUMENTOS_NODE_BINARY: process.execPath,
        DOCUMENTOS_NPM_BINARY: "",
        DOCUMENTOS_NODE_MODULES_PATH: fileURLToPath(new URL("../../node_modules", import.meta.url)),
        DOCUMENTOS_CHROME_PATH: process.env.DOCUMENTOS_CHROME_PATH ?? "",
        DOCUMENTOS_DISK: "e2e_dual",
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
    if (existsSync(hotFile)) {
        if (environment.E2E_FRESH_DATABASE === "true") throw new Error("La prueba aislada requiere el build sin public/hot; no se eliminó el archivo existente.");
        rmSync(hotFile);
    }

    if (environment.E2E_FRESH_DATABASE === "true" && environment.DB_CONNECTION !== "sqlite") throw new Error("La base nueva por ejecución requiere SQLite dedicada.");
    if (environment.DB_CONNECTION === "sqlite") {
        const database = environment.DB_DATABASE;
        const fresh = environment.E2E_FRESH_DATABASE === "true";
        const safeFresh = dirname(resolve(database)) === dirname(resolve(safeLocalDatabase)) && /^kronik-e2e-[0-9a-f-]{36}\.sqlite$/.test(basename(database));
        if (fresh ? !safeFresh : resolve(database) !== resolve(safeLocalDatabase)) {
            throw new Error(`Ruta SQLite E2E insegura: ${database}`);
        }
        if (fresh) {
            // Exclusive creation: never truncate or reuse an existing database.
            closeSync(openSync(database, "wx"));
        } else {
            if (existsSync(database)) rmSync(database);
            closeSync(openSync(database, "w"));
        }
    } else if (
        environment.DB_CONNECTION !== "mysql" ||
        environment.DB_DATABASE !== "kronik_e2e"
    ) {
        throw new Error("MySQL E2E solo puede usar la base kronik_e2e.");
    }

    const prepend = [];
    if (environment.E2E_BULLET_FIXTURES === "true") {
        writeFileSync(resolve(root, "storage/framework/testing/bullet-e2e-clock.txt"), "2027-01-01T12:00:00-06:00\n");
        prepend.push("-d", `auto_prepend_file=${resolve(root, "tests/E2E/support/clock.php")}`);
    }
    execFileSync(
        "php",
        [...prepend, "artisan", environment.E2E_FRESH_DATABASE === "true" ? "migrate" : "migrate:fresh", "--seed", "--seeder=E2eSeeder", "--force"],
        {
            cwd: root,
            env: { ...process.env, ...environment },
            stdio: "inherit",
        },
    );

    return environment;
}
