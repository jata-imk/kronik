<?php

namespace App\Services\Scrapers;

use App\Interfaces\BrowserClientInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class BaseCatalogScraperService
{
    protected HttpClientInterface $http;
    protected BrowserClientInterface $browserClient;
    protected string $url;
    protected string $downloadPath;

    public function __construct(
        HttpClientInterface $http,
        BrowserClientInterface $browserClient,
        string $url,
        string $downloadPath
    ) {
        $this->http = $http;
        $this->browserClient = $browserClient;
        $this->url = $url;
        $this->downloadPath = $this->getOrCreatePath($downloadPath);
    }

    public function getOrCreatePath($relativePath): string
    {
        $this->downloadPath = storage_path($relativePath);

        if (!file_exists($this->downloadPath)) {
            mkdir($this->downloadPath, 0755, true);
        }

        return $this->downloadPath;
    }
}
