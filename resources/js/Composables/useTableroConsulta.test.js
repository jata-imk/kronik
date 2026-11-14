import { mount } from "@vue/test-utils";
import { expect, it, vi } from "vitest";
import { router } from "@inertiajs/vue3";
import { useTableroConsulta } from "./useTableroConsulta";

vi.mock("@inertiajs/vue3", () => ({ router: { get: vi.fn() } }));

it("cancela consultas anteriores y descarta errores tardíos al cambiar fechas o alcance", () => {
    let estado;
    const wrapper = mount({
        setup() {
            estado = useTableroConsulta(
                () => ({
                    scope: "current",
                    sucursal_id: 1,
                    corte: "2026-11-13",
                    desde: "2026-11-01",
                    hasta: "2026-11-13",
                }),
                () => "/dashboard",
            );
            return {};
        },
        template: "<div />",
    });
    estado.consultar("capital");
    const primero = router.get.mock.calls.at(-1)[2];
    const cancel = vi.fn();
    primero.onCancelToken({ cancel });
    estado.consulta.value.scope = "all";
    estado.consulta.value.sucursal_id = 2;
    estado.consultar("cobros");
    expect(cancel).toHaveBeenCalledOnce();
    primero.onError({ corte: "Error anterior" });
    primero.onFinish();
    expect(estado.errores.value).toEqual({});
    expect(estado.cargando.value).toBe(true);
    expect(router.get.mock.calls.at(-1)[1]).toMatchObject({
        scope: "all",
        sucursal_id: 2,
        detalle: "cobros",
    });
    router.get.mock.calls.at(-1)[2].onSuccess();
    router.get.mock.calls.at(-1)[2].onFinish();
    expect(estado.cargando.value).toBe(false);
    expect(estado.errores.value).toEqual({});
    estado.consultar();
    router.get.mock.calls.at(-1)[2].onFinish();
    expect(estado.errores.value.consulta).toContain(
        "No se pudo completar la consulta",
    );
    expect(estado.cargando.value).toBe(false);
    wrapper.unmount();
});
