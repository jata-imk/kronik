<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\ClienteDatosFiscales;
use App\Models\CodigoPostal;
use App\Models\Direccion;
use App\Models\Pais;
use App\Models\RegimenFiscal;
use App\Models\Sic;
use App\Models\SicApi;
use App\Models\SicQuery;
use App\Models\SicQueryResult;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $this->seedUser();
            $this->seedClientes();
        });
    }

    private function seedUser(): User
    {
        $user = User::where('email', 'test@example.com')->first();

        if (! $user) {
            $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);
        }

        $user->forceFill([
            'name' => 'Test User',
        ])->save();

        $team = $user->ownedTeams()->where('personal_team', true)->first();

        if (! $team) {
            $team = Team::forceCreate([
                'user_id' => $user->id,
                'name' => "{$user->name}'s Team",
                'personal_team' => true,
            ]);
        }

        if (! $user->current_team_id) {
            $user->forceFill([
                'current_team_id' => $team->id,
            ])->save();
        }

        return $user;
    }

    private function seedClientes(): void
    {
        $pais = Pais::where('codigo_iso', 'MX')->first();
        $regimenFisica = RegimenFiscal::where('fisica', true)->first();
        $regimenMoral = RegimenFiscal::where('moral', true)->first();
        $codigoPostal = CodigoPostal::with('divisionAdministrativa.padre.padre')->where('codigo', 'like', '06%')->first()
            ?? CodigoPostal::with('divisionAdministrativa.padre.padre')->first();
        $sic = Sic::where('clave', 'circulo-credito')->first();
        $sicApi = $sic ? SicApi::where('sic_id', $sic->id)->where('clave', 'fico_score_v2')->first() : null;

        if (! $pais || ! $regimenFisica || ! $regimenMoral || ! $codigoPostal) {
            return;
        }

        $clientes = [
            [
                'cliente' => [
                    'primer_nombre' => 'Ana',
                    'segundo_nombre' => 'Lucia',
                    'apellido_paterno' => 'Garcia',
                    'apellido_materno' => 'Lopez',
                    'fecha_nacimiento' => '1991-04-18',
                    'pais_nacimiento_id' => $pais->id,
                    'telefono_codigo_pais' => '+52',
                    'telefono' => '5512345678',
                    'email' => 'ana.garcia@example.test',
                    'sexo' => 'femenino',
                ],
                'fiscal' => [
                    'tipo_persona' => 'fisica',
                    'regimen_fiscal_id' => $regimenFisica->id,
                    'curp' => 'GALA910418MDFRPN01',
                    'rfc' => 'GALA910418ABC',
                    'razon_social' => 'ANA LUCIA GARCIA LOPEZ',
                ],
                'direccion' => [
                    'linea_uno' => 'Av. Paseo de la Reforma 100',
                    'linea_dos' => 'Piso 4',
                    'linea_tres' => 'Centro',
                    'coordenadas' => ['lat' => 19.4326, 'lng' => -99.1332],
                ],
                'score' => 714,
                'status' => 'success',
            ],
            [
                'cliente' => [
                    'primer_nombre' => 'Carlos',
                    'segundo_nombre' => 'Eduardo',
                    'apellido_paterno' => 'Martinez',
                    'apellido_materno' => 'Ramos',
                    'fecha_nacimiento' => '1986-09-02',
                    'pais_nacimiento_id' => $pais->id,
                    'telefono_codigo_pais' => '+52',
                    'telefono' => '5587654321',
                    'email' => 'carlos.martinez@example.test',
                    'sexo' => 'masculino',
                ],
                'fiscal' => [
                    'tipo_persona' => 'moral',
                    'regimen_fiscal_id' => $regimenMoral->id,
                    'curp' => 'MARC860902HDFRMR03',
                    'rfc' => 'KRO260101AB1',
                    'razon_social' => 'KRONIK DEMO SA DE CV',
                ],
                'direccion' => [
                    'linea_uno' => 'Calle Operacion 42',
                    'linea_dos' => null,
                    'linea_tres' => 'Zona Centro',
                    'coordenadas' => ['lat' => 19.4284, 'lng' => -99.1276],
                ],
                'score' => null,
                'status' => 'pending',
            ],
        ];

        foreach ($clientes as $record) {
            $cliente = Cliente::updateOrCreate(
                ['email' => $record['cliente']['email']],
                $record['cliente']
            );

            ClienteDatosFiscales::updateOrCreate(
                ['cliente_id' => $cliente->id],
                $record['fiscal']
            );

            $this->seedDireccion($cliente, $pais, $codigoPostal, $record['direccion']);
            $this->seedSicQuery($cliente, $sic, $sicApi, $record['score'], $record['status']);
        }
    }

    private function seedDireccion(Cliente $cliente, Pais $pais, CodigoPostal $codigoPostal, array $data): void
    {
        $divisionTres = $codigoPostal->divisionAdministrativa;
        $divisionDos = $divisionTres?->padre;
        $divisionUno = $divisionDos?->padre;

        if (! $divisionUno || ! $divisionDos) {
            return;
        }

        Direccion::updateOrCreate(
            [
                'entidad_id' => $cliente->id,
                'entidad_tipo' => $cliente->getMorphClass(),
                'tipo' => 'personal',
            ],
            [
                'pais_id' => $pais->id,
                'codigo_postal_id' => $codigoPostal->id,
                'linea_uno' => $data['linea_uno'],
                'linea_dos' => $data['linea_dos'],
                'linea_tres' => $data['linea_tres'],
                'division_admin_uno_id' => $divisionUno->id,
                'division_admin_dos_id' => $divisionDos->id,
                'division_admin_tres_id' => $divisionTres?->id,
                'datos_adicionales' => $codigoPostal->datos_adicionales,
                'coordenadas' => $data['coordenadas'],
            ]
        );
    }

    private function seedSicQuery(Cliente $cliente, ?Sic $sic, ?SicApi $sicApi, ?int $score, string $status): void
    {
        if (! $sic || ! $sicApi) {
            return;
        }

        $query = SicQuery::updateOrCreate(
            [
                'cliente_id' => $cliente->id,
                'sic_id' => $sic->id,
                'sic_api_id' => $sicApi->id,
            ],
            [
                'fecha_consulta' => now()->subDays($status === 'success' ? 3 : 1),
                'status' => $status,
                'mensaje_error' => $status === 'success' ? null : 'Consulta demo pendiente de respuesta.',
                'response_data' => [
                    'provider' => 'circulo-credito',
                    'api' => $sicApi->clave,
                    'demo' => true,
                    'score' => $score,
                ],
            ]
        );

        if ($status !== 'success' || ! $score) {
            return;
        }

        SicQueryResult::updateOrCreate(
            [
                'sic_query_id' => $query->id,
                'tipo_registro' => 'score',
            ],
            [
                'data' => [
                    'score' => $score,
                    'razones' => [
                        'Historial de pago consistente',
                        'Uso moderado de líneas de crédito',
                    ],
                ],
            ]
        );
    }
}
