<?php

namespace XD\Ovis;

use Config;
use Convert;
use Exception;
use GuzzleHttp\Client;
use XD\Ovis\Models\Presentation;

/**
 * Class Ovis
 *
 * @author Bram de Leeuw
 */
class Ovis
{
    const API = 'https://api.ovis.nl/';
    const API_SANDBOX = 'https://api-develop.ovis.nl/';
    const EXCEPTION_NO_API_KEY = 1;
    const EXCEPTION_NO_SEARCH_QUERY = 2;

    /**
     * @var string|null
     */
    private static $api_key = null;

    /**
     * You can configure the search body to your needs.
     * See the docs/search.md for the available options.
     *
     * @var array
     */
    private static $search = [
        'itemsPerPage' => 100
    ];

    /**
     * Search the API
     *
     * @param array $query will be merged with the static search params set in the config.
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public static function search($query = array())
    {
        if (($query = array_merge(self::config()->get('search'), $query)) && empty($query)) {
            throw new Exception('No search query is set.', self::EXCEPTION_NO_SEARCH_QUERY);
        }

        return self::client()->request('POST', 'search/', [
            'body' => Convert::array2json($query)
        ]);
    }

    /**
     * Get the available brands and categories
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public static function brands()
    {
        return self::client()->request('GET', 'search/brands');
    }

    /**
     * Retrieve the client minMax values
     *
     * @return \Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public static function minMax()
    {
        return self::client()->request('POST', 'search/minmax/client', [
            'body' => Convert::array2json(['selection' => ['clean', 'sold', 'noimage']])
        ]);
    }

    /**
     * TODO Make a trade in request
     * POST /search/tradeinrequest
     */
    public static function tradeInRequest()
    {}

    /**
     * TODO Make a rental request
     * POST /search/rentalrequest
     */
    public static function rentalRequest()
    {}

    /**
     * TODO Retrieve the rental status
     * POST /search/rentalstatus
     *
     * @param $presentationID
     */
    public static function rentalStatus($presentationID)
    {}

    /**
     * TODO Contact the dealer
     * POST /search/contactdealer
     *
     * array['presentationID']
     * array['name']
     * array['email']
     * array['phoneNumber']
     * array['message']
     * @param array $query
     */
    public static function contactDealer($query = [])
    {}

    /**
     * Get the configured Guzzle client
     *
     * @return Client
     * @throws Exception
     */
    public static function client()
    {
        if (!$key = self::config()->get('api_key')) {
            throw new Exception('No api key is set.', self::EXCEPTION_NO_API_KEY);
        }

        return new Client([
            'base_uri' => self::API,
            'headers' => [
                'Content-Type' => 'application/json; charset=utf-8',
                'Authentication' => $key
            ]
        ]);
    }

    /**
     * Get a client configured for downloading files
     *
     * @return Client
     */
    public static function mediaClient()
    {
        return new Client([
            'referer' => true,
            'http_errors' => false,
            'headers' => [
                'User-Agent' => 'XD\Ovis\Importer/v1.0',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,image/apng,*/*;q=0.8',
                'Accept-Encoding' => 'gzip, deflate, br',
            ]
        ]);
    }

    /**
     * Get the config for this class
     *
     * @return \Config_ForClass
     */
    public static function config()
    {
        return Config::inst()->forClass(get_called_class());
    }
}
