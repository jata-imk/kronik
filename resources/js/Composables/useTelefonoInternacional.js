import { computed, ref } from "vue";

export const normalizarCodigoPaisTelefono = (value) =>
    String(value ?? "").replace(/\D/g, "");

export const construirTelefonoE164 = (codigoPais, numero) => {
    const codigo = normalizarCodigoPaisTelefono(codigoPais);
    const nacional = String(numero ?? "").replace(/\D/g, "");
    return codigo && nacional ? `+${codigo}${nacional}` : "";
};

export function useTelefonoInternacional(formSource, options = {}) {
    const getForm = () =>
        typeof formSource === "function" ? formSource() : formSource;
    const e164Key = options.e164Key ?? null;
    const countryCodeKey = options.countryCodeKey ?? "telefono_codigo_pais";
    const numberKey = options.numberKey ?? "telefono";

    const valueFromForm = () => {
        const form = getForm();
        return e164Key
            ? String(form[e164Key] ?? "")
            : construirTelefonoE164(form[countryCodeKey], form[numberKey]);
    };

    const telefonoInternacional = e164Key
        ? computed({
              get: valueFromForm,
              set: (value) => {
                  getForm()[e164Key] = String(value ?? "");
              },
          })
        : ref(valueFromForm());

    const sincronizarDesdeFormulario = () => {
        telefonoInternacional.value = valueFromForm();
    };

    const onChangeNumber = ({ number, numberWithoutCountryCode, dialCode }) => {
        const form = getForm();
        telefonoInternacional.value = number ?? "";

        if (e164Key) {
            form[e164Key] = number ?? "";
            return;
        }

        form[countryCodeKey] = normalizarCodigoPaisTelefono(dialCode);
        form[numberKey] = String(numberWithoutCountryCode ?? "").replace(
            /\D/g,
            "",
        );
    };

    return {
        telefonoInternacional,
        sincronizarDesdeFormulario,
        onChangeNumber,
    };
}
