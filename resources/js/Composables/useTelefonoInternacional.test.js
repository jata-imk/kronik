import { describe, expect, it } from "vitest";
import {
    construirTelefonoE164,
    normalizarCodigoPaisTelefono,
    useTelefonoInternacional,
} from "./useTelefonoInternacional";

describe("useTelefonoInternacional", () => {
    it("normaliza el código de país sin duplicar el signo más", () => {
        expect(normalizarCodigoPaisTelefono("++52")).toBe("52");
        expect(construirTelefonoE164("+52", "999 123 4567")).toBe(
            "+529991234567",
        );
    });

    it("mantiene sincronizados el número internacional y sus campos separados", () => {
        const form = { telefono_codigo_pais: "+52", telefono: "9991234567" };
        const telefono = useTelefonoInternacional(form);

        expect(telefono.telefonoInternacional.value).toBe("+529991234567");

        telefono.onChangeNumber({
            number: "+34600111222",
            numberWithoutCountryCode: "600111222",
            dialCode: "+34",
        });

        expect(form).toEqual({
            telefono_codigo_pais: "34",
            telefono: "600111222",
        });
    });

    it("sincroniza campos E.164 de empresa y sucursal", () => {
        const form = { telefono: "+525512345678" };
        const telefono = useTelefonoInternacional(form, {
            e164Key: "telefono",
        });

        telefono.onChangeNumber({ number: "+529991234567" });
        expect(form.telefono).toBe("+529991234567");
    });
});
