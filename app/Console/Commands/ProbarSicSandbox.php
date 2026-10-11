<?php

namespace App\Console\Commands;

use App\Services\SICs\CirculoSandboxGateway;
use Illuminate\Console\Command;

/** Explicit laboratory operation. It never consults application clients or stores usable evidence. */
class ProbarSicSandbox extends Command
{
    protected $signature = 'sic:probar-sandbox {servicio} {--confirmar-qa : Autoriza una petición sintética al sandbox}';

    protected $description = 'Verifica uno de los tres servicios QA con el caso sintético publicado por el proveedor';

    public function handle(CirculoSandboxGateway $gateway): int
    {
        $api = $this->argument('servicio');
        if (! $this->option('confirmar-qa') || ! in_array($api, array_keys(config('sic.servicios')), true)) {
            $this->error('Selecciona fico_score_v2, fintech o rc_fico_score y confirma --confirmar-qa. No se realizó ninguna petición.');

            return self::INVALID;
        }
        CirculoSandboxGateway::habilitado();
        $dom = ['direccion' => 'PASADISO ENCONTRADO 58', 'coloniaPoblacion' => 'MONTEVIDEO',
            'ciudad' => 'CIUDAD DE MEXICO', 'CP' => '07730', 'delegacionMunicipio' => 'GUSTAVO A MADERO', 'estado' => 'CDMX'];
        $persona = ['apellidoPaterno' => 'RATCLIFFE', 'apellidoMaterno' => 'BERBEL', 'fechaNacimiento' => '1965-08-09', 'RFC' => 'SEPJ650809JG1', 'domicilio' => $dom];
        $payload = match ($api) {
            'fico_score_v2' => ['folio' => '240101000003', 'persona' => [...$persona, 'nombres' => 'CARLO']],
            'fintech' => ['folioOtorgante' => '20210304', 'persona' => [...$persona, 'apellidoPaterno' => 'SESENTAYDOS', 'apellidoMaterno' => 'PRUEBA',
                'primerNombre' => 'JUAN', 'segundoNombre' => 'JUAN', 'domicilio' => [...$dom, 'pais' => 'MX']]],
            'rc_fico_score' => [...$persona, 'primerNombre' => 'JUAN PRUEBA SIETE', 'apellidoPaterno' => 'PRUEBA', 'apellidoMaterno' => 'SIETE', 'RFC' => 'PUSJ800107H2O', 'nacionalidad' => 'MX',
                'domicilio' => ['direccion' => 'INSURGENTES SUR 1001', 'coloniaPoblacion' => 'INSURGENTES SUR', 'delegacionMunicipio' => 'CIUDAD DE MEXICO', 'ciudad' => 'CIUDAD DE MEXICO', 'estado' => 'CDMX', 'CP' => '11230']],
        };
        try {
            $result = $gateway->consultar($api, $payload);
            $this->line(json_encode(['servicio' => $api, 'ambiente' => 'qa', 'fecha' => now()->toISOString(),
                'http_status' => $result['http_status'], 'estado' => $result['resultado']['estado'],
                'scores_recibidos' => count($result['resultado']['scores']), 'secciones_recibidas' => array_keys($result['resultado']['secciones'])], JSON_UNESCAPED_UNICODE));

            return in_array($result['resultado']['estado'], ['con_score', 'sin_score', 'bloqueado'], true) ? self::SUCCESS : self::FAILURE;
        } catch (\Throwable $exception) {
            $cause = $exception;
            while ($cause && ! method_exists($cause, 'getHandlerContext')) {
                $cause = $cause->getPrevious();
            }
            $errno = $cause?->getHandlerContext()['errno'] ?? null;
            if (is_int($errno)) {
                $this->line('Código de transporte cURL: '.$errno.'.');
            }
            $this->error('No se pudo confirmar la respuesta del sandbox. No se reintentó ni guardó un resultado. Revisa conectividad y habilitación del servicio.');

            return self::FAILURE;
        }
    }
}
