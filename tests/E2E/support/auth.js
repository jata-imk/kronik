import { expect } from "@playwright/test";

export const users = {
    superAdmin: { email: "test@example.com", password: "password" },
    reader: { email: "consulta.clientes@example.test", password: "password" },
    editor: { email: "editor.expedientes@example.test", password: "password" },
    denied: { email: "sin.acceso.clientes@example.test", password: "password" },
};

export async function login(page, user = users.superAdmin) {
    await page.goto("/login");
    await page.locator("#email").fill(user.email);
    await page.locator("#password").fill(user.password);
    await page.getByRole("button", { name: /log in/i }).click();
    await expect(page).toHaveURL(/\/dashboard$/);
}

export async function failOnConsoleErrors(page) {
    await page.route("https://fonts.bunny.net/**", (route) =>
        route.fulfill({ contentType: "text/css", body: "" }),
    );
    await page.route("https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/assets/svg/**", (route) =>
        route.fulfill({
            contentType: "image/svg+xml",
            body: '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1 1" />',
        }),
    );
    await page.route(/https:\/\/[a-c]\.tile\.openstreetmap\.org\/.*/, (route) =>
        route.fulfill({
            contentType: "image/png",
            body: Buffer.from(
                "iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=",
                "base64",
            ),
        }),
    );
    await page.route("**/geocoding/search?**", (route) =>
        route.fulfill({
            contentType: "application/json",
            body: JSON.stringify([
                {
                    lat: "19.4326",
                    lon: "-99.1332",
                    boundingbox: ["19.3", "19.5", "-99.2", "-99.0"],
                },
            ]),
        }),
    );

    const errors = [];
    page.on("console", (message) => {
        const externalResource = message.location().url;
        const isKnownExternalResource =
            externalResource.startsWith("https://fonts.bunny.net/") ||
            externalResource.startsWith("https://cdn.jsdelivr.net/gh/twitter/twemoji@14.0.2/");

        if (message.type() === "error" && !isKnownExternalResource) {
            errors.push(message.text());
        }
    });
    page.on("pageerror", (error) => errors.push(error.message));

    return () => expect(errors, "La consola del navegador contiene errores").toEqual([]);
}
