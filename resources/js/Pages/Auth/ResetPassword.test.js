import { mount } from "@vue/test-utils";
import { describe, expect, it, vi } from "vitest";
import ResetPassword from "./ResetPassword.vue";

const post = vi.fn();
vi.mock("@inertiajs/vue3", () => ({ Head: { template: "<span />" }, useForm: (data) => ({ ...data, errors: {}, post, reset: vi.fn() }) }));
vi.stubGlobal("route", (name) => name);
const passthrough = { template: "<div><slot /></div>" };

describe("Aceptación y recuperación", () => {
    it.each([[true, "invitations.accept", "Crear mi contraseña"], [false, "password.update", "Restablecer contraseña"]])("selecciona el destino del formulario según su flujo (%s)", async (invitation, target, label) => {
        const wrapper = mount(ResetPassword, { props: { invitation, email: "invitada@example.test", token: "token" }, global: { stubs: { AuthenticationCard: passthrough, AuthenticationCardLogo: true, PrimaryButton: { template: "<button><slot /></button>" }, InputLabel: true, InputError: true, TextInput: true } } });
        expect(wrapper.text()).toContain(label);
        await wrapper.get("form").trigger("submit");
        expect(post.mock.lastCall[0]).toBe(target);
    });
});
