<?php

namespace App\Services\Documentos;

use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class RespuestaArchivoPrivado
{
    private const MIME_EXTENSIONS = [
        'application/pdf' => ['pdf'],
        'image/jpeg' => ['jpg', 'jpeg'],
        'image/png' => ['png'],
    ];

    public function make(string $disk, string $path, string $filename, bool $download = false): StreamedResponse
    {
        abort_if($path === '' || str_contains(str_replace('\\', '/', $path), '../'), 404);
        $storage = Storage::disk($disk);
        abort_unless($storage->exists($path), 404);
        $root = realpath($storage->path(''));
        $absolute = realpath($storage->path($path));
        abort_unless($root && $absolute && ($absolute === $root || str_starts_with($absolute, $root.DIRECTORY_SEPARATOR)), 404);

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($absolute) ?: '';
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        abort_unless(isset(self::MIME_EXTENSIONS[$mime]) && in_array($extension, self::MIME_EXTENSIONS[$mime], true), 415);

        $safeName = $this->safeName($filename);
        $headerBag = new ResponseHeaderBag;
        $disposition = $headerBag->makeDisposition(
            $download ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE,
            $safeName,
            preg_replace('/[^A-Za-z0-9._-]+/', '-', $safeName) ?: 'documento',
        );

        return response()->stream(function () use ($absolute): void {
            $stream = fopen($absolute, 'rb');
            if ($stream) {
                fpassthru($stream);
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mime,
            'Content-Length' => (string) filesize($absolute),
            'Content-Disposition' => $disposition,
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self'; sandbox",
            'Referrer-Policy' => 'no-referrer',
        ]);
    }

    private function safeName(string $filename): string
    {
        $filename = preg_replace('/[^\pL\pN._-]+/u', '-', basename($filename)) ?: 'documento';

        return mb_substr($filename, 0, 180);
    }
}
