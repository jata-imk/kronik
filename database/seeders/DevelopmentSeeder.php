<?php

namespace Database\Seeders;

use App\Enums\ClienteDocumentoTipo;
use App\Models\Cliente;
use App\Models\ClienteDatosFiscales;
use App\Models\ClienteDocumento;
use App\Models\ClienteGarantia;
use App\Models\ClienteReferencia;
use App\Models\ClienteVinculo;
use App\Models\CodigoPostal;
use App\Models\Direccion;
use App\Models\DivisionAdministrativa;
use App\Models\EmpresaConfiguracion;
use App\Models\Pais;
use App\Models\Permission;
use App\Models\RegimenFiscal;
use App\Models\Sic;
use App\Models\SicApi;
use App\Models\SicQuery;
use App\Models\SicQueryResult;
use App\Models\Sucursal;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class DevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $user = $this->seedUser();

            $this->seedEmpresaConfiguracion();
            $this->seedSucursal();
            $this->seedClientes();
            $this->seedRolesForTeam($user, $user->currentTeam);
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

        $user->forceFill(['name' => 'Test User'])->save();

        $team = $user->ownedTeams()->where('personal_team', true)->first();

        if (! $team) {
            $team = Team::forceCreate([
                'user_id' => $user->id,
                'name' => "{$user->name}'s Team",
                'personal_team' => true,
            ]);
        }

        if ((int) $user->current_team_id !== (int) $team->id) {
            $user->forceFill(['current_team_id' => $team->id])->save();
        }

        return $user->refresh();
    }

    private function seedEmpresaConfiguracion(): void
    {
        $regimenMoral = RegimenFiscal::where('clave', '601')->first();

        EmpresaConfiguracion::updateOrCreate(
            ['singleton_key' => 'default'],
            [
                'razon_social' => 'KRONIK DEMO SA DE CV',
                'nombre_comercial' => 'Kronik Demo',
                'tipo_persona' => 'moral',
                'rfc' => 'KDE260101AB1',
                'regimen_fiscal_id' => $regimenMoral?->id,
                'email' => 'operaciones@example.test',
                'telefono' => '+525512345678',
                'sitio_web' => 'https://example.test',
                'domicilio_fiscal' => [
                    'calle' => 'Av. Paseo de la Reforma',
                    'numero_exterior' => '100',
                    'numero_interior' => 'Piso 4',
                    'colonia' => 'Centro',
                    'municipio' => 'Cuauhtémoc',
                    'estado' => 'Ciudad de México',
                    'codigo_postal' => '06000',
                    'pais' => 'México',
                ],
                'moneda' => 'MXN',
                'zona_horaria' => 'America/Mexico_City',
                'pais_base' => 'MX',
                'parametros_operativos' => [
                    'horario_operacion' => [
                        'lunes_viernes' => '09:00-18:00',
                        'sabado' => '10:00-14:00',
                    ],
                    'folios' => [
                        'credito_prefijo' => 'KRN',
                        'credito_siguiente' => 1001,
                    ],
                    'dias_inhabiles' => [
                        '2026-01-01',
                        '2026-02-02',
                        '2026-03-16',
                    ],
                    'reglas_cobranza' => [
                        'dias_gracia' => 3,
                        'contactar_desde_dia' => 1,
                    ],
                    'cuentas_bancarias' => [
                        [
                            'banco' => 'Banco Demo',
                            'clabe' => '002010077777777771',
                            'uso' => 'cobranza',
                        ],
                    ],
                    'contactos' => [
                        [
                            'nombre' => 'Mesa de Operaciones',
                            'email' => 'operaciones@example.test',
                            'telefono' => '+525512345678',
                        ],
                    ],
                ],
                'integraciones' => [
                    'circulo_credito' => [
                        'habilitado' => false,
                        'env_prefix' => 'CDC',
                    ],
                    'geocoding' => [
                        'habilitado' => false,
                        'env_key' => 'GEOCODING_API_KEY',
                    ],
                ],
                'estatus' => 'borrador',
            ],
        );
    }

    private function seedSucursal(): void
    {
        Sucursal::updateOrCreate(
            ['clave' => 'MATRIZ'],
            [
                'nombre' => 'Matriz',
                'domicilio' => [
                    'calle' => 'Av. Paseo de la Reforma',
                    'numero_exterior' => '100',
                    'colonia' => 'Centro',
                    'municipio' => 'Cuauhtémoc',
                    'estado' => 'Ciudad de México',
                    'codigo_postal' => '06000',
                    'pais' => 'México',
                ],
                'telefono' => '+525512345678',
                'email' => 'matriz@example.test',
                'horario' => [
                    'lunes_viernes' => '09:00-18:00',
                    'sabado' => '10:00-14:00',
                ],
                'prefijo_folio' => 'MTZ',
                'consecutivo_solicitud' => 1,
                'consecutivo_contrato' => 1,
                'consecutivo_credito' => 1,
                'consecutivo_recibo' => 1,
                'activa' => true,
            ],
        );
    }

    private function seedRolesForTeam(User $user, ?Team $team): void
    {
        if (! $team || ! function_exists('setPermissionsTeamId')) {
            return;
        }

        $roleModel = config('permission.models.role');
        $teamsKey = config('permission.column_names.team_foreign_key', 'team_id');
        $globalRoles = $roleModel::where($teamsKey, null)->with('permissions')->get();

        foreach ($globalRoles as $globalRole) {
            if ($globalRole->name === 'Super Admin') {
                continue;
            }

            $teamRole = $roleModel::firstOrCreate(
                [
                    'name' => $globalRole->name,
                    'guard_name' => $globalRole->guard_name,
                    $teamsKey => $team->id,
                ],
            );

            $teamRole->syncPermissions($globalRole->permissions);
        }

        setPermissionsTeamId($team->id);
        $user->assignRole('Super Admin');

        $this->seedPermissionTestUsers($team, $roleModel, $teamsKey);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function seedPermissionTestUsers(Team $team, string $roleModel, string $teamsKey): void
    {
        $profiles = [
            [
                'name' => 'Consulta de Clientes',
                'email' => 'consulta.clientes@example.test',
                'role' => 'Consulta de clientes',
                'permissions' => ['read clientes'],
            ],
            [
                'name' => 'Editor de Expedientes',
                'email' => 'editor.expedientes@example.test',
                'role' => 'Edición de expedientes',
                'permissions' => ['read clientes', 'update clientes'],
            ],
            [
                'name' => 'Sin Acceso a Clientes',
                'email' => 'sin.acceso.clientes@example.test',
                'role' => 'Sin acceso a clientes',
                'permissions' => [],
            ],
        ];

        foreach ($profiles as $profile) {
            $role = $roleModel::firstOrCreate([
                'name' => $profile['role'],
                'guard_name' => 'web',
                $teamsKey => $team->id,
            ]);
            $role->syncPermissions(
                Permission::whereIn('name', $profile['permissions'])->get(),
            );

            $demoUser = User::updateOrCreate(
                ['email' => $profile['email']],
                [
                    'name' => $profile['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'current_team_id' => $team->id,
                ],
            );

            $team->users()->syncWithoutDetaching([
                $demoUser->id => ['role' => null],
            ]);
            $demoUser->syncRoles([$role]);
        }
    }

    private function seedClientes(): void
    {
        if (
            Pais::where('codigo_iso', 'MX')->doesntExist()
            || RegimenFiscal::where('fisica', true)->doesntExist()
            || RegimenFiscal::where('moral', true)->doesntExist()
            || CodigoPostal::query()->doesntExist()
        ) {
            $this->seedFallbackCatalogs();
        }

        $pais = Pais::where('codigo_iso', 'MX')->first();
        $regimenFisica = RegimenFiscal::where('fisica', true)->first();
        $regimenMoral = RegimenFiscal::where('moral', true)->first();
        $codigoPostal = CodigoPostal::with('divisionAdministrativa.padre.padre')
            ->where('codigo', 'like', '06%')
            ->first()
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
                    'telefono_codigo_pais' => '52',
                    'telefono' => '5512345678',
                    'email' => 'ana.garcia@example.test',
                    'sexo' => 'femenino',
                    'ocupacion' => 'Consultora administrativa',
                    'actividad_economica' => 'Servicios profesionales de consultoria',
                    'ingresos_mensuales' => 48000,
                    'egresos_mensuales' => 21500,
                    'origen_recursos' => 'Honorarios profesionales y servicios de consultoria.',
                ],
                'fiscal' => [
                    'tipo_persona' => 'fisica',
                    'regimen_fiscal_id' => $regimenFisica->id,
                    'curp' => 'GALA910418MDFRPN01',
                    'rfc' => 'GALA910418AB8',
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
                    'telefono_codigo_pais' => '52',
                    'telefono' => '5587654321',
                    'email' => 'carlos.martinez@example.test',
                    'sexo' => 'masculino',
                    'ocupacion' => 'Director de operaciones',
                    'actividad_economica' => 'Servicios financieros y administrativos',
                    'ingresos_mensuales' => 72000,
                    'egresos_mensuales' => 34800,
                    'origen_recursos' => 'Sueldo y compensaciones laborales.',
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
                $record['cliente'],
            );

            ClienteDatosFiscales::updateOrCreate(
                ['cliente_id' => $cliente->id],
                $record['fiscal'],
            );

            $this->seedDireccion($cliente, $pais, $codigoPostal, $record['direccion']);
            $this->seedSicQuery($cliente, $sic, $sicApi, $record['score'], $record['status']);
            $this->seedExpediente($cliente);
        }

        $this->seedVinculosYGarantias();
    }

    private function seedFallbackCatalogs(): void
    {
        $pais = Pais::firstOrCreate(
            ['codigo_iso' => 'MX'],
            [
                'nombre_es' => 'México',
                'nombre_us' => 'Mexico',
                'nombre_nativo' => ['spa' => 'México'],
                'idiomas' => ['spa' => 'Español'],
                'codigo_iso3' => 'MEX',
                'emoji' => '🇲🇽',
                'mapas' => [
                    'google' => 'https://www.google.com/maps/place/Mexico',
                    'openstreetmap' => 'https://www.openstreetmap.org/relation/114686',
                ],
            ],
        );

        RegimenFiscal::firstOrCreate(
            ['clave' => '612'],
            [
                'descripcion' => 'Personas Físicas con Actividades Empresariales y Profesionales',
                'fisica' => true,
                'moral' => false,
                'fecha_inicio_vigencia' => '2022-01-01',
                'fecha_fin_vigencia' => '2099-12-31',
            ],
        );

        RegimenFiscal::firstOrCreate(
            ['clave' => '601'],
            [
                'descripcion' => 'General de Ley Personas Morales',
                'fisica' => false,
                'moral' => true,
                'fecha_inicio_vigencia' => '2022-01-01',
                'fecha_fin_vigencia' => '2099-12-31',
            ],
        );

        $estado = DivisionAdministrativa::firstOrCreate(
            [
                'pais_id' => $pais->id,
                'codigo' => 'CMX',
                'nivel' => 1,
            ],
            [
                'nombre' => 'Ciudad de México',
                'tipo' => 'estado',
            ],
        );

        $municipio = DivisionAdministrativa::firstOrCreate(
            [
                'pais_id' => $pais->id,
                'codigo' => 'CUA',
                'nivel' => 2,
                'division_padre_id' => $estado->id,
            ],
            [
                'nombre' => 'Cuauhtémoc',
                'tipo' => 'alcaldia',
            ],
        );

        $localidad = DivisionAdministrativa::firstOrCreate(
            [
                'pais_id' => $pais->id,
                'codigo' => 'CENTRO',
                'nivel' => 3,
                'division_padre_id' => $municipio->id,
            ],
            [
                'nombre' => 'Centro',
                'tipo' => 'colonia',
            ],
        );

        CodigoPostal::firstOrCreate(
            [
                'codigo' => '06000',
                'pais_id' => $pais->id,
                'division_admin_id' => $localidad->id,
            ],
            [
                'datos_adicionales' => [
                    'estado' => 'Ciudad de México',
                    'municipio' => 'Cuauhtémoc',
                    'asentamiento' => 'Centro',
                    'tipo_asentamiento' => 'Colonia',
                    'demo' => true,
                ],
            ],
        );
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
            ],
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
            ],
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
            ],
        );
    }

    private function seedExpediente(Cliente $cliente): void
    {
        foreach (ClienteDocumentoTipo::requeridos() as $tipo) {
            ClienteDocumento::firstOrCreate(
                ['cliente_id' => $cliente->id, 'tipo' => $tipo->value, 'version' => 1],
                ['estado' => 'pendiente', 'es_actual' => true],
            );
        }

        ClienteReferencia::updateOrCreate(
            ['cliente_id' => $cliente->id, 'tipo' => 'personal', 'telefono' => '5511112233'],
            [
                'nombre' => 'Referencia Demo',
                'relacion' => 'Amistad',
                'telefono_codigo_pais' => '52',
                'email' => 'referencia@example.test',
                'notas' => 'Contacto disponible en horario laboral.',
            ],
        );
    }

    private function seedVinculosYGarantias(): void
    {
        $titular = Cliente::where('email', 'ana.garcia@example.test')->first();
        $vinculado = Cliente::where('email', 'carlos.martinez@example.test')->first();

        if (! $titular || ! $vinculado) {
            return;
        }

        ClienteVinculo::updateOrCreate(
            [
                'cliente_id' => $titular->id,
                'cliente_vinculado_id' => $vinculado->id,
                'rol' => 'aval',
            ],
            ['notas' => 'Vinculo demo para validacion del expediente.'],
        );

        ClienteGarantia::updateOrCreate(
            ['cliente_id' => $titular->id, 'descripcion' => 'Vehiculo utilitario demo'],
            [
                'propietario_cliente_id' => $titular->id,
                'tipo' => 'prendaria',
                'valor_estimado' => 285000,
                'moneda' => 'MXN',
                'notas' => 'Valor declarado para datos de demostracion.',
            ],
        );
    }
}
