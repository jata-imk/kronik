import { mount } from "@vue/test-utils";
import { defineComponent, h } from "vue";
import { describe, expect, it } from "vitest";
import ActivityLogTable from "./ActivityLogTable.vue";

const log = {
    id: 17,
    causer: { name: "Ana Operadora", email: "ana@example.test" },
    created_at: "2026-08-12T12:00:00Z",
    event_label: "Actualización",
    event_severity: "info",
    event_icon: "pi-pencil",
    team: { name: "Operaciones" },
    sucursal: { clave: "MATRIZ", name: "Matriz" },
    description: "Actualizó el cliente",
    subject: { type: "Cliente", id: 8 },
    ip: "127.0.0.1",
    properties: { attributes: { status: "active" } },
};

const DataTableStub = defineComponent({
    setup(_, { slots }) {
        return () => h("div", { role: "table" }, slots.default?.());
    },
});
const ColumnStub = defineComponent({
    props: { header: String },
    setup(props, { slots }) {
        return () =>
            h("section", [
                props.header ? h("strong", props.header) : null,
                slots.body?.({ data: log }),
            ]);
    },
});
const DialogStub = defineComponent({
    props: { visible: Boolean, header: String },
    setup(props, { slots }) {
        return () =>
            props.visible
                ? h("section", { role: "dialog" }, [h("h2", props.header), slots.default?.()])
                : null;
    },
});
const ButtonStub = defineComponent({
    inheritAttrs: false,
    emits: ["click"],
    setup(_, { attrs, emit }) {
        return () =>
            h(
                "button",
                { "aria-label": attrs["aria-label"], onClick: () => emit("click") },
                attrs["aria-label"],
            );
    },
});
const ValueStub = defineComponent({
    props: { value: String, label: String },
    setup(props) {
        return () => h("span", props.value ?? props.label);
    },
});

const renderTable = () =>
    mount(ActivityLogTable, {
        props: { logs: [log] },
        global: {
            directives: { tooltip: () => {} },
            stubs: {
                Card: { template: "<div><slot name='content' /></div>" },
                DataTable: DataTableStub,
                Column: ColumnStub,
                Dialog: DialogStub,
                Button: ButtonStub,
                Tag: ValueStub,
                Avatar: ValueStub,
            },
        },
    });

describe("ActivityLogTable", () => {
    it("muestra equipo y sucursal como contexto de la actividad", () => {
        const wrapper = renderTable();

        expect(wrapper.text()).toContain("Operaciones");
        expect(wrapper.text()).toContain("MATRIZ · Matriz");
    });

    it("abre los detalles y propiedades del registro seleccionado", async () => {
        const wrapper = renderTable();

        await wrapper.get('[aria-label="Ver detalle de actividad 17"]').trigger("click");
        expect(wrapper.get('[role="dialog"]').text()).toContain("Cliente");
        expect(wrapper.get('[role="dialog"]').text()).toContain("Matriz");

        await wrapper.get('[aria-label="Ver propiedades de actividad 17"]').trigger("click");
        expect(wrapper.findAll('[role="dialog"]')[1].text()).toContain('"status": "active"');
    });
});
