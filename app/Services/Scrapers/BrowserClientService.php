<?php

namespace App\Services\Scrapers;

use App\Interfaces\BrowserClientInterface;

use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Contracts\HttpClient\HttpClientInterface;

use Symfony\Component\DomCrawler\Crawler;

class BrowserClientService implements BrowserClientInterface
{
    private HttpBrowser $client;

    public function __construct(HttpClientInterface $http)
    {
        $this->client = new HttpBrowser($http);
    }

    public function request(string $method, string $url, array $parameters = [], array $files = [], array $server = [], ?string $content = null): Crawler
    {
        return $this->client->request($method, $url, $parameters, $files, $server, $content);
    }

    public function getResponseStatusCode(): int
    {
        return $this->client->getResponse()->getStatusCode();
    }

    public function getCrawler(): Crawler
    {
        return $this->client->getCrawler();
    }
}
