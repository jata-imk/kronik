<?php

namespace App\Services\Development;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ResetDataFiles
{
    public function collect(?int $adminId): array
    {
        $files = [];
        $sources = [
            ['cliente_documentos', 'disk', 'path', 'local', '#^clientes/\d+/documentos/[^/]+$#D'],
            ['cliente_consentimientos_sic', 'evidencia_disk', 'evidencia_path', 'local', '#^clientes/\d+/consentimientos-sic/[^/]+$#D'],
            ['documentos_generados', 'disk', 'path', config('documentos.disk', 'local'), '#^documentos-generados/\d+/[^/]+\.pdf$#D'],
            ['documento_recursos', 'disk', 'path', config('documentos.disk', 'local'), '#^documento-recursos/[^/]+\.png$#D'],
            ['solicitud_firmas', 'disk', 'path', config('documentos.disk', 'local'), '#^solicitudes/\d+/firmas/[^/]+$#D'],
        ];

        foreach ($sources as [$table, $diskColumn, $pathColumn, $disk, $pattern]) {
            foreach (DB::table($table)->whereNotNull($pathColumn)->cursor() as $row) {
                $files[] = $this->target($row->$diskColumn, $row->$pathColumn, $disk, $pattern);
            }
        }

        $photoDisk = config('jetstream.profile_photo_disk', 'public');
        $photos = DB::table('users')->whereNotNull('profile_photo_path');
        if ($adminId !== null) {
            $photos->where('id', '!=', $adminId);
        }
        $preservedPhoto = $adminId ? DB::table('users')->where('id', $adminId)->value('profile_photo_path') : null;
        foreach ($photos->pluck('profile_photo_path') as $path) {
            if ($path !== $preservedPhoto) {
                $files[] = $this->target($photoDisk, $path, $photoDisk, '#^profile-photos/[^/]+$#D');
            }
        }

        return array_values(array_unique($files, SORT_REGULAR));
    }

    private function target(?string $disk, string $path, string $expectedDisk, string $pattern): array
    {
        if ($disk !== $expectedDisk || ! preg_match($pattern, $path)
            || str_contains($path, '..') || preg_match('/[\\\\\x00-\x1F\x7F]/', $path)) {
            throw new ResetDataException('Hay un archivo fuera de las rutas previstas. Revisa sus metadatos antes de reiniciar.');
        }

        $this->assertLocalPath($disk, $path);

        return ['disk' => $disk, 'path' => $path];
    }

    // Do not follow symlinks outside a local disk, including directory symlinks.
    private function assertLocalPath(string $disk, string $path): void
    {
        $config = config("filesystems.disks.{$disk}");
        if (! $config) {
            throw new ResetDataException('El disco de un archivo no está configurado.');
        }
        if (($config['driver'] ?? null) !== 'local') {
            return;
        }
        $root = realpath($config['root']);
        if ($root === false || ! is_dir($root) || ! is_readable($root)) {
            throw new ResetDataException('No se puede comprobar la raíz del disco de un archivo. Revisa el almacenamiento antes de continuar.');
        }
        $candidate = $root;
        foreach (explode('/', $path) as $part) {
            $candidate .= DIRECTORY_SEPARATOR.$part;
            $resolved = realpath($candidate);
            $rootPrefix = str_replace('\\', '/', $root).'/';
            $resolvedPath = $resolved === false ? null : str_replace('\\', '/', $resolved);
            if (PHP_OS_FAMILY === 'Windows') {
                $rootPrefix = strtolower($rootPrefix);
                $resolvedPath = $resolvedPath === null ? null : strtolower($resolvedPath);
            }
            if (is_link($candidate) || ($resolvedPath !== null && ! str_starts_with($resolvedPath, $rootPrefix))) {
                throw new ResetDataException('Hay enlaces simbólicos en una ruta de limpieza. Revisa el almacenamiento.');
            }
            if (is_dir($candidate) && ! is_readable($candidate)) {
                throw new ResetDataException('No se puede comprobar un directorio de archivos. Revisa sus permisos.');
            }
        }
    }

    public function delete(array $file): bool
    {
        $this->assertLocalPath($file['disk'], $file['path']);
        $disk = Storage::disk($file['disk']);

        return ! $disk->exists($file['path']) || $disk->delete($file['path']);
    }
}
