import { spawn } from "node:child_process";
import { fileURLToPath } from "node:url";
import { e2eEnvironment, prepareE2eDatabase } from "./prepare.js";

// Dedicated, disposable local database. Reuses the E2E launcher's safety checks.
const environment = prepareE2eDatabase(e2eEnvironment());
const publicRoot = fileURLToPath(new URL("../../public", import.meta.url));
const router = fileURLToPath(new URL("../../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php", import.meta.url));
const server = spawn("php", ["-S", "127.0.0.1:8015", "-t", ".", router], { cwd: publicRoot, env: { ...process.env, ...environment }, stdio: "inherit" });
process.on("SIGINT", () => server.kill());
process.on("SIGTERM", () => server.kill());
server.on("exit", code => process.exit(code ?? 0));
