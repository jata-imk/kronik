import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import DocumentVersionStatus from "./DocumentVersionStatus.vue";

describe("DocumentVersionStatus", () => {
    it("presenta una versión activa con una etiqueta legible", () => {
        const wrapper = mount(DocumentVersionStatus, {
            props: { status: "activa" },
            global: { stubs: { Tag: { template: "<span><slot /></span>" } } },
        });
        expect(wrapper.text()).toContain("Activa");
        expect(wrapper.find(".pi-check-circle").exists()).toBe(true);
    });
});
