<?php

namespace App\Services\Documentos;

use DOMDocument;
use DOMElement;
use DOMNode;

final class HtmlDocumentoSanitizer
{
    private const TAGS = [
        'p', 'br', 'strong', 'em', 'u', 's', 'h1', 'h2', 'h3',
        'ul', 'ol', 'li', 'blockquote', 'span', 'div', 'img',
    ];

    private const ACTIVE_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
        'link', 'meta', 'base', 'form', 'input', 'button',
    ];

    public function sanitize(?string $html): string
    {
        if (blank($html)) {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $document->loadHTML(
            '<?xml encoding="utf-8" ?><div id="documento-raiz">'.$html.'</div>',
            LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NONET,
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        $root = $document->getElementById('documento-raiz');
        if (! $root) {
            return '';
        }

        $this->cleanChildren($root);

        return collect(iterator_to_array($root->childNodes))
            ->map(fn (DOMNode $node) => $document->saveHTML($node))
            ->implode('');
    }

    private function cleanChildren(DOMNode $parent): void
    {
        foreach (iterator_to_array($parent->childNodes) as $node) {
            if ($node instanceof DOMElement) {
                $tag = strtolower($node->tagName);
                if (in_array($tag, self::ACTIVE_TAGS, true)) {
                    $node->parentNode?->removeChild($node);

                    continue;
                }

                if (! in_array($tag, self::TAGS, true)) {
                    $this->cleanChildren($node);
                    $this->unwrap($node);

                    continue;
                }

                if ($tag === 'img' && ! preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/', $node->getAttribute('data-recurso-id'))) {
                    $node->parentNode?->removeChild($node);

                    continue;
                }

                foreach (iterator_to_array($node->attributes) as $attribute) {
                    $allowed = match ($attribute->name) {
                        'class' => $this->safeClasses($attribute->value),
                        'data-recurso-id' => $tag === 'img',
                        'alt' => $tag === 'img' && mb_strlen($attribute->value) <= 160,
                        'width' => $tag === 'img' && ctype_digit($attribute->value) && (int) $attribute->value >= 24 && (int) $attribute->value <= 650,
                        'data-list' => $tag === 'li' && in_array($attribute->value, ['bullet', 'ordered'], true),
                        'style' => true,
                        default => false,
                    };
                    if (! $allowed) {
                        $node->removeAttribute($attribute->name);
                    }
                }
                if ($node->hasAttribute('style')) {
                    $style = $this->safeStyle($node->getAttribute('style'));
                    $node->removeAttribute('style');
                    if ($style !== '') {
                        $node->setAttribute('style', $style);
                    }
                }
            }

            $this->cleanChildren($node);
        }
    }

    private function safeClasses(string $classes): bool
    {
        foreach (preg_split('/\s+/', trim($classes)) ?: [] as $class) {
            if (! preg_match('/^(document-page-break|ql-(align-(center|right|justify)|indent-[1-8]|font-(serif|monospace)|size-(small|large|huge)))$/', $class)) {
                return false;
            }
        }

        return $classes !== '';
    }

    private function safeStyle(string $style): string
    {
        $safe = [];
        foreach (explode(';', $style) as $declaration) {
            $parts = explode(':', $declaration, 2);
            if (count($parts) !== 2) {
                continue;
            }
            [$property, $value] = array_map('trim', $parts);
            $property = strtolower($property);
            $value = strtolower($value);
            $allowed = match ($property) {
                'color', 'background-color' => (bool) preg_match('/^(#[0-9a-f]{6}|#[0-9a-f]{3}|rgb\(\s*\d{1,3}\s*,\s*\d{1,3}\s*,\s*\d{1,3}\s*\))$/', $value),
                'font-size' => in_array($value, ['8pt', '9pt', '10pt', '11pt', '12pt', '14pt', '16pt', '18pt', '20pt', '24pt', '28pt', '32pt', '36pt'], true),
                'text-align' => in_array($value, ['left', 'center', 'right', 'justify'], true),
                default => false,
            };
            if ($allowed) {
                $safe[$property] = $value;
            }
        }
        ksort($safe);

        return implode(';', array_map(fn ($key, $value) => "$key:$value", array_keys($safe), $safe));
    }

    private function unwrap(DOMElement $element): void
    {
        $parent = $element->parentNode;
        if (! $parent) {
            return;
        }

        while ($element->firstChild) {
            $parent->insertBefore($element->firstChild, $element);
        }
        $parent->removeChild($element);
    }
}
