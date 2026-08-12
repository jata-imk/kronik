import { defineConfig, devices } from "@playwright/test";
import { fileURLToPath } from "node:url";

const root = fileURLToPath(new URL(".", import.meta.url));
const port = Number(process.env.E2E_PORT ?? 8015);
const baseURL = process.env.E2E_BASE_URL ?? `http://127.0.0.1:${port}`;
const localDatabase = fileURLToPath(
    new URL("./storage/framework/testing/kronik-e2e.sqlite", import.meta.url),
);

const e2eEnvironment = {
    APP_ENV: "e2e",
    APP_URL: baseURL,
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

export default defineConfig({
    testDir: "./tests/E2E",
    outputDir: "test-results",
    fullyParallel: false,
    workers: 1,
    retries: process.env.CI ? 1 : 0,
    reporter: [["list"], ["html", { open: "never" }]],
    use: {
        baseURL,
        trace: "retain-on-failure",
        screenshot: "only-on-failure",
        video: "retain-on-failure",
        locale: "es-MX",
        timezoneId: "America/Mexico_City",
    },
    projects: [
        {
            name: "chromium",
            use: { ...devices["Desktop Chrome"] },
        },
    ],
    webServer:
        process.env.E2E_MANAGED_SERVER === "true"
            ? undefined
            : {
                  command: `php artisan serve --host=127.0.0.1 --port=${port}`,
                  cwd: root,
                  env: { ...process.env, ...e2eEnvironment },
                  url: `${baseURL}/login`,
                  reuseExistingServer: false,
                  timeout: 120_000,
                  stdout: "pipe",
                  stderr: "pipe",
              },
    metadata: { e2eEnvironment },
});
