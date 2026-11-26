<?php

namespace App\Services\Communications;

use App\Services\Documentos\CompiladorPlantillaDocumento;
use Illuminate\Validation\ValidationException;

final class NotificationContent
{
    public const VARIABLES = ['nombre', 'fecha_vencimiento'];

    public function __construct(private CompiladorPlantillaDocumento $compiler) {}

    public function content(string $channel, array $data): array
    {
        $format = $data['format'] ?? 'text';
        $body = (string) ($data['body'] ?? '');
        $subject = $channel === 'email' ? (string) ($data['subject'] ?? '') : null;
        if (! in_array($channel, ['sms', 'email'], true) || ! in_array($format, ['text', 'html'], true) || ($channel === 'sms' && $format !== 'text')) {
            throw ValidationException::withMessages(['format' => 'Seleccione un formato válido para este canal. SMS solo admite texto plano.']);
        }
        foreach ([$body, $subject ?? ''] as $text) {
            $rest = str_replace(array_map(fn ($key) => '{'.$key.'}', self::VARIABLES), '', $text);
            if (str_contains($rest, '{') || str_contains($rest, '}')) {
                throw ValidationException::withMessages(['body' => 'La plantilla contiene una variable no permitida. Use {nombre} o {fecha_vencimiento}.']);
            }
        }
        if ($format === 'html') {
            // Notification mail supports rich text, not private document images or pagination.
            if (preg_match('/<img\b|document-page-break/i', $body)) {
                throw ValidationException::withMessages(['body' => 'El correo admite formato de texto, sin imágenes ni saltos de página.']);
            }
            $body = $this->compiler->sanitize($body);
        }
        $visible = $format === 'html' ? html_entity_decode(strip_tags($body), ENT_QUOTES | ENT_HTML5, 'UTF-8') : $body;
        if (trim(str_replace(["\xc2\xa0", "\xe2\x80\x8b"], '', $visible)) === '' || mb_strlen($body) > ($format === 'html' ? 200000 : 2000)) {
            throw ValidationException::withMessages(['body' => 'Escriba un contenido válido dentro de la longitud permitida.']);
        }
        if ($channel === 'email' && (trim($subject ?? '') === '' || mb_strlen($subject) > 160)) {
            throw ValidationException::withMessages(['subject' => 'Escriba el asunto del correo, de hasta 160 caracteres.']);
        }

        return ['format' => $format, 'subject' => $subject, 'body' => $body,
            'content_hash' => hash('sha256', json_encode([$format, $subject, $body], JSON_THROW_ON_ERROR))];
    }

    public function render(array $content, array $variables): array
    {
        $subject = $content['subject'] ?? '';
        $body = $content['body'];
        preg_match_all('/\{(nombre|fecha_vencimiento)\}/', $subject."\n".$body, $matches);
        foreach (array_unique($matches[1]) as $key) {
            if (! isset($variables[$key]) || trim((string) $variables[$key]) === '') {
                throw ValidationException::withMessages(['body' => 'Falta un dato requerido por la plantilla. Revise el nombre y la fecha del evento.']);
            }
        }
        $tokens = array_combine(array_map(fn ($key) => '{'.$key.'}', self::VARIABLES), array_map(fn ($key) => $variables[$key] ?? '', self::VARIABLES));
        if (($content['format'] ?? 'text') === 'html') {
            $html = strtr($body, array_combine(array_keys($tokens), array_map(fn ($key) => '{{'.$key.'}}', self::VARIABLES)));
            $body = $this->compiler->render($html, $variables);
        } else {
            $body = strtr($body, $tokens);
        }

        return ['subject' => strtr($subject, $tokens), 'body' => $body, 'format' => $content['format'] ?? 'text', 'variables' => $variables];
    }

    public function preview(string $channel, array $data): array
    {
        $rendered = $this->render($this->content($channel, $data), ['nombre' => 'María', 'fecha_vencimiento' => '30/11/2026']);

        return [...$rendered, 'measurement' => $channel === 'sms' ? app(SmsText::class)->measure($rendered['body'], true) : null,
            'notice' => 'Previsualización estimada con datos sintéticos. No genera envíos ni documentos.'];
    }
}
