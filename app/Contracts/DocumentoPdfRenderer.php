<?php

namespace App\Contracts;

interface DocumentoPdfRenderer
{
    public function render(string $bodyHtml, ?string $headerHtml = null, ?string $footerHtml = null): string;
}
