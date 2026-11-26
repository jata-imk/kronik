import { enableAutoUnmount, flushPromises, mount } from "@vue/test-utils";
import { afterEach, beforeEach, expect, it, vi } from "vitest";
import { reactive } from "vue";
import OperatorNotificationBell from "./OperatorNotificationBell.vue";

const { get, patch, page } = vi.hoisted(() => ({
    get: vi.fn(),
    patch: vi.fn(),
    page: { props: {} },
}));
vi.mock("axios", () => ({ default: { get, patch } }));
vi.mock("@inertiajs/vue3", () => ({
    usePage: () => page,
    Link: { props: ["href"], template: '<a :href="href"><slot /></a>' },
}));

beforeEach(() => {
    globalThis.route = vi.fn((name, id) =>
        id ? `/notificaciones/${id}/leer` : `/notificaciones/${name}`,
    );
    get.mockReset();
    patch.mockReset();
    page.props = reactive({
        contextoOperativo: { team_id: 1, sucursal_id: 2 },
    });
});
enableAutoUnmount(afterEach);
afterEach(() => {
    vi.restoreAllMocks();
    vi.useRealTimers();
});

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

const first = {
    id: 1,
    title: "Primera",
    body: "Aviso uno",
    created_at: "2026-11-14T12:00:00Z",
    read_at: null,
};
const second = { ...first, id: 2, title: "Segunda" };
const readAt = "2026-11-14T12:01:00Z";
const bell = (wrapper) =>
    wrapper.get('button[aria-controls="operator-notification-menu"]');

async function openBell() {
    get.mockResolvedValue({
        data: { unread_count: 2, items: [first, second] },
    });
    const wrapper = mount(OperatorNotificationBell, {
        global: { mocks: { route: globalThis.route } },
    });
    await flushPromises();
    await bell(wrapper).trigger("click");
    await flushPromises();
    return wrapper;
}

it("serializa lecturas y evita duplicados y recargas mientras hay mutaciones pendientes", async () => {
    vi.useFakeTimers();
    const pending = [];
    patch.mockImplementation(
        () => new Promise((resolve) => pending.push(resolve)),
    );
    const wrapper = await openBell();
    const buttons = wrapper.findAll("#operator-notification-menu li button");
    await buttons[0].trigger("click");
    await buttons[0].trigger("click");
    await buttons[1].trigger("click");
    await flushPromises();
    expect(patch).toHaveBeenCalledTimes(1);
    vi.advanceTimersByTime(60000);
    await flushPromises();
    expect(get).toHaveBeenCalledTimes(2);
    pending[0]({
        data: {
            unread_count: 1,
            items: [{ ...first, read_at: readAt }, second],
        },
    });
    await flushPromises();
    expect(patch).toHaveBeenCalledTimes(2);
    pending[1]({
        data: {
            unread_count: 0,
            items: [
                { ...first, read_at: readAt },
                { ...second, read_at: readAt },
            ],
        },
    });
    await flushPromises();
    expect(bell(wrapper).attributes("aria-label")).toBe(
        "Notificaciones: 0 sin leer",
    );
});

it("descarta lecturas en cola y errores tardíos al cambiar de contexto", async () => {
    let rejectOld;
    patch.mockImplementation(
        () =>
            new Promise((resolve, reject) => {
                rejectOld = reject;
            }),
    );
    const wrapper = await openBell();
    const buttons = wrapper.findAll("#operator-notification-menu li button");
    await buttons[0].trigger("click");
    await buttons[1].trigger("click");
    await flushPromises();
    get.mockResolvedValue({ data: { unread_count: 0, items: [] } });
    page.props.contextoOperativo = { team_id: 3, sucursal_id: 4 };
    await flushPromises();
    rejectOld(new Error("respuesta del equipo anterior"));
    await flushPromises();
    expect(patch).toHaveBeenCalledTimes(1);
    expect(bell(wrapper).attributes("aria-label")).toBe(
        "Notificaciones: 0 sin leer",
    );
    await bell(wrapper).trigger("click");
    await flushPromises();
    expect(wrapper.text()).not.toContain("Primera");
    expect(wrapper.text()).not.toContain("No se pudieron cargar");
});

it("una consulta iniciada antes de marcar leído no restaura un resumen antiguo", async () => {
    const wrapper = await openBell();
    let resolveOld;
    get.mockImplementationOnce(
        () =>
            new Promise((resolve) => {
                resolveOld = resolve;
            }),
    );
    await bell(wrapper).trigger("click");
    await bell(wrapper).trigger("click");
    patch.mockResolvedValue({
        data: {
            unread_count: 1,
            items: [{ ...first, read_at: readAt }, second],
        },
    });
    await wrapper
        .findAll("#operator-notification-menu li button")[0]
        .trigger("click");
    await flushPromises();
    resolveOld({ data: { unread_count: 2, items: [first, second] } });
    await flushPromises();
    expect(bell(wrapper).attributes("aria-label")).toBe(
        "Notificaciones: 1 sin leer",
    );
});
