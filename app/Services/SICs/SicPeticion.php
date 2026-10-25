<?php

namespace App\Services\SICs;

use App\Models\Cliente;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class SicPeticion
{
    private const ESTADOS = ['AGUASCALIENTES' => 'AGS', 'BAJA CALIFORNIA' => 'BC', 'BAJA CALIFORNIA SUR' => 'BCS',
        'CAMPECHE' => 'CAMP', 'COAHUILA' => 'COAH', 'COAHUILA DE ZARAGOZA' => 'COAH', 'COLIMA' => 'COL', 'CHIAPAS' => 'CHIS',
        'CHIHUAHUA' => 'CHIH', 'CIUDAD DE MEXICO' => 'CDMX', 'DISTRITO FEDERAL' => 'DF', 'DURANGO' => 'DGO', 'GUANAJUATO' => 'GTO',
        'GUERRERO' => 'GRO', 'HIDALGO' => 'HGO', 'JALISCO' => 'JAL', 'MEXICO' => 'MEX', 'ESTADO DE MEXICO' => 'MEX',
        'MICHOACAN' => 'MICH', 'MICHOACAN DE OCAMPO' => 'MICH', 'MORELOS' => 'MOR', 'NAYARIT' => 'NAY', 'NUEVO LEON' => 'NL',
        'OAXACA' => 'OAX', 'PUEBLA' => 'PUE', 'QUERETARO' => 'QRO', 'QUINTANA ROO' => 'QROO', 'SAN LUIS POTOSI' => 'SLP',
        'SINALOA' => 'SIN', 'SONORA' => 'SON', 'TABASCO' => 'TAB', 'TAMAULIPAS' => 'TAMP', 'TLAXCALA' => 'TLAX',
        'VERACRUZ' => 'VER', 'VERACRUZ DE IGNACIO DE LA LLAVE' => 'VER', 'YUCATAN' => 'YUC', 'ZACATECAS' => 'ZAC'];

    public function construir(Cliente $cliente, string $api, string $folio): array
    {
        $dom = $cliente->direcciones()->with(['codigoPostal', 'divisionAdministrativaUno', 'divisionAdministrativaDos', 'divisionAdministrativaTres', 'pais'])->orderBy('id')->first();
        $estado = self::ESTADOS[Str::upper(Str::ascii($dom?->divisionAdministrativaUno?->nombre ?? ''))] ?? null;
        $cp = (string) ($dom?->codigoPostal?->codigo ?? '');
        if (! $cliente->primer_nombre || ! $cliente->apellido_paterno || ! $cliente->fecha_nacimiento
            || ! $dom?->linea_uno || ! $estado || ! preg_match('/^\d{5}$/D', $cp)
            || ! $dom?->divisionAdministrativaDos?->nombre || ! $dom?->divisionAdministrativaTres?->nombre
            || $dom?->pais?->codigo_iso !== 'MX') {
            throw ValidationException::withMessages(['sic' => 'Completa nombre, apellido, nacimiento y domicilio mexicano con municipio, colonia, estado y código postal válido antes de consultar.']);
        }
        $nombreCiudad = data_get($dom->codigoPostal->datos_adicionales, 'nombre_ciudad');
        $ciudad = is_string($nombreCiudad) ? self::textoDomicilio($nombreCiudad) : '';
        if ((in_array($api, ['fico_score_v2', 'rc_fico_score'], true) && $ciudad === '') || mb_strlen($ciudad) > 65) {
            throw ValidationException::withMessages(['sic' => 'El código postal seleccionado no tiene una ciudad válida para este servicio SIC. Revisa el catálogo postal antes de consultar.']);
        }
        $persona = ['primerNombre' => Str::upper(Str::ascii($cliente->primer_nombre)),
            'apellidoPaterno' => Str::upper(Str::ascii($cliente->apellido_paterno)),
            'fechaNacimiento' => $cliente->fecha_nacimiento->format('Y-m-d'),
            'domicilio' => ['direccion' => self::textoDomicilio(trim($dom->linea_uno.' '.$dom->linea_dos)), 'CP' => $cp, 'estado' => $estado,
                'delegacionMunicipio' => self::textoDomicilio($dom->divisionAdministrativaDos->nombre),
                'coloniaPoblacion' => self::textoDomicilio($dom->divisionAdministrativaTres?->nombre ?? '')]];
        if ($ciudad !== '') {
            $persona['domicilio']['ciudad'] = $ciudad;
        }
        if ($cliente->apellido_materno) {
            $persona['apellidoMaterno'] = Str::upper(Str::ascii($cliente->apellido_materno));
        }
        if ($cliente->segundo_nombre) {
            $persona['segundoNombre'] = Str::upper(Str::ascii($cliente->segundo_nombre));
        }
        if ($rfc = $cliente->datosFiscales?->rfc) {
            $persona['RFC'] = Str::upper(trim($rfc));
        }
        if ($api === 'fico_score_v2') {
            $persona['nombres'] = trim($persona['primerNombre'].' '.($persona['segundoNombre'] ?? ''));
            unset($persona['primerNombre'], $persona['segundoNombre']);

            return ['folio' => $folio, 'persona' => $persona];
        }
        if ($api === 'fintech') {
            $folioSandbox = config('sic.servicios.fintech.folio_sandbox');
            if (! is_string($folioSandbox) || trim($folioSandbox) === '') {
                throw ValidationException::withMessages(['sic' => 'El escenario de simulación Fintech no está configurado. Solicita al administrador técnico revisar la configuración de QA.']);
            }
            $persona['domicilio']['pais'] = 'MX';

            return ['folioOtorgante' => $folioSandbox, 'persona' => $persona];
        }
        $persona['nacionalidad'] = 'MX';

        return $persona;
    }

    private static function textoDomicilio(string $value): string
    {
        // Catalog labels can contain parentheses rejected by the sandbox; keep the source unchanged.
        return Str::squish(Str::upper(str_replace(['(', ')'], ' ', Str::ascii($value))));
    }
}
