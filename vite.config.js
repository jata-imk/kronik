import { fileURLToPath, URL } from "node:url";
import { defineConfig } from "vitest/config";

import Components from "unplugin-vue-components/vite";
import { PrimeVueResolver } from "@primevue/auto-import-resolver";

import laravel from "laravel-vite-plugin";
import vue from "@vitejs/plugin-vue";

export default defineConfig({
    plugins: [
        laravel({
            input: "resources/js/app.js",
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        Components({
            resolvers: [PrimeVueResolver()],
        }),
    ],
    resolve: {
        alias: {
            "@": fileURLToPath(new URL("./resources/js", import.meta.url)),
            "@css": fileURLToPath(new URL("./resources/css", import.meta.url)),
            "@config": fileURLToPath(new URL("./resources/config", import.meta.url)),
            "@services": fileURLToPath(new URL("./resources/js/Services", import.meta.url)),
            "@components": fileURLToPath(new URL("./resources/js/Components", import.meta.url)),
            "@composables": fileURLToPath(new URL("./resources/js/Composables", import.meta.url)),
            "@sakai-vue": fileURLToPath(
                new URL("./resources/external/sakai-vue", import.meta.url),
            ),
        },
    },
    test: {
        environment: "jsdom",
        setupFiles: ["resources/js/tests/setup.js"],
    },
});
