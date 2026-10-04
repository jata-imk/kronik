import { definePreset } from "@primevue/themes";
import Aura from "@primevue/themes/aura";

// The initial palette is shared with the appearance configurator; users can replace it.
export const KronikPrimaryPalette = Object.freeze({
    50: "#ecfdf5",
    100: "#d1fae5",
    200: "#a7f3d0",
    300: "#6ee7b7",
    400: "#34d399",
    500: "#047857",
    600: "#047857",
    700: "#065f46",
    800: "#064e3b",
    900: "#022c22",
    950: "#011c16",
});

export const KronikPreset = definePreset(Aura, {
    semantic: {
        primary: KronikPrimaryPalette,
        colorScheme: {
            light: {
                primary: {
                    color: "{primary.500}",
                    contrastColor: "#ffffff",
                    hoverColor: "{primary.700}",
                    activeColor: "{primary.800}",
                },
            },
            dark: {
                primary: {
                    color: "{primary.400}",
                    contrastColor: "#0f172a",
                    hoverColor: "{primary.300}",
                    activeColor: "{primary.200}",
                },
            },
        },
        focusRing: {
            width: "2px",
            style: "solid",
            color: "{primary.color}",
            offset: "2px",
        },
    },
});
