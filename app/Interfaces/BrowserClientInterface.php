<?php

namespace App\Interfaces;

use Symfony\Component\DomCrawler\Crawler;

interface BrowserClientInterface
{
    public function request(string $method, string $url, array $parameters = [], array $files = [], array $server = [], ?string $content = null): Crawler;

    public function getResponseStatusCode(): int;

    public function getCrawler(): Crawler;
}
