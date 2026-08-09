import { ref } from "vue";

const currentLocality = (form, initialLocality = null) => {
    if (initialLocality) {
        return initialLocality;
    }

    if (!form.codigo_postal_id || !form.division_admin_tres_id) {
        return null;
    }

    return {
        codigoPostalId: form.codigo_postal_id,
        divisionAdminTresId: form.division_admin_tres_id,
        nombre: form.colonia ?? form.localidad ?? "Localidad guardada",
        tipo: form.tipo_localidad ?? null,
    };
};

export function useDireccionCodigoPostal(formSource, options = {}) {
    const getForm = () =>
        typeof formSource === "function" ? formSource() : formSource;
    const initial = currentLocality(getForm(), options.initialLocality);
    const localidades = ref(initial ? [initial] : []);

    const aplicarCodigoPostal = (contexto) => {
        const form = getForm();
        Object.assign(form, {
            pais_id: contexto.pais?.id ?? null,
            pais_codigo_iso: contexto.pais?.codigo_iso ?? "",
            codigo_postal_id: null,
            codigo_postal: contexto.codigo,
            division_admin_uno_id: contexto.divisionAdminUno?.id ?? null,
            division_admin_dos_id: contexto.divisionAdminDos?.id ?? null,
            division_admin_tres_id: null,
        });

        if ("colonia" in form) form.colonia = "";
        if ("municipio" in form) {
            form.municipio = contexto.divisionAdminDos?.nombre ?? "";
        }
        if ("estado" in form) {
            form.estado = contexto.divisionAdminUno?.nombre ?? "";
        }
        if ("pais" in form) form.pais = contexto.pais?.nombre_es ?? "";

        localidades.value = contexto.localidades;
        options.onApplied?.(contexto);
    };

    const limpiarUbicacionPostal = () => {
        const form = getForm();
        Object.assign(form, {
            pais_id: null,
            pais_codigo_iso: "",
            codigo_postal_id: null,
            division_admin_uno_id: null,
            division_admin_dos_id: null,
            division_admin_tres_id: null,
        });

        for (const field of ["colonia", "municipio", "estado", "pais"]) {
            if (field in form) form[field] = "";
        }

        localidades.value = [];
        options.onCleared?.();
    };

    const seleccionarLocalidad = (value) => {
        const form = getForm();
        const localidad = localidades.value.find(
            (item) =>
                item.codigoPostalId === value ||
                item.divisionAdminTresId === value,
        );

        if (!localidad) {
            form.codigo_postal_id = null;
            form.division_admin_tres_id = null;
            options.onLocalitySelected?.(null);
            return null;
        }

        form.codigo_postal_id = localidad.codigoPostalId;
        form.division_admin_tres_id = localidad.divisionAdminTresId;
        if ("colonia" in form) form.colonia = localidad.nombre;
        options.onLocalitySelected?.(localidad);

        return localidad;
    };

    const onLocalidadChange = ({ value }) => seleccionarLocalidad(value);

    return {
        localidades,
        aplicarCodigoPostal,
        limpiarUbicacionPostal,
        seleccionarLocalidad,
        onLocalidadChange,
    };
}
