<?php


namespace App\Libraries;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\RequestOptions;

class APIUtils
{
    public static function request(Client $client, string $method, string $localEndpoint, array $json = [], array $headers =  [])
    {
        try
        {
            $results = $client->request($method, $localEndpoint, [
                RequestOptions::JSON => $json,
                RequestOptions::HEADERS => $headers
            ])
                ->getBody()
                ->getContents();
        }
        catch (RequestException | GuzzleException $exception)
        {
            $response = $exception->getResponse();
            throw $exception;
        }

        return json_decode($results, true);
    }
}