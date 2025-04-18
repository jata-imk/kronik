<?php

namespace App\Services\Scrapers;

use App\Interfaces\BrowserClientInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class BaseCatalogScraperService
{
    protected $url = '';
    protected $downloadPath;
    protected $browserClient;
    protected $http;

    public function __construct(HttpClientInterface $http, BrowserClientInterface $browserClient)
    {
        $this->http = $http;
        $this->browserClient = $browserClient;
        $this->downloadPath = $this->getOrCreatePath($this->downloadPath);
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
