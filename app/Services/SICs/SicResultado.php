<?php

namespace App\Services\SICs;

/** Provider values are displayed without invented ranges or risk thresholds. */
final class SicResultado
{
    public function historico(\App\Models\SicQuery $query): ?array
    {
        $body = app(SicOperacionService::class)->leer($query, 'historico') ?? $query->response_data;
        if (is_string($body)) {
            $body = json_decode($body, true);
        }
        if (is_array($body)) {
            return $this->normalizar($body, historico: true);
        }
        $body = [];
        foreach ($query->results()->orderBy('id')->get() as $result) {
            if (! is_array($result->data)) {
                continue;
            }
            $key = match ($result->tipo_registro) {
                'score' => 'scores', 'credito' => 'creditos', 'consulta' => 'consultas', 'domicilio' => 'domicilios', 'empleo' => 'empleos', default => null
            };
            if ($key) {
                $body[$key][] = $result->data;
            }
        }

        return $body ? $this->normalizar($body, historico: true) : null;
    }

    public function normalizar(array $body, bool $historico = false): array
    {
        $scores = [];
        $razones = [];
        if ($historico && isset($body['score']) && is_numeric($body['score'])) {
            $body['score'] = ['valor' => $body['score'], 'nombreScore' => 'Valor heredado sin modelo acreditado'];
        }
        $raw = $body['scores'] ?? (isset($body['score']) ? [$body['score']] : []);
        if (isset($raw['valor'])) {
            $raw = [$raw];
        }
        foreach (is_array($raw) ? $raw : [] as $score) {
            if (! is_array($score)) {
                continue;
            }
            $r = $score['razones'] ?? $score['razon'] ?? [];
            $r = is_array($r) ? array_values($r) : [$r];
            $razones = [...$razones, ...array_filter($r, fn ($v) => is_string($v))];
            if (isset($score['valor']) && is_numeric($score['valor'])) {
                $scores[] = ['modelo' => $score['nombreScore'] ?? $score['nombre'] ?? 'Modelo no informado',
                    'valor' => (string) $score['valor'], 'razones' => $r];
            }
        }
        $secciones = [];
        foreach (['creditos', 'consultas', 'domicilios', 'empleos', 'mensajes'] as $key) {
            if (isset($body[$key]) && is_array($body[$key])) {
                $secciones[$key] = array_values($body[$key]);
            }
        }
        if (isset($body['persona']) && is_array($body['persona'])) {
            $secciones['persona'] = [$body['persona']];
        }
        if (isset($body['declaracionesConsumidor']) && is_string($body['declaracionesConsumidor']) && $body['declaracionesConsumidor'] !== '') {
            $secciones['declaraciones'] = [$body['declaracionesConsumidor']];
        }
        $death = in_array('E2', $razones, true);
        $usable = collect($scores)->contains(fn ($s) => (int) $s['valor'] > 0);

        return ['estado' => $death ? 'bloqueado' : ($usable ? 'con_score' : ($scores || $secciones ? 'sin_score' : 'formato_desconocido')),
            'scores' => $scores, 'razones' => array_values(array_unique($razones)), 'secciones' => $secciones,
            'folio' => $body['folioConsulta'] ?? $body['folio'] ?? null, 'demostrativo' => (bool) ($body['demo'] ?? false)];
    }
}
