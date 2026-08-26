<?php

namespace App\Services\Documentos;

use App\Enums\DocumentoPlantillaTipo;

final class CatalogoVariablesDocumento
{
    /** @return array<string, array<string, mixed>> */
    public function all(): array
    {
        $todas = array_map(fn (DocumentoPlantillaTipo $tipo) => $tipo->value, DocumentoPlantillaTipo::cases());

        return [
            'documento.fecha_generacion' => $this->definition('Fecha de generación', 'fecha', 'sistema', 'fecha_corta', true, $todas),
            'empresa.razon_social' => $this->definition('Razón social', 'texto', 'configuración de empresa', 'texto', true, $todas),
            'empresa.nombre_comercial' => $this->definition('Nombre comercial', 'texto', 'configuración de empresa', 'texto', false, $todas),
            'empresa.rfc' => $this->definition('RFC de la empresa', 'rfc', 'configuración de empresa', 'mayúsculas', false, $todas),
            'empresa.telefono' => $this->definition('Teléfono de la empresa', 'teléfono', 'configuración de empresa', 'texto', false, $todas),
            'empresa.email' => $this->definition('Correo de la empresa', 'correo', 'configuración de empresa', 'minúsculas', false, $todas),
            'cliente.nombre_completo' => $this->definition('Nombre completo del cliente', 'texto', 'expediente del cliente', 'nombre', true, $todas),
            'cliente.rfc' => $this->definition('RFC del cliente', 'rfc', 'datos fiscales del cliente', 'mayúsculas', false, $todas),
            'cliente.curp' => $this->definition('CURP del cliente', 'curp', 'datos fiscales del cliente', 'mayúsculas', false, $todas),
            'cliente.fecha_nacimiento' => $this->definition('Fecha de nacimiento', 'fecha', 'expediente del cliente', 'fecha_corta', false, $todas),
            'cliente.telefono' => $this->definition('Teléfono del cliente', 'teléfono', 'expediente del cliente', 'texto', false, $todas),
            'cliente.email' => $this->definition('Correo del cliente', 'correo', 'expediente del cliente', 'minúsculas', false, $todas),
            'garantia.tipo' => $this->definition('Tipo de garantía', 'texto', 'garantía del expediente', 'nombre', true, [DocumentoPlantillaTipo::Garantia->value]),
            'garantia.descripcion' => $this->definition('Descripción de la garantía', 'texto', 'garantía del expediente', 'texto', true, [DocumentoPlantillaTipo::Garantia->value]),
            'garantia.valor_estimado' => $this->definition('Valor estimado', 'moneda', 'garantía del expediente', 'moneda', false, [DocumentoPlantillaTipo::Garantia->value]),
            'garantia.moneda' => $this->definition('Moneda de la garantía', 'moneda_iso', 'garantía del expediente', 'mayúsculas', false, [DocumentoPlantillaTipo::Garantia->value]),
            'garantia.propietario' => $this->definition('Propietario de la garantía', 'texto', 'garantía del expediente', 'nombre', false, [DocumentoPlantillaTipo::Garantia->value]),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public function forType(DocumentoPlantillaTipo $tipo): array
    {
        return collect($this->all())
            ->filter(fn (array $definition) => in_array($tipo->value, $definition['tipos'], true))
            ->map(fn (array $definition, string $key) => ['clave' => $key, ...$definition])
            ->values()
            ->all();
    }

    /** @return array<string, mixed> */
    private function definition(string $nombre, string $tipo, string $origen, string $formato, bool $requerida, array $tipos): array
    {
        return compact('nombre', 'tipo', 'origen', 'formato', 'requerida', 'tipos') + [
            'si_falta' => $requerida ? 'impedir_generacion' : 'cadena_vacia',
        ];
    }
}
