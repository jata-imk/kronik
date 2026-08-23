import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import FinancialFieldHelp from "./FinancialFieldHelp.vue";

const Button = {
    props: ["ariaLabel"],
    emits: ["click"],
    template:
        '<button :aria-label="ariaLabel" @click="$emit(\'click\', $event)"><slot /></button>',
};
const Popover = {
    methods: { toggle() {} },
    template: "<section><slot /></section>",
};

describe("FinancialFieldHelp", () => {
    it("expone una ayuda accesible con explicación y ejemplo", () => {
        const wrapper = mount(FinancialFieldHelp, {
            props: {
                title: "Tasa anual",
                description: "Usa días reales entre 360.",
                example: "36% × 31/360",
            },
            global: { stubs: { Button, Popover } },
        });

        expect(wrapper.get("button").attributes("aria-label")).toBe(
            "Ayuda: Tasa anual",
        );
        expect(wrapper.text()).toContain("Usa días reales entre 360.");
        expect(wrapper.text()).toContain("36% × 31/360");
    });
});
