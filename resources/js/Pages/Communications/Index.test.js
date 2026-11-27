import { shallowMount } from "@vue/test-utils";
import { reactive } from "vue";
import { beforeEach, expect, it, vi } from "vitest";
import Index from "./Index.vue";

const { sent, cancelled } = vi.hoisted(() => ({
    sent: vi.fn(),
    cancelled: vi.fn(),
}));
vi.mock("@sakai-vue/layout/AppLayout.vue", () => ({
    default: { template: "<main><slot /></main>" },
}));
vi.mock("@inertiajs/vue3", () => ({
    Link: { template: "<a><slot /></a>" },
    router: { get: vi.fn() },
    useForm: (data) => {
        let defaults = { ...data };
        let transform = (value) => value;
        const form = reactive({ ...data, errors: {}, processing: false });
        Object.assign(form, {
            cancel: cancelled,
            clearErrors: () => {
                form.errors = {};
            },
            defaults: (value) => {
                defaults = { ...value };
            },
            reset: () => Object.assign(form, defaults),
            transform: (callback) => {
                transform = callback;
                return form;
            },
            put: (url) =>
                sent(
                    url,
                    transform(
                        Object.fromEntries(
                            Object.keys(defaults).map((key) => [
                                key,
                                form[key],
                            ]),
                        ),
                    ),
                ),
            post: sent,
        });
        return form;
    },
}));

beforeEach(() => {
    sent.mockClear();
    cancelled.mockClear();
    vi.stubGlobal("route", (name) => name);
});

function render(permissions = {}) {
    return shallowMount(Index, {
        props: {
            channels: [
                {
                    id: 1,
                    channel: "sms",
                    provider: "labsmobile",
                    enabled: false,
                    eligibility: "testing",
                    has_secret: true,
                },
            ],
            capabilities: {},
            rules: [],
            consents: [],
            deliveries: { data: [] },
            filters: {},
            ruleDefaults: {
                leadDays: 3,
                maxLeadDays: 30,
                timezone: "America/Mexico_City",
                windowStart: "09:00",
                windowEnd: "20:00",
                dailyLimit: 1,
            },
            smsConfiguration: {
                configured: true,
                credentials: true,
                sender: true,
                sending_enabled: false,
            },
            canManage: true,
            canTest: true,
            sendingEnabled: false,
            ...permissions,
        },
        global: {
            renderStubDefaultSlot: true,
            mocks: { route: (name) => name },
        },
    });
}

it("explica el contacto cambiado sin presentar la autorización como vigente ni habilitar escritura al lector", async () => {
    const wrapper = render({
        canManage: false,
        canTest: false,
        consents: [
            {
                id: 1,
                cliente_id: 2,
                channel: "email",
                purpose: "operational",
                granted_at: "2026-11-26T12:00:00Z",
                revoked_at: null,
                effective_status: "contact_changed",
            },
        ],
    });
    await wrapper
        .findAll("button")
        .find((button) => button.text() === "Consentimientos")
        .trigger("click");
    expect(wrapper.text()).toContain(
        "Contacto cambiado; requiere nueva autorización",
    );
    expect(wrapper.text()).not.toContain("Vigente");
    expect(wrapper.find("form").exists()).toBe(false);
    expect(wrapper.text()).not.toContain("Revocar");
});

it("SMS muestra presencia y envía solo decisiones operativas", async () => {
    const wrapper = render();
    await wrapper.find("select").setValue("sms");
    expect(wrapper.get('[data-testid="sms-environment"]').text()).toContain(
        "Conexión: configurada",
    );
    expect(wrapper.find("textarea").exists()).toBe(false);
    expect(wrapper.text()).toContain("Los envíos SMS están desactivados");
    await wrapper.find("form").trigger("submit");
    expect(sent).toHaveBeenCalledWith("comunicaciones.channels.update", {
        channel: "sms",
        enabled: false,
        eligibility: "testing",
        eligibility_reference: "",
    });
});

it("cambiar de canal descarta credenciales Meta y cancela solicitudes pendientes", async () => {
    const wrapper = render();
    await wrapper.find("select").setValue("whatsapp");
    await wrapper.find("textarea").setValue("SYNTHETIC-META-SECRET");
    await wrapper.find("select").setValue("sms");
    expect(wrapper.find("textarea").exists()).toBe(false);
    await wrapper.find("select").setValue("whatsapp");
    expect(wrapper.find("textarea").element.value).toBe("");
    expect(cancelled).toHaveBeenCalled();
});

it("consulta no ofrece ni ejecuta configuración por teclado o manejador", async () => {
    const wrapper = render({ canManage: false, canTest: false });
    expect(wrapper.find("form").exists()).toBe(false);
    await wrapper.trigger("keydown", { key: "Enter" });
    wrapper.vm.saveChannel();
    wrapper.vm.saveRule();
    wrapper.vm.checkConnection();
    expect(sent).not.toHaveBeenCalled();
});

it("recargar la misma selección conserva errores y retirar permiso cancela edición", async () => {
    const wrapper = render();
    await wrapper.find("select").setValue("sms");
    wrapper.vm.channelForm.errors.eligibility = "Revisa la elegibilidad";
    await wrapper.setProps({ channels: [{ ...wrapper.props("channels")[0] }] });
    expect(wrapper.text()).toContain("Revisa la elegibilidad");
    await wrapper.setProps({ canManage: false, canTest: false });
    expect(wrapper.find("form").exists()).toBe(false);
    wrapper.vm.saveChannel();
    expect(sent).not.toHaveBeenCalled();
});

it("una validación de regla conserva captura y errores hasta cambiar contexto o permisos", async () => {
    const rule = {
        id: 4,
        event_type: "payment_received",
        channel: "email",
        enabled: true,
        timezone: "America/Mexico_City",
        window_start: "09:00:00",
        window_end: "20:00:00",
        daily_limit: 1,
        notification_template_id: 8,
    };
    const wrapper = render({
        rules: [rule],
        contextoOperativo: { team_id: 1, sucursal_id: 1 },
    });
    wrapper.vm.ruleForm.window_end = "08:00";
    wrapper.vm.ruleForm.errors.window_end =
        "El fin debe ser posterior al inicio";
    await wrapper.setProps({ rules: [{ ...rule }] });
    expect(wrapper.vm.ruleForm.window_end).toBe("08:00");
    expect(wrapper.vm.ruleForm.errors.window_end).toContain("posterior");
    await wrapper.setProps({
        contextoOperativo: { team_id: 2, sucursal_id: 1 },
    });
    expect(wrapper.vm.ruleForm.window_end).toBe("20:00");
    expect(wrapper.vm.ruleForm.errors).toEqual({});
    await wrapper.setProps({ canManage: false });
    wrapper.vm.saveRule();
    expect(sent).not.toHaveBeenCalled();
});

it("una regla sin versión activa puede desactivarse sin elegir reemplazo", async () => {
    const wrapper = render({
        activeTab: "rules",
        rules: [
            {
                id: 4,
                event_type: "payment_received",
                channel: "email",
                enabled: true,
                notification_template_id: 8,
                notification_template: {
                    name: "Aviso retirado",
                    active_version: null,
                },
            },
        ],
    });
    const selector = wrapper
        .findAll("select")
        .find((item) =>
            item.text().includes("Seleccione una plantilla activa"),
        );
    expect(selector.text()).toContain("Aviso retirado · Sin versión activa");
    expect(selector.attributes("required")).toBeDefined();
    wrapper.vm.ruleForm.enabled = false;
    await wrapper.vm.$nextTick();
    expect(selector.attributes("required")).toBeUndefined();
    wrapper.vm.saveRule();
    expect(sent).toHaveBeenCalledWith(
        "comunicaciones.rules.update",
        expect.objectContaining({
            enabled: false,
            notification_template_id: 8,
        }),
    );
});
