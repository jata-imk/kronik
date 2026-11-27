<?php

namespace App\Services\Documentos;

/** Shared static styles and immutable local fonts for HTML and PDF. */
final class DocumentoEstilos
{
    public function css(bool $fonts = true): string
    {
        $css = file_get_contents(resource_path('css/document-content.css'));
        if (! $fonts) {
            return $css;
        }
        foreach (['Sans', 'Serif', 'Mono'] as $family) {
            foreach (['Regular' => [400, 'normal'], 'Bold' => [700, 'normal'], 'Italic' => [400, 'italic'], 'BoldItalic' => [700, 'italic']] as $variant => [$weight, $style]) {
                $font = file_get_contents(public_path("fonts/liberation/Liberation{$family}-{$variant}.ttf"));
                $css .= '@font-face{font-family:"Document '.$family.'";font-weight:'.$weight.';font-style:'.$style.';src:url(data:font/ttf;base64,'.base64_encode($font).')}';
            }
        }

        return $css;
    }
}
