import { mount } from "@vue/test-utils";
import { describe, expect, it } from "vitest";
import ProductVersionStatus from "./ProductVersionStatus.vue";

const Tag = { props: ["value", "severity"], template: '<span :data-severity="severity">{{ value }}</span>' };

describe("ProductVersionStatus", () => {
    it("distingue una versión activa utilizada", () => {
        const wrapper = mount(ProductVersionStatus, { props: { state: "activa", used: true }, global: { components: { Tag } } });

        expect(wrapper.text()).toContain("Activa");
        expect(wrapper.text()).toContain("En uso");
        expect(wrapper.findComponent({ name: "Tag" }).props("severity")).toBe("success");
    });

    it("presenta el borrador como estado neutral y editable", () => {
        const wrapper = mount(ProductVersionStatus, { props: { state: "borrador" }, global: { components: { Tag } } });

        expect(wrapper.text()).toContain("Borrador");
        expect(wrapper.text()).not.toContain("En uso");
    });
});
