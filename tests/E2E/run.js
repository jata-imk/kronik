import { spawn } from "node:child_process";
import { fileURLToPath } from "node:url";
import { e2eEnvironment, prepareE2eDatabase } from "./prepare.js";

const root = fileURLToPath(new URL("../..", import.meta.url));
const publicRoot = fileURLToPath(new URL("../../public", import.meta.url));
const environment = prepareE2eDatabase(e2eEnvironment());
const port = process.env.E2E_PORT ?? "8015";
const baseURL = process.env.E2E_BASE_URL ?? `http://127.0.0.1:${port}`;
const router = fileURLToPath(
    new URL("../../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php", import.meta.url),
);

const server = spawn("php", ["-S", `127.0.0.1:${port}`, "-t", ".", router], {
    cwd: publicRoot,
    env: { ...process.env, ...environment },
    stdio: "ignore",
});

async function waitForServer() {
    const deadline = Date.now() + 120_000;
    while (Date.now() < deadline) {
        try {
            const response = await fetch(`${baseURL}/login`);
            if (response.ok) return;
        } catch {
            // Laravel todavía está iniciando.
        }
        await new Promise((resolve) => setTimeout(resolve, 250));
    }
    throw new Error("Laravel E2E no inició dentro del tiempo esperado.");
}

let exitCode = 1;
try {
    await waitForServer();
    const playwrightCli = fileURLToPath(
        new URL("../../node_modules/@playwright/test/cli.js", import.meta.url),
    );
    const runner = spawn(
        process.execPath,
        [playwrightCli, "test", ...process.argv.slice(2)],
        {
            cwd: root,
            env: {
                ...process.env,
                ...environment,
                E2E_MANAGED_SERVER: "true",
                E2E_BASE_URL: baseURL,
            },
            stdio: "inherit",
        },
    );
    exitCode = await new Promise((resolve) => runner.on("exit", (code) => resolve(code ?? 1)));
} finally {
    server.kill("SIGTERM");
}

process.exit(exitCode);
