<?php

namespace App\Services;

use App\Interfaces\BrowserClientInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

abstract class BaseCatalogScraperService
{
    protected $url = 'http://omawww.sat.gob.mx/tramitesyservicios/Paginas/anexo_20.htm';
    protected $downloadPath;
    protected $browserClient;
    protected $http;

    public function __construct(HttpClientInterface $http, BrowserClientInterface $browserClient)
    {
        $this->http = $http;
        $this->browserClient = $browserClient;
        $this->downloadPath = $this->getOrCreateDownloadPath('app/sat_cfdi_v4');
    }

    public function getOrCreateDownloadPath($relativePath): string
    {
        $this->downloadPath = storage_path($relativePath);

        if (!file_exists($this->downloadPath)) {
            mkdir($this->downloadPath, 0755, true);
        }

        return $this->downloadPath;
    }
}
