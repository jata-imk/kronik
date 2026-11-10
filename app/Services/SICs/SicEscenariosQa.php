<?php

namespace App\Services\SICs;

use Illuminate\Validation\ValidationException;

final class SicEscenariosQa
{
    private const INTERNOS = [
        'con_score' => 'Con score',
        'sin_score' => 'Sin score',
        'bloqueado' => 'Razón bloqueante E2',
        'persona_no_encontrada' => 'Persona no encontrada',
        'validacion' => 'Error de validación',
        'autenticacion' => 'Error de autenticación',
        'permisos' => 'Error de permisos',
        'limite' => 'Límite de consultas',
        'fallo' => 'Fallo del proveedor',
        'incompleta' => 'Respuesta incompleta',
        'incierta' => 'Transporte incierto',
    ];

    public function catalogo(string $api): array
    {
        $rows = json_decode(file_get_contents(resource_path('sic/escenarios-cdc.json')), true, flags: JSON_THROW_ON_ERROR)[$api] ?? [];
        foreach ($rows as &$row) {
            $row += ['version' => 1, 'procedencia' => 'cdc_sandbox'];
            $row['nombre'] = str_replace(['Full Report', 'Segmented Report', 'No Hit', ' Folio'], ['Reporte completo', 'Reporte segmentado', 'Sin historial', ' por folio'], $row['nombre']);
            $row['servicio'] = $api;
            $row['fuente'] = match ($api) {
                'fico_score_v2' => 'https://developer.circulodecredito.com.mx/sites/default/files/postman/1747669029/Ficoscore%20v2%20sandbox.postman_collection.json',
                'fintech' => 'https://developer.circulodecredito.com.mx/sites/default/files/postman/1669825486/fintech-score-sandbox-collection.json',
                default => 'https://developer.circulodecredito.com.mx/sites/default/files/postman/1675875186/reporte-de-credito-fico-score-sandox-collection.json',
            };
        }
        unset($row);
        foreach (self::INTERNOS as $key => $nombre) {
            $rows[] = ['clave' => 'interno_'.$key, 'nombre' => 'Simulación interna · '.$nombre,
                'version' => 1, 'procedencia' => 'simulacion_interna', 'caso' => $key, 'peticion' => []];
        }

        return $rows;
    }

    public function opciones(string $api): array
    {
        return array_map(fn ($r) => array_intersect_key($r, array_flip(['clave', 'nombre', 'version', 'procedencia'])), $this->catalogo($api));
    }

    public function resolver(string $api, ?string $clave): array
    {
        $this->habilitado();
        $clave ??= $api === 'fintech' ? 'cdc_fintech_07' : 'cdc_'.$api.'_01';
        foreach ($this->catalogo($api) as $row) {
            if ($row['clave'] === $clave) {
                if ($row['procedencia'] === 'simulacion_interna') {
                    $row['servicio'] = $api;
                    if ($row['caso'] !== 'incierta') {
                        $row['simulacion'] = $this->simular($row);
                    }
                }

                return $row;
            }
        }
        throw ValidationException::withMessages(['escenario_qa' => 'Selecciona un escenario disponible para este servicio SIC.']);
    }

    public function habilitado(): void
    {
        CirculoSandboxGateway::habilitado();
        if (config('sic.escenarios_qa_habilitados') !== true) {
            throw ValidationException::withMessages(['escenario_qa' => 'Los escenarios SIC QA están desactivados.']);
        }
    }

    public function simular(array $escenario): array
    {
        if (isset($escenario['simulacion'])) {
            return $escenario['simulacion'];
        }
        $caso = $escenario['caso'];
        if ($caso === 'incierta') {
            throw new \RuntimeException('Transporte sintético incierto.');
        }
        $status = match ($caso) {
            'sin_score' => 204, 'persona_no_encontrada' => 404, 'validacion' => 400,
            'autenticacion' => 401, 'permisos' => 403, 'limite' => 429, 'fallo' => 503, default => 200,
        };
        $body = match ($caso) {
            'con_score' => ['demo' => true, 'score' => ['nombreScore' => 'Modelo sintético QA', 'valor' => 680]],
            'bloqueado' => ['demo' => true, 'score' => ['valor' => 0, 'razones' => ['E2']]],
            'sin_score' => [],
            'incompleta' => ['respuesta_incompleta' => true],
            default => ['errores' => [['codigo' => $caso === 'persona_no_encontrada' ? '404.1' : (string) $status]]],
        };

        return ['http_status' => $status, 'respuesta' => $body,
            'resultado' => app(SicResultado::class)->interpretar($status, $body)];
    }
}
