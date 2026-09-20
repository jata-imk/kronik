<?php

namespace App\Services\Documentos;

use App\Models\Cliente;
use App\Models\ClienteGarantia;
use App\Models\EmpresaConfiguracion;
use App\Services\FechaEmpresa;
use Illuminate\Validation\ValidationException;

final class ResolverVariablesDocumento
{
    public function __construct(
        private readonly CatalogoVariablesDocumento $catalogo,
        private readonly FechaEmpresa $fechaEmpresa,
    ) {}

    /** @return array{values: array<string, string>, metadata: array<string, array<string, string>>} */
    public function resolve(array $keys, Cliente $cliente, ?ClienteGarantia $garantia = null): array
    {
        $cliente->loadMissing(['datosFiscales']);
        $garantia?->loadMissing('propietario');
        $empresa = EmpresaConfiguracion::query()->first();
        $raw = [
            'documento.fecha_generacion' => $this->fechaEmpresa->hoy(),
            'empresa.razon_social' => $empresa?->razon_social,
            'empresa.nombre_comercial' => $empresa?->nombre_comercial,
            'empresa.rfc' => $empresa?->rfc,
            'empresa.telefono' => $empresa?->telefono,
            'empresa.email' => $empresa?->email,
            'cliente.nombre_completo' => $this->nombre($cliente),
            'cliente.rfc' => $cliente->datosFiscales?->rfc,
            'cliente.curp' => $cliente->datosFiscales?->curp,
            'cliente.fecha_nacimiento' => $cliente->fecha_nacimiento,
            'cliente.telefono' => trim(($cliente->telefono_codigo_pais ?? '').' '.($cliente->telefono ?? '')),
            'cliente.email' => $cliente->email,
            'garantia.tipo' => $garantia?->tipo?->value,
            'garantia.descripcion' => $garantia?->descripcion,
            'garantia.valor_estimado' => $garantia?->valor_estimado,
            'garantia.moneda' => $garantia?->moneda,
            'garantia.propietario' => $garantia?->propietario ? $this->nombre($garantia->propietario) : null,
        ];
        $catalogo = $this->catalogo->all();
        $values = [];
        $metadata = [];
        $missing = [];

        foreach ($keys as $key) {
            $definition = $catalogo[$key];
            $value = $raw[$key] ?? null;
            if ($definition['requerida'] && blank($value)) {
                $missing[] = $definition['nombre'];
            }
            $formatted = $this->format($value, $definition['formato']);
            $values[$key] = $formatted;
            $metadata[$key] = [
                'origen' => $definition['origen'],
                'formato' => $definition['formato'],
                'valor_hash' => hash('sha256', $formatted),
            ];
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'variables' => 'Faltan datos obligatorios para generar el documento: '.implode(', ', $missing).'.',
            ]);
        }

        return compact('values', 'metadata');
    }

    private function format(mixed $value, string $format): string
    {
        if (blank($value)) {
            return '';
        }

        return match ($format) {
            'fecha_corta' => $value instanceof \DateTimeInterface ? $value->format('d/m/Y') : (string) $value,
            'moneda' => '$'.number_format((float) $value, 2, '.', ','),
            'mayúsculas' => mb_strtoupper((string) $value),
            'minúsculas' => mb_strtolower((string) $value),
            'nombre' => mb_convert_case((string) $value, MB_CASE_TITLE, 'UTF-8'),
            default => trim((string) $value),
        };
    }

    private function nombre(Cliente $cliente): string
    {
        return collect([$cliente->primer_nombre, $cliente->segundo_nombre, $cliente->apellido_paterno, $cliente->apellido_materno])
            ->filter()->implode(' ');
    }
}
