import { usePage } from "@inertiajs/vue3";
import { watchEffect } from "vue";

const estados = new WeakMap();

export default {
    mounted(el, binding, vnode) {
        const page = usePage();
        const estado = { accion: binding.value, vnode, bloqueada: false, title: el.getAttribute("title") };
        estado.prevenir = (event) => {
            if (estado.bloqueada) {
                event.preventDefault();
                event.stopImmediatePropagation();
            }
        };
        el.addEventListener("click", estado.prevenir, true);
        estado.actualizar = () => {
            const empresa = page.props.empresaOperacion;
            estado.bloqueada = empresa?.capacidades?.[estado.accion] === false;
            if ("disabled" in el) el.disabled = estado.bloqueada || Boolean(estado.vnode.props?.disabled);
            if (estado.bloqueada) {
                el.setAttribute("aria-disabled", "true");
                el.setAttribute("title", empresa.motivo);
            } else {
                el.removeAttribute("aria-disabled");
                if (estado.title === null) el.removeAttribute("title");
                else el.setAttribute("title", estado.title);
            }
        };
        estado.stop = watchEffect(estado.actualizar, { flush: "post" });
        estados.set(el, estado);
    },
    updated(el, binding, vnode) {
        const estado = estados.get(el);
        if (!estado) return;
        estado.accion = binding.value;
        estado.vnode = vnode;
        estado.actualizar();
    },
    unmounted(el) {
        const estado = estados.get(el);
        estado?.stop();
        el.removeEventListener("click", estado?.prevenir, true);
        estados.delete(el);
    },
};
