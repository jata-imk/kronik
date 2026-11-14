import { flushPromises, mount } from "@vue/test-utils";
import { afterEach, beforeEach, expect, it, vi } from "vitest";
import OperatorNotificationBell from "./OperatorNotificationBell.vue";

const { get, patch } = vi.hoisted(() => ({ get: vi.fn(), patch: vi.fn() }));
vi.mock("axios", () => ({ default: { get, patch } }));
vi.mock("@inertiajs/vue3", () => ({
    usePage: () => ({
        props: { contextoOperativo: { team_id: 1, sucursal_id: 2 } },
    }),
    Link: { props: ["href"], template: '<a :href="href"><slot /></a>' },
}));

beforeEach(() => {
    globalThis.route = vi.fn((name, id) =>
        id ? `/notificaciones/${id}/leer` : `/notificaciones/${name}`,
    );
    get.mockReset();
    patch.mockReset();
});
afterEach(() => vi.restoreAllMocks());

it("muestra el contador y marca leído el aviso propio sin confundirlo con el evento de negocio", async () => {
    const item = {
        id: 7,
        title: "Solicitud asignada",
        body: "Se te asignó una solicitud.",
        created_at: "2026-11-14T12:00:00Z",
        read_at: null,
    };
    get.mockResolvedValue({ data: { unread_count: 1, items: [item] } });
    patch.mockResolvedValue({
        data: {
            unread_count: 0,
            items: [{ ...item, read_at: "2026-11-14T12:01:00Z" }],
        },
    });
    const wrapper = mount(OperatorNotificationBell, {
        global: { mocks: { route: globalThis.route } },
    });
    await flushPromises();
    expect(
        wrapper.get('button[aria-label="Notificaciones: 1 sin leer"]').exists(),
    ).toBe(true);
    await wrapper
        .get('button[aria-label="Notificaciones: 1 sin leer"]')
        .trigger("click");
    await flushPromises();
    expect(wrapper.text()).toContain("Solicitud asignada");
    await wrapper.get("#operator-notification-menu button").trigger("click");
    await flushPromises();
    expect(patch).toHaveBeenCalledWith("/notificaciones/7/leer");
    expect(
        wrapper.get('button[aria-label="Notificaciones: 0 sin leer"]').exists(),
    ).toBe(true);
    wrapper.unmount();
});
