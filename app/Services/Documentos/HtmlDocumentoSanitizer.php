<?php

namespace App\Services\Documentos;

use DOMDocument;
use DOMElement;
use DOMNode;

final class HtmlDocumentoSanitizer
{
    private const TAGS = [
        'p', 'br', 'strong', 'em', 'u', 's', 'h1', 'h2', 'h3',
        'ul', 'ol', 'li', 'blockquote', 'span', 'div',
    ];

    private const ACTIVE_TAGS = [
        'script', 'style', 'iframe', 'object', 'embed', 'svg', 'math',
        'img', 'link', 'meta', 'base', 'form', 'input', 'button',
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
                    $this->unwrap($node);

                    continue;
                }

                foreach (iterator_to_array($node->attributes) as $attribute) {
                    if ($attribute->name !== 'class' || ! $this->safeClasses($attribute->value)) {
                        $node->removeAttribute($attribute->name);
                    }
                }
            }

            $this->cleanChildren($node);
        }
    }

    private function safeClasses(string $classes): bool
    {
        foreach (preg_split('/\s+/', trim($classes)) ?: [] as $class) {
            if (! preg_match('/^ql-(align-(center|right|justify)|indent-[1-8]|size-(small|large|huge))$/', $class)) {
                return false;
            }
        }

        return $classes !== '';
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
