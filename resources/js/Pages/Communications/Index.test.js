import { shallowMount } from "@vue/test-utils";
import { reactive } from "vue";
import { beforeEach, expect, it, vi } from "vitest";
import Index from "./Index.vue";

const { sent, cancelled } = vi.hoisted(() => ({ sent: vi.fn(), cancelled: vi.fn() }));
vi.mock("@sakai-vue/layout/AppLayout.vue", () => ({ default: { template: "<main><slot /></main>" } }));
vi.mock("@inertiajs/vue3", () => ({
    Link: { template: "<a><slot /></a>" },
    router: { get: vi.fn() },
    useForm: (data) => {
        let defaults = { ...data };
        let transform = (value) => value;
        const form = reactive({ ...data, errors: {}, processing: false });
        Object.assign(form, {
            cancel: cancelled,
            clearErrors: () => { form.errors = {}; },
            defaults: (value) => { defaults = { ...value }; },
            reset: () => Object.assign(form, defaults),
            transform: (callback) => { transform = callback; return form; },
            put: (url) => sent(url, transform(Object.fromEntries(Object.keys(defaults).map((key) => [key, form[key]])))),
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
            channels: [{ id: 1, channel: "sms", provider: "labsmobile", enabled: false, eligibility: "testing", has_secret: true }],
            capabilities: {}, rules: [], consents: [], deliveries: { data: [] }, filters: {},
            ruleDefaults: { leadDays: 3, maxLeadDays: 30, timezone: "America/Mexico_City", windowStart: "09:00", windowEnd: "20:00", dailyLimit: 1 },
            smsConfiguration: { configured: true, credentials: true, sender: true, sending_enabled: false },
            canManage: true, canTest: true, sendingEnabled: false, ...permissions,
        },
        global: { renderStubDefaultSlot: true, mocks: { route: (name) => name } },
    });
}

it("SMS muestra presencia y envía solo decisiones operativas", async () => {
    const wrapper = render();
    await wrapper.find("select").setValue("sms");
    expect(wrapper.get('[data-testid="sms-environment"]').text()).toContain("Conexión: configurada");
    expect(wrapper.find("textarea").exists()).toBe(false);
    expect(wrapper.text()).toContain("Los envíos SMS están desactivados");
    await wrapper.find("form").trigger("submit");
    expect(sent).toHaveBeenCalledWith("comunicaciones.channels.update", {
        channel: "sms", enabled: false, eligibility: "testing", eligibility_reference: "",
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
