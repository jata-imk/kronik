import { flushPromises, shallowMount } from "@vue/test-utils";
import { afterEach, expect, it, vi } from "vitest";
import ApiTokenManager from "./ApiTokenManager.vue";
import { forgetUnsavedChanges, hasUnsavedChanges } from "@/Utils/unsavedChanges";

let wrapper;
afterEach(() => { wrapper?.unmount(); forgetUnsavedChanges(); vi.restoreAllMocks(); });
it("abrir permisos no ensucia y cancelar conserva o descarta la captura explícitamente", async () => {
    const token = { id: 1, name: "Integración sintética", abilities: ["read"] };
    wrapper = shallowMount(ApiTokenManager, {
        props: { tokens: [token], availablePermissions: ["read", "write"], defaultPermissions: ["read"] },
        global: { renderStubDefaultSlot: true, mocks: { $page: { props: { jetstream: { flash: {} } } } } },
    });
    wrapper.vm.manageApiTokenPermissions(token);
    await flushPromises();
    expect(hasUnsavedChanges()).toBe(false);
    wrapper.vm.closeApiTokenPermissions();
    await flushPromises();
    expect(hasUnsavedChanges()).toBe(false);
    wrapper.vm.manageApiTokenPermissions(token);
    wrapper.vm.updateApiTokenForm.permissions.push("write");
    await flushPromises();
    expect(token.abilities).toEqual(["read"]);
    expect(hasUnsavedChanges()).toBe(true);
    const confirm = vi.spyOn(window, "confirm").mockReturnValue(false);
    wrapper.vm.closeApiTokenPermissions();
    expect(wrapper.vm.managingPermissionsFor).toEqual(token);
    expect(wrapper.vm.updateApiTokenForm.permissions).toEqual(["read", "write"]);
    confirm.mockReturnValue(true);
    wrapper.vm.closeApiTokenPermissions();
    await flushPromises();
    expect(wrapper.vm.managingPermissionsFor).toBe(null);
    expect(hasUnsavedChanges()).toBe(false);
});
