<?php

namespace App\Console\Commands;

use App\Services\Development\ResetDataException;
use App\Services\Development\ResetDevelopmentDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class ResetDevelopmentData extends Command
{
    protected $signature = 'dev:reset-data
        {--minimal : Conserva un superadministrador, un equipo, Matriz y la configuración de empresa, sin datos demo}
        {--admin-email= : Correo del superadministrador existente que se conservará en modo mínimo}
        {--no-seed : Elimina los datos sin crear usuarios ni datos demo}
        {--dry-run : Muestra el alcance sin modificar registros ni archivos}
        {--confirm-qa : Declara que todos los datos de esta instalación son de prueba y se pueden eliminar}
        {--resume-cleanup= : Reintenta únicamente los archivos pendientes del reinicio indicado}';

    protected $description = 'Reinicia datos de desarrollo o QA y conserva los catálogos del sistema';

    public function handle(ResetDevelopmentDataService $service): int
    {
        $lock = null;
        try {
            $mode = $this->option('minimal') ? 'minimal' : ($this->option('no-seed') ? 'empty' : 'demo');
            $email = $this->option('admin-email');
            $resume = $this->option('resume-cleanup');
            if (($this->option('minimal') && $this->option('no-seed'))
                || ($mode === 'minimal' && ! $email) || ($mode !== 'minimal' && $email)
                || ($resume && ($this->option('minimal') || $this->option('no-seed') || $email || $this->option('dry-run')))) {
                throw new ResetDataException('Opciones incompatibles. Usa --minimal --admin-email=correo, --no-seed o --resume-cleanup=identificador por separado.');
            }
            if (! $this->option('dry-run')) {
                if (! $this->option('confirm-qa')) {
                    throw new ResetDataException('No se ha eliminado nada. Revisa --dry-run y añade --confirm-qa solo para una instalación exclusivamente de prueba.');
                }
                if (! app()->environment(['local', 'testing']) && ! app()->isDownForMaintenance()) {
                    throw new ResetDataException('Activa el modo mantenimiento y detén los workers y el programador antes del reinicio QA.');
                }
                if (app()->isProduction() && $mode !== 'minimal' && ! $resume) {
                    throw new ResetDataException('Con APP_ENV=production solo se admite --minimal en una instalación exclusivamente QA.');
                }
                $lock = fopen(storage_path('framework/dev-reset-data.lock'), 'c');
                if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
                    throw new ResetDataException('Otro reinicio está en ejecución. Espera a que termine.');
                }
            }
            if (! $resume) {
                $plan = $service->plan($mode, $email);
                $this->line('Entorno: '.app()->environment().' | Conexión: '.DB::getDefaultConnection().' | Base: '.DB::connection()->getDatabaseName());
                $this->line('Modo: '.$mode);
                $this->table(['Tabla', 'Registros actuales'], collect($plan['counts'])->map(fn ($count, $table) => [$table, $count])->values()->all());
                $this->line('Archivos registrados a eliminar: '.count($plan['files']));
                $this->line('También se eliminan roles por equipo y todas las asignaciones de permisos. Se conservan los roles globales y sus permisos.');
                if ($mode === 'minimal') {
                    $this->line("Conservar: administrador #{$plan['admin_id']}, equipo ".($plan['team_id'] ?? 'nuevo Administración').', sucursal '.($plan['branch_id'] ?? 'nueva Matriz').'.');
                    $this->line('Se conserva la configuración de empresa, contraseña, 2FA y foto del administrador. Se revocan sesiones, tokens y recordar sesión.');
                } elseif ($mode === 'demo') {
                    $this->warn('Se crearán cuentas con contraseña demo conocida. No compartas accesos de este modo.');
                }
                if ($this->option('dry-run')) {
                    $this->info('Vista previa completada. No se modificó ningún registro ni archivo.');

                    return self::SUCCESS;
                }
                $resume = $service->reset($mode, $email);
                $this->info('Reinicio de base de datos confirmado. Registro: '.$resume);
            }
            $pending = $service->cleanup($resume);
            if ($pending > 0) {
                $this->error("La base ya se reinició; quedan {$pending} archivos pendientes. Corrige el almacenamiento y ejecuta: php artisan dev:reset-data --resume-cleanup={$resume} --confirm-qa");

                return self::FAILURE;
            }
            $this->info('Reinicio completado. Archivos registrados eliminados y catálogos conservados. Registro: '.$resume);

            return self::SUCCESS;
        } catch (ResetDataException $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        } catch (Throwable $e) {
            report($e);
            $this->error('El reinicio no terminó. Revisa el registro técnico y development_data_resets antes de volver a ejecutarlo.');

            return self::FAILURE;
        } finally {
            if (is_resource($lock)) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }
}
