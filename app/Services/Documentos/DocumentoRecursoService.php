<?php

namespace App\Services\Documentos;

use App\Models\DocumentoRecurso;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class DocumentoRecursoService
{
    public function upload(UploadedFile $file, ?int $userId): DocumentoRecurso
    {
        $info = @getimagesize($file->getRealPath());
        if (! $info || ! in_array($info['mime'], ['image/png', 'image/jpeg'], true)
            || $file->getSize() > 2097152 || max($info[0], $info[1]) > 2048) {
            throw ValidationException::withMessages(['archivo' => 'Seleccione una imagen PNG o JPG válida, de hasta 2 MB y 2048 × 2048 píxeles.']);
        }
        $image = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if (! $image) {
            throw ValidationException::withMessages(['archivo' => 'No fue posible leer la imagen. Seleccione otro archivo PNG o JPG.']);
        }
        imagealphablending($image, false);
        imagesavealpha($image, true);
        ob_start();
        try {
            imagepng($image);
            $bytes = ob_get_contents();
        } finally {
            ob_end_clean();
            imagedestroy($image);
        }
        $id = (string) Str::uuid();
        $disk = config('documentos.disk', 'local');
        $path = "documento-recursos/{$id}.png";
        if (! Storage::disk($disk)->put($path, $bytes)) {
            throw new \RuntimeException('No fue posible almacenar la imagen.');
        }
        try {
            return DocumentoRecurso::create([
                'id' => $id, 'nombre' => Str::limit(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME), 160, ''),
                'disk' => $disk, 'path' => $path, 'mime_type' => 'image/png',
                'ancho' => $info[0], 'alto' => $info[1], 'tamano_bytes' => strlen($bytes),
                'archivo_hash' => hash('sha256', $bytes), 'creado_por' => $userId,
            ]);
        } catch (\Throwable $error) {
            Storage::disk($disk)->delete($path);
            throw $error;
        }
    }

    public function references(string ...$sections): array
    {
        preg_match_all('/data-recurso-id="([a-f0-9-]{36})"/', implode('', $sections), $matches);
        $ids = array_values(array_unique($matches[1]));
        sort($ids);
        $resources = $ids ? DocumentoRecurso::whereIn('id', $ids)->get()->keyBy('id') : collect();
        if (count($ids) !== $resources->count()) {
            throw ValidationException::withMessages(['contenido_html' => 'Una imagen ya no está disponible. Selecciónela nuevamente desde la biblioteca.']);
        }

        return collect($ids)->mapWithKeys(fn ($id) => [$id => $resources[$id]->archivo_hash])->all();
    }

    public function bytes(DocumentoRecurso $resource): string
    {
        $expected = "documento-recursos/{$resource->id}.png";
        abort_unless($resource->path === $expected, 404);
        $bytes = Storage::disk($resource->disk)->get($expected);
        abort_unless(is_string($bytes) && hash_equals($resource->archivo_hash, hash('sha256', $bytes)), 404);

        return $bytes;
    }

    public function embed(string $html): string
    {
        $references = $this->references($html);
        if (! $references) {
            return $html;
        }
        $resources = DocumentoRecurso::whereIn('id', array_keys($references))->get()->keyBy('id');

        return preg_replace_callback('/<img\b[^>]*data-recurso-id="([a-f0-9-]{36})"[^>]*>/i', function ($match) use ($resources) {
            return str_replace('<img ', '<img src="data:image/png;base64,'.base64_encode($this->bytes($resources[$match[1]])).'" ', $match[0]);
        }, $html) ?? $html;
    }
}
